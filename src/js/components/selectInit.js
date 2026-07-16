const selectInit = () => {
	// Gravity Forms selects: toggle the floating-label state on the wrapping field.
	// Named function (not arrow) so `this` is the <select> element.
	$('body').on('blur change', '.ginput_container>select', function toggleActiveMod() {
		if ($(this).val()) {
			$(this).parents('.gfield:not(fieldset)').addClass('active_mod');
		} else {
			$(this).parents('.gfield:not(fieldset)').removeClass('active_mod');
		}
	});
};

export default selectInit;
