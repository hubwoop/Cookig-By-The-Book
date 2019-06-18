$(function () {
    const $sortableList = $("#sortable_list");
    const $jsonStore = $("#cbtb_ingredients_field");
    const $submitButton = $("#list_item_adder");
    const $titleInput = $("#add-new-ingredient-title");
    const $amountInput = $("#add-new-ingredient-amount");
    const $unitInput = $("#add-new-ingredient-unit");
    const $saveNotice = $("#cbtb-ingredients-list-save-notice");
    const $undo = $("#cbtb-ingredients-undo");
    const $redo = $("#cbtb-ingredients-redo");
    const inputs = [$titleInput, $amountInput, $unitInput];
    const identifiers = ["title", "amount", "unit"];
    let history, initialIngredients; // initialized in init();
    // generated and passed down by wp_localize_script in class-recipe-block-editor.php
    const apiRoot = cbtbIngredientsRestSettings["root"];
    const nonce = cbtbIngredientsRestSettings["nonce"];
    const loggingEnabled = cbtbIngredientsRestSettings["loggingEnabled"];

    /*
    * Manages this meta-boxes state history by combining an array
    * of remembered states with a "pointer" to the currently rendered
    * state
    */
    class History {

        constructor(initialIngredients) {
            this.history = [];
            this.history.push(initialIngredients);
            this.pointer = 0;
            $redo.css('color', '#bcbcbc');
            $undo.css('color', '#bcbcbc');
        }

        _atPresent() {
            return this.pointer === this.history.length - 1;
        }

        _atOldest() {
            return this.pointer === 0;
        }

        push(ingredients) {
            if (!this._atPresent()) {
                this.history = this.history.slice(0, this.pointer)
            }
            this.history.push(ingredients);
            this.pointer = this.history.length - 1;
            this._decideOnButtonStates();
        }

        undo(e) {
            e.preventDefault();
            if (!this._atOldest()) {
                this.pointer -= 1;
                this.makeHistory()
            }
            this._decideOnButtonStates();
        }

        redo(e) {
            e.preventDefault();
            if (!this._atPresent()) {
                this.pointer += 1;
                this.makeHistory()
            }
            this._decideOnButtonStates();
        }

        _decideOnButtonStates() {
            // cant go forward
            if (this._atPresent()) {
                $redo.css('color', '#bcbcbc');
            } else {
                $redo.css('color', 'black');
            }
            // cant go backwards
            if (this._atOldest()) {
                $undo.css('color', '#bcbcbc');
            } else {
                $undo.css('color', 'black')
            }
        }

        makeHistory() {
            render(this.history[this.pointer]);
            parseAndStore();
        }
    }

    init();

    function init() {
        try {
            initialIngredients = JSON.parse($jsonStore.val());
        } catch(e) {
            if(loggingEnabled) {
                console.error("Couldn't parse ingredients:", $jsonStore.val(),"Falling back to empty list", e);
            }
            initialIngredients = [];
        }
        history = new History(initialIngredients);

        render(initialIngredients);
        registerEventListeners();

        $sortableList.sortable({
            update: saveState,
            start: styleItemBeingSorted,
            stop: removeBeingSortedStyles,
            cursor: 'grabbing'
        });

        document.getElementById("sortable_list").addEventListener("click", removeListItem);
    }

    function render(ingredients) {
        $sortableList.empty();
        ingredients.forEach((ingredient) => {
            $sortableList.append(produceIngredientHtml(ingredient))
        });
    }

    function parseAndAddNewIngredient(e) {
        e.preventDefault();
        const newIngredient = {
            title: stripHTML($titleInput.val()),
            amount: stripHTML($amountInput.val()),
            unit: stripHTML($unitInput.val())
        };
        add(newIngredient);
        // reset form
        inputs.forEach((input) => input.val(''));
        $titleInput.focus();
    }

    function registerEventListeners() {
        $submitButton.click(parseAndAddNewIngredient);
        $undo.click(e => history.undo(e));
        $redo.click(e => history.redo(e));

        // Enter key pressed while cursor in one of the add-new-ingredient fields.
        $("#add-new-ingredient-title, #add-new-ingredient-amount, #add-new-ingredient-unit").on('keyup', function (e) {
            if (e.keyCode === 13) {
                parseAndAddNewIngredient(e);
            }
        });

        wp.data.subscribe(function () {
            const isSaving = wp.data.select('core/editor').isSavingPost();
            const isAutoSaving = wp.data.select('core/editor').isAutosavingPost();

            if (isSaving && !isAutoSaving) {
                $saveNotice.toggle(false);
            }
        });
    }

    function produceIngredientHtml(ingredient) {
        return `
        <li class="cbtb-ingredients-table">
            <span class="cbtb-ingredient-title">${ingredient.title ? stripHTML(ingredient.title) : ''}</span>
            <span class="cbtb-ingredient-amount">${ingredient.amount ? ingredient.amount : ''}</span>
            <span class="cbtb-ingredient-unit">${ingredient.unit ? ingredient.unit : ''}</span>
            <div class="cbtb-remove-button"><span class="dashicons dashicons-dismiss"></span></div>
        </li>`
    }

    function parseAndStore() {
        const parsedList = parseListDOM();
        store(parsedList);
        return parsedList;
    }

    function saveState() {
        const parsedList = parseAndStore();
        history.push(parsedList);
    }

    function parseListDOM() {
        let rawList = document.getElementById("sortable_list");
        return Array
            .from(rawList.childNodes)
            .filter(node => node.nodeType !== Node.TEXT_NODE)
            .map(item => parseItem(item));
    }

    function parseItem(item) {
        // parse ingredient items parts (aka. the current ingredient items DOM-subtree)
        return Array
            .from(item.childNodes)
            .filter(node => node.nodeType !== Node.TEXT_NODE && node.nodeName.toLowerCase() === 'span')
            .map(part => parsePart(part))
            .reduce((parsedItemAccumulator, part) => {
                if (part) {
                    parsedItemAccumulator[part.identifier] = part.text;
                    return parsedItemAccumulator;
                }
            }, {});
    }

    function parsePart(part) {
        if (!part.classList || part.classList.length !== 1) {
            throw `An ingredients components (${identifiers}) couldn't be parsed. Identifying class missing.`;
        }
        const identifierClassParts = part.classList[0].split('-');
        if (identifierClassParts.length !== 3 || !part.classList[0].startsWith('cbtb-ingredient-')) {
            throw `An ingredients components (${identifiers}) couldn't be parsed. Identifying class invalid.`;
        }
        const identifier = part.classList[0].split('-')[2];
        if (identifiers.includes(identifier)) {
            return {identifier: identifier, text: part.innerText};
        }
        return null;
    }

    function store(parsedList) {
        const jsonList = JSON.stringify(parsedList);
        if(loggingEnabled) {
            console.log("Storing: ", jsonList);
            console.table(parsedList);
        }
        $jsonStore.val(jsonList);
        save(jsonList);
        return jsonList;
    }

    function save(jsonList) {
        $saveNotice.toggle(true);
        const postId = wp.data.select("core/editor").getCurrentPostId();
        fetch(`${apiRoot}cbtb-recipe/v1/recipe/${postId}/ingredients`, {
                method: 'PUT',
                body: jsonList,
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': nonce
                }
        }).then(response => {
            if(loggingEnabled) console.log(response);
            $saveNotice.toggle(false);
        });
    }

    function add(ingredient) {
        $sortableList
            .append(produceIngredientHtml(ingredient))
            .sortable("refresh");

        saveState();
    }

    function removeListItem(e) {
        // ignore clicks on container
        if ((e.target.nodeName.toLowerCase() === 'div' && e.target.classList.contains('cbtb-remove-button'))
            || e.target.nodeName.toLowerCase() === 'span' && e.target.classList.contains('dashicons-dismiss')) {
            e.target.closest('li').remove();
            saveState();
        }
    }

    function removeBeingSortedStyles(event, ui) {
        ui.item.removeClass("cbtb-dragged");
    }

    function styleItemBeingSorted(event, ui) {
        ui.item.addClass("cbtb-dragged");
    }

    function stripHTML(text) {
        const doc = new DOMParser().parseFromString(text, 'text/html');
        return doc.body.textContent || "";
    }
});