$(function () {
    const _$this = $('#cbtb_servings_field');

    _$this
        .blur(function () {
            validateServingsInput()
        })
        .on('keyup', function (e) {
            if (e.keyCode === 13) {
                validateServingsInput()
            }
        });

    function validateServingsInput() {
        const input = Number(_$this.val());
        if(Number.isNaN(input) || !Number.isInteger(input) || input < 1 || !Number.isFinite(input)) {
            _$this.addClass('cbtb-invalid-input');
            return false;
        }
        _$this.removeClass('cbtb-invalid-input');
        return true;
    }
});