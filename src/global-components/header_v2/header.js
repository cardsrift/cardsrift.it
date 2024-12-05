import {
	GLOBAL_VARS,
} from '../utils/constants';
import {
	getWindowSize,
} from '../utils/index';

const Header = () => {
	const hamburgerMenu = document.querySelector('.hamburgerMenu');
	const mainMenu = document.querySelector('.mainMenu');
	const nav = document.querySelector('.mainMenu nav');
	const body = document.querySelector('body');
	const menuItemWChildSecondLevel = document.querySelectorAll('.secondLevel .menuItemWChild');
	const menuItemWChild = document.querySelectorAll('.menuItemWChild');
	const firstLevelMenu = document.querySelectorAll('.firstLevelMenu');
	const whiteLogo = document.querySelector('.whiteLogo');
	const searchButton = document.querySelector('.searchButton');
	const wrapperHeaderOpen = document.querySelector('.wrapperHeaderOpen');

	const {
		lg,
	} = GLOBAL_VARS;
	const {
		windowWidth,
	} = getWindowSize();

	let lastScrollTop = 0;
	const delta = 15;

	window.addEventListener('scroll', () => {
		const st = window.pageYOffset || document.documentElement.scrollTop;
		const header = document.querySelector('header');
		const bodyTopPosition = body.getBoundingClientRect().top;

		if (Math.abs(lastScrollTop - st) <= delta) return;

		if (st > lastScrollTop && lastScrollTop > 0) {
			header.classList.add('header_scrolled');
			firstLevelMenu.forEach(item => item.classList.remove('itemActive'));
			body.classList.remove('headerMenuOpened');
		} else {
			header.classList.remove('header_scrolled');
			if (st < 200) {
				body.classList.remove('headerMenuOpened');
				menuItemWChild.forEach(el => el.classList.remove('itemActive'));
			} else {
				body.classList.add('headerMenuOpened');
			}
		}
		lastScrollTop = st;
	});

	// mobile menu
	hamburgerMenu.addEventListener('click', () => {
		mainMenu.classList.toggle('menuOpened');
		hamburgerMenu.classList.toggle('hamburgerOpen');
		body.classList.toggle('overflow-y-hidden');
		searchButton.classList.toggle('hidden');
		whiteLogo.classList.toggle('hidden');
		wrapperHeaderOpen.classList.toggle('absolute');
	});

	// Primo livello: rimane al click, anche su desktop
	menuItemWChild.forEach((element) => {
		const a = element.querySelector('a');
		a.addEventListener('click', (e) => {
			e.preventDefault();
			element.classList.toggle('itemActive');
			body.classList.add('headerMenuOpened');
			nav.classList.add('slidedNav');

			// Chiudi se si clicca fuori dal menu
			document.addEventListener('click', (event) => {
				if (!element.contains(event.target) && windowWidth > lg) {
					element.classList.remove('itemActive');
				}
			});
		});

		const backToFirstLevel = element.querySelector('.backToFirstLevel');
		backToFirstLevel?.addEventListener('click', () => {
			nav.classList.remove('slidedNav');
			setTimeout(() => {
				menuItemWChild.forEach((el) => {
					el.classList.remove('itemActive');
				});
			}, 300);
		});
	});

	// Secondo livello: apri al passaggio del mouse solo per larghezze > lg
	if (windowWidth > lg) {
		menuItemWChildSecondLevel.forEach(element => {
			element.addEventListener('mouseenter', () => {
				element.classList.add('itemActive');
			});

			element.addEventListener('mouseleave', () => {
				element.classList.remove('itemActive');
			});
		});
	} else {
		menuItemWChildSecondLevel.forEach((element) => {
			const plusMenu = element.querySelector('.plusMenu');
			const a = element.querySelector('a');
			a.addEventListener('click', (e) => {
				plusMenu.classList.toggle('openAccordion');
			});
		});
	}

	// Popola automaticamente src dell'immagine del menu al mouse hover
	const secondLevelMenuContainer = document.querySelector('.secondLevel'); // Seleziona l'intero container del secondo livello
	const menuItems = document.querySelectorAll('.secondLevel .menuItem, .secondLevel .menuItemWChild');
	const dynamicImage = document.getElementById('dynamicImage');

	// Mostra l'immagine al passaggio del mouse su una voce del secondo livello
	menuItems.forEach(item => {
		item.addEventListener('mouseenter', () => {
			const imgSrc = item.getAttribute('data-img');
			if (imgSrc) {
				dynamicImage.classList.remove('fade-in');
				setTimeout(() => {
					dynamicImage.setAttribute('src', imgSrc);
					dynamicImage.classList.add('fade-in');
				}, 100);
			}
		});
	});

	// Rimuovi l'immagine quando esci dall'intero menu di secondo livello
	secondLevelMenuContainer?.addEventListener('mouseleave', () => {
		dynamicImage.classList.remove('fade-in');
		setTimeout(() => {
			dynamicImage.setAttribute('src', ''); // Rimuovi l'immagine
		}, 100);
	});
};

export default Header;
