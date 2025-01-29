import { GLOBAL_VARS } from '../utils/constants';
import { getWindowSize } from '../utils/index';

const Header = () => {
	const hamburgerMenu = document.querySelector('.hamburgerMenu');
	const mainMenu = document.querySelector('.mainMenu');
	const body = document.querySelector('body');
	const toggleMenu = document.querySelectorAll('.toggleMenu');
	const menuItemWChild = document.querySelectorAll('.menuItemWChild');

	const { lg } = GLOBAL_VARS;
	const { windowWidth } = getWindowSize();

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
		mainMenu.classList.toggle('hidden');
		hamburgerMenu.classList.toggle('hamburgerOpen');
		body.classList.toggle('overflow-y-hidden');
	});
	toggleMenu.forEach((element) => {
		if (windowWidth <= lg) {
			element.addEventListener('click', () => {
				element.classList.toggle('menuActive');
			});
		}
	});
	menuItemWChild.forEach((element) => {
		if (windowWidth > lg) {
			element.addEventListener('mouseover', () => {
				element.classList.add('itemActive');
			});
			element.addEventListener('mouseout', () => {
				element.classList.remove('itemActive');
			});
		}
	});
};
export default Header;
