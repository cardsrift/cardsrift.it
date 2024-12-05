import jQuery from 'jquery';

const inputInit = () => {
	// Gravity Form Logic Input
	const inputListGravity = document.querySelectorAll('.gform-body .gfield input');
	const textAreaGravity = document.querySelectorAll('.gform-body .gfield textarea');
	const checkIsFilledGravity = (target) => {
		let inputValue = target.value;
		if (inputValue === '') {
			target.closest('.gfield').classList.remove('form_input--active_mod');
		} else {
			target.closest('.gfield').classList.add('form_input--active_mod');
		}
	};

	jQuery(document).on('gform_post_render', () => {
		// not working -> moved in footer.php script
	});

	inputListGravity.forEach(item => {
		checkIsFilledGravity(item);

		item.addEventListener('focus', (el) => {
			// console.log('input in');
			el.target.closest('.gfield').classList.add('form_input--active_mod');
		});

		item.addEventListener('blur', (el) => {
			// console.log('input out');
			checkIsFilledGravity(el.target);
		});
	});

	textAreaGravity.forEach(item => {
		checkIsFilledGravity(item);

		item.addEventListener('focus', (el) => {
			el.target.closest('.gfield').classList.add('form_input--active_mod');
		});

		item.addEventListener('blur', (el) => {
			checkIsFilledGravity(el.target);
		});
	});
};

export default inputInit;
