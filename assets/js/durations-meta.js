$(function () {
    const $prepTime = $('#cbtb_durations_prep_time_field');
    const $cookTime = $('#cbtb_durations_cook_time_field');
    const $saveNotice = $('#cbtb-durations-save-notice');
    const $overallDurationBox = $('.cbtb-overall-duration');
    const $overallDuration = $('#cbtb-duration');
    const invalidInputClassToken = "cbtb-invalid-input";
    const initialValues = {
        prep: $prepTime.val(),
        cook: $cookTime.val()
    };

    if((!initialValues.prep && !initialValues.cook)
        || Number($overallDuration.text()) === 0) {
        $overallDurationBox.toggle(false);
    }

    $prepTime
        .add($cookTime)
        .blur(function (e) {
            validateServingsInput(e);
            toggleSaveNotice();
        })
        .on('keyup', function (e) {
            if (e.keyCode === 13) {
                validateServingsInput(e);
                toggleSaveNotice();
            }
        });

    wp.data.subscribe(function () {
        const isSaving = wp.data.select('core/editor').isSavingPost();
        const isAutoSaving = wp.data.select('core/editor').isAutosavingPost();

        if (isSaving && !isAutoSaving) {
            $saveNotice.toggle(false);
        }
    });

    function validateServingsInput(e) {
        const input = Number(e.target.value);
        if(Number.isNaN(input) || !Number.isInteger(input) || input < 0 || input > 9000 || !Number.isFinite(input)) {
            e.target.classList.add(invalidInputClassToken);
        } else {
            e.target.classList.remove(invalidInputClassToken);
        }
        displaySum();
    }

    function toggleSaveNotice() {
        if($prepTime.val() !== initialValues.prep || $cookTime.val() !== initialValues.cook) {
            $saveNotice.toggle(true);
        } else {
            $saveNotice.toggle(false);
        }
    }

    function displaySum() {
        const cook = $cookTime.hasClass(invalidInputClassToken) ? 0 : Number($cookTime.val());
        const prep = $prepTime.hasClass(invalidInputClassToken) ? 0 : Number($prepTime.val());
        const sum = cook + prep;
        if(sum) {
            $overallDurationBox.toggle(true);
            $overallDuration.text(sum);
        } else {
            $overallDurationBox.toggle(false);
        }

    }
});