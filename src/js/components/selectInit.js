const selectInit = () => {
	// console.log('ciao da select init');
	jQuery(document).ready(() => {
		// jQuery('.gfield_select').select2();
	});

	$('body').on('blur', '.ginput_container>select', () => {
		if (!$(this).val()) {
			$(this).parents('.gfield:not(fieldset)').removeClass('active_mod');
		}
	});
	$('body').on('change', '.ginput_container>select', () => {
		if (!$(this).val()) {
			$(this).parents('.gfield:not(fieldset)').removeClass('active_mod');
		} else {
			$(this).parents('.gfield:not(fieldset)').addClass('active_mod');
		}

		if (!$(this).val() && $(this).hasClass('mod_active')) {
			$(this).parents('.gfield:not(fieldset)').removeClass('active_mod');
		}
		if ($(this).val()) {
			$(this).parents('.gfield:not(fieldset)').addClass('active_mod');
		}
	});
};

export default selectInit;
