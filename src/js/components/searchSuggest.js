/**
 * TENDINA DI SUGGERIMENTI sotto il campo di ricerca (header, desktop e menu mobile).
 *
 * L'endpoint (`wc-ajax=cr_suggest`, includes/nav.php) risponde con HTML già pronto:
 * qui non si costruisce markup, si incolla e si gestisce la tastiera. È lo stesso
 * criterio dei frammenti del mini-carrello — il vestito resta in PHP.
 *
 * Schema combobox: l'input dichiara aria-expanded/aria-activedescendant, il pannello
 * è una listbox e ogni riga un option. Frecce per muoversi, Invio per andare,
 * Esc per chiudere.
 */

const MIN_CHARS = 2;
const DEBOUNCE = 180;

const initOne = (form) => {
	const endpoint = form.getAttribute('data-cr-suggest');
	const input = form.querySelector('input[name="s"]');
	const panel = form.querySelector('.cr-suggest');
	const gameField = form.querySelector('input[name="cr_g"]');
	if (!endpoint || !input || !panel) return;

	const cache = new Map();
	let timer = null;
	let controller = null;
	let options = [];
	let active = -1;

	const setActive = (i) => {
		if (options[active]) options[active].setAttribute('aria-selected', 'false');
		active = i;
		if (options[active]) {
			options[active].setAttribute('aria-selected', 'true');
			input.setAttribute('aria-activedescendant', options[active].id);
			options[active].scrollIntoView({ block: 'nearest' });
		} else {
			input.removeAttribute('aria-activedescendant');
		}
	};

	const close = () => {
		panel.hidden = true;
		input.setAttribute('aria-expanded', 'false');
		options = [];
		setActive(-1);
	};

	const open = (html) => {
		if (!html) { close(); return; }
		panel.innerHTML = html;
		panel.hidden = false;
		input.setAttribute('aria-expanded', 'true');
		options = [...panel.querySelectorAll('[role="option"]')];
		setActive(-1);
	};

	const ask = (term) => {
		if (cache.has(term)) { open(cache.get(term)); return; }

		// una richiesta alla volta: quella vecchia risponderebbe dopo, sovrascrivendo
		if (controller) controller.abort();
		controller = new AbortController();

		const url = new URL(endpoint, window.location.origin);
		url.searchParams.set('term', term);
		if (gameField && gameField.value) url.searchParams.set('cr_g', gameField.value);

		fetch(url.toString(), { signal: controller.signal, credentials: 'same-origin' })
			.then((r) => (r.ok ? r.json() : null))
			.then((data) => {
				if (!data) return;
				cache.set(term, data.html || '');
				// nel frattempo il testo può essere cambiato: non mostrare una risposta vecchia
				if (input.value.trim() === term) open(data.html || '');
			})
			.catch(() => { /* abort o rete giù: la tendina semplicemente non compare */ });
	};

	input.addEventListener('input', () => {
		const term = input.value.trim();
		window.clearTimeout(timer);
		if (term.length < MIN_CHARS) { close(); return; }
		timer = window.setTimeout(() => ask(term), DEBOUNCE);
	});

	input.addEventListener('keydown', (e) => {
		if (e.key === 'Escape') { close(); return; }

		if (panel.hidden || !options.length) return;

		if (e.key === 'ArrowDown') {
			e.preventDefault();
			setActive(active + 1 >= options.length ? 0 : active + 1);
		} else if (e.key === 'ArrowUp') {
			e.preventDefault();
			setActive(active - 1 < 0 ? options.length - 1 : active - 1);
		} else if (e.key === 'Enter' && options[active]) {
			// con una riga selezionata Invio ci va; senza, il form fa la ricerca piena
			e.preventDefault();
			window.location.href = options[active].href;
		}
	});

	input.addEventListener('focus', () => {
		if (input.value.trim().length >= MIN_CHARS && panel.innerHTML) open(panel.innerHTML);
	});

	// chiusura uscendo dal form (click fuori o Tab): relatedTarget dice dove si è finiti
	form.addEventListener('focusout', (e) => {
		if (!form.contains(e.relatedTarget)) close();
	});
	document.addEventListener('click', (e) => {
		if (!form.contains(e.target)) close();
	});
};

const searchSuggest = () => {
	document.querySelectorAll('[data-cr-suggest]').forEach(initOne);
};

export default searchSuggest;
