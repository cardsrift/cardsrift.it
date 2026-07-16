import { GLOBAL_VARS } from '../utils/constants';

const Header = () => {
	const hamburgerMenu = document.querySelector('.hamburgerMenu');
	const mainMenu = document.querySelector('.mainMenu');
	const body = document.querySelector('body');
	const toggleMenu = document.querySelectorAll('.toggleMenu');
	const menuItemWChild = document.querySelectorAll('.menuItemWChild');

	const { lg } = GLOBAL_VARS;
	// Valutata a runtime negli handler: resta corretta anche dopo resize/rotazione
	const desktopMq = window.matchMedia(`(min-width: ${lg}px)`);

	// header scroll
	let lastScrollTop = 0;
	const delta = 15;

	window.addEventListener('scroll', () => {
		const st = window.pageYOffset || document.documentElement.scrollTop;

		if (Math.abs(lastScrollTop - st) <= delta) {
			return;
		}

		if (st > lastScrollTop && lastScrollTop > 0) {
			// downscroll code
			document.querySelector('header').classList.add('header_scrolled');
		} else {
			// upscroll code
			document.querySelector('header').classList.remove('header_scrolled');
		}

		lastScrollTop = st;
	});

	// mobile menu
	hamburgerMenu.addEventListener('click', () => {
		mainMenu.classList.toggle('menuOpen');
		hamburgerMenu.classList.toggle('hamburgerOpen');
		body.classList.toggle('overflow-y-hidden');
	});
	toggleMenu.forEach((element) => {
		element.addEventListener('click', () => {
			if (desktopMq.matches) return;
			element.classList.toggle('menuActive');
		});
	});

	menuItemWChild.forEach((element) => {
		// mouseenter/mouseleave (non bubblano): niente flicker attraversando il dropdown
		element.addEventListener('mouseenter', () => {
			if (!desktopMq.matches) return;
			mainMenu.classList.add('menuOpen');
			element.classList.add('itemActive');
		});
		element.addEventListener('mouseleave', () => {
			if (!desktopMq.matches) return;
			mainMenu.classList.remove('menuOpen');
			element.classList.remove('itemActive');
		});
	});
};
export default Header;
