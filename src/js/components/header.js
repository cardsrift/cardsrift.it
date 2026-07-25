/**
 * HEADER — barra a due righe.
 *
 * Il JS qui fa solo due cose: nasconde la barra quando si scorre in giù e apre
 * il menu su telefono. Le tendine del catalogo si aprono in CSS (group-hover /
 * group-focus-within) e l'accordion del menu mobile è un <details> nativo:
 * nessuno dei due passa da qui.
 */

const Header = () => {
	const header = document.querySelector('.cr-header');
	const toggle = document.querySelector('[data-cr-menu-toggle]');
	const menu = document.getElementById('cr-mobile-menu');

	// ---- la barra si ritrae scorrendo in giù, torna scorrendo in su ----
	if (header) {
		let lastScrollTop = 0;
		const delta = 15;

		window.addEventListener('scroll', () => {
			const st = window.pageYOffset || document.documentElement.scrollTop;
			if (Math.abs(lastScrollTop - st) <= delta) return;

			// col menu aperto la barra deve restare: è l'unico modo per richiuderlo
			const menuOpen = menu && menu.dataset.open === 'true';
			header.classList.toggle('header_scrolled', st > lastScrollTop && lastScrollTop > 0 && !menuOpen);

			lastScrollTop = st;
		}, { passive: true });
	}

	// ---- menu mobile ----
	if (!toggle || !menu) return;

	// il contenuto di pagina sotto al pannello: coperto agli occhi, va tolto anche
	// alla tastiera e allo screen reader, altrimenti col menu aperto si continua a
	// tabulare dentro a cose che non si vedono
	const page = document.querySelector('.wrapper');
	let lastFocus = null;

	const setMenu = (open) => {
		toggle.dataset.open = String(open);
		menu.dataset.open = String(open);
		toggle.setAttribute('aria-expanded', String(open));
		// stessa classe del drawer del carrello (shop.css): su <html>, perché su iOS
		// il blocco sul solo <body> non ferma il rimbalzo dello sfondo
		document.documentElement.classList.toggle('cr-noscroll', open);
		if (page) {
			if (open) page.setAttribute('inert', '');
			else page.removeAttribute('inert');
		}
		if (open) {
			lastFocus = document.activeElement;
			// col menu aperto la barra deve restare a schermo: è da lì che si richiude
			if (header) header.classList.remove('header_scrolled');
		} else if (lastFocus && lastFocus.focus) {
			lastFocus.focus();
		}
	};

	toggle.addEventListener('click', () => setMenu(menu.dataset.open !== 'true'));

	// Tab non esce dal pannello finché è aperto (il resto della pagina è già `inert`,
	// questo chiude il giro anche sulla barra dell'header, che inert non copre)
	const FOCUSABLE = 'a[href],button:not([disabled]),input:not([disabled]),select:not([disabled]),textarea:not([disabled]),summary,[tabindex]:not([tabindex="-1"])';
	document.addEventListener('keydown', (e) => {
		if (e.key !== 'Tab' || menu.dataset.open !== 'true') return;
		const items = [...menu.querySelectorAll(FOCUSABLE)].filter((el) => el.offsetParent !== null);
		if (!items.length) return;
		const first = items[0];
		const last = items[items.length - 1];
		if (e.shiftKey && (document.activeElement === first || document.activeElement === toggle)) {
			e.preventDefault();
			last.focus();
		} else if (!e.shiftKey && document.activeElement === last) {
			e.preventDefault();
			first.focus();
		}
	});

	// ---- accordion dei giochi: apertura e chiusura con l'altezza che scorre ----
	// <details> apre di scatto. Qui il click lo pilotiamo noi: `open` regge il rendering
	// del contenuto, la classe .is-open guida la transizione (grid-template-rows in
	// layout.css). In chiusura `open` va tolto SOLO a transizione finita, altrimenti il
	// contenuto sparisce prima di essersi richiuso.
	// Con prefers-reduced-motion non aggiungiamo .cr-js e <details> resta nativo.
	if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
		const accordions = menu.querySelectorAll('.cr-acc');

		if (accordions.length) {
			menu.classList.add('cr-js');

			accordions.forEach((acc) => {
				const summary = acc.querySelector('summary');
				const wrap = acc.querySelector('.cr-acc__wrap');
				if (!summary || !wrap) return;

				// il gioco attivo arriva già aperto dal PHP
				acc.classList.toggle('is-open', acc.open);

				summary.addEventListener('click', (e) => {
					e.preventDefault();

					if (!acc.open) {
						acc.open = true;
						// un frame di stacco: senza, il browser non ha uno stato da cui transire
						window.requestAnimationFrame(() => acc.classList.add('is-open'));
						return;
					}

					acc.classList.remove('is-open');
					const close = (ev) => {
						if (ev && ev.propertyName !== 'grid-template-rows') return;
						wrap.removeEventListener('transitionend', close);
						// riaperto nel frattempo: non richiuderlo
						if (!acc.classList.contains('is-open')) acc.open = false;
					};
					wrap.addEventListener('transitionend', close);
				});
			});
		}
	}

	// Esc chiude e riporta il fuoco sul comando che l'ha aperto
	document.addEventListener('keydown', (e) => {
		if (e.key === 'Escape' && menu.dataset.open === 'true') {
			setMenu(false);
			toggle.focus();
		}
	});

	// tornando a desktop il pannello non ha più senso: chiudilo e sblocca lo scroll
	window.matchMedia('(min-width: 1024px)').addEventListener('change', (e) => {
		if (e.matches) setMenu(false);
	});
};

export default Header;
