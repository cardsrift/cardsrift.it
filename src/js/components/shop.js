import $ from 'jquery';

/**
 * SHOP — comportamenti del flusso d'acquisto.
 *
 * Il markup di WooCommerce viene sostituito in AJAX a ogni aggiornamento
 * (cart.js sostituisce `.woocommerce-cart-form` e `.cart_totals`, add-to-cart.js
 * sostituisce i frammenti del drawer), quindi qui si usa SOLO delega di eventi su
 * `document`: agganciarsi ai nodi significherebbe perdere i listener al primo
 * ricalcolo.
 */

const QTY_DEBOUNCE = 650;
const DONE_MS = 1600;

/* =========================================================================
 * 1 · STEPPER QUANTITÀ (carrello)
 * ========================================================================= */

const qtyBounds = ($input) => {
	const max = parseInt($input.attr('max'), 10);
	return {
		value: parseInt($input.val(), 10) || 0,
		min: parseInt($input.attr('min'), 10) || 0,
		max: Number.isNaN(max) ? Infinity : max,
	};
};

/**
 * Spegne − e + quando si è al limite: la disponibilità reale è un vincolo,
 * non un errore da scoprire dopo aver cliccato (le singole sono spesso pezzi unici).
 */
const syncQtyButtons = () => {
	$('.cr-qty').each((i, el) => {
		const $qty = $(el);
		const $input = $qty.find('input.qty');
		if (!$input.length) return;
		const { value, min, max } = qtyBounds($input);
		$qty.find('[data-cr-qty="-"]').prop('disabled', value <= min);
		$qty.find('[data-cr-qty="+"]').prop('disabled', value >= max);
	});
};

/**
 * Tasti − / + dello stepper. Vale OVUNQUE ci sia un `.cr-qty`: pagina carrello e
 * drawer. ⚠️ Stava dentro l'inizializzazione del solo carrello, e nel drawer i tasti
 * non facevano nulla (il campo si poteva solo digitare).
 */
const qtyStepper = () => {
	$(document).on('click', '.cr-qty__btn', function stepQty() {
		const $btn = $(this);
		const $input = $btn.closest('.cr-qty').find('input.qty');
		if (!$input.length) return;

		const { value, min, max } = qtyBounds($input);
		const next = Math.min(Math.max(value + ($btn.attr('data-cr-qty') === '+' ? 1 : -1), min), max);
		if (next === value) return;

		$input.val(next).trigger('change');
	});
};

const cartQuantity = () => {
	// Il carrello si aggiorna da solo: "Aggiorna carrello" è un passaggio che nessuno
	// vuole fare. Si simula il click sul pulsante (nascosto via CSS) invece di lanciare
	// l'evento `wc_update_cart`: quell'evento serializza il form SENZA il parametro
	// `update_cart`, che è proprio quello che il gestore di WooCommerce pretende per
	// salvare le quantità — le nuove quantità verrebbero perse in silenzio.
	let timer = null;
	$(document).on('change input', '.woocommerce-cart-form input.qty', () => {
		syncQtyButtons();
		clearTimeout(timer);
		timer = setTimeout(() => {
			$('.woocommerce-cart-form :input[name="update_cart"]')
				.prop('disabled', false)
				.trigger('click');
		}, QTY_DEBOUNCE);
	});

	$(document.body).on('updated_wc_div updated_cart_totals updated_checkout', syncQtyButtons);
	syncQtyButtons();
};

/* =========================================================================
 * 2 · RIEPILOGO DEL CHECKOUT (ripiegato su mobile)
 * ========================================================================= */

/**
 * Aperto su desktop (colonna sticky), ripiegato su mobile, dove tenerlo aperto
 * significherebbe far scorrere tutto l'ordine prima del primo campo. Nel markup
 * è `open`: senza JS resta visibile, che è il ripiego giusto.
 */
const checkoutSummary = () => {
	const box = document.querySelector('[data-cr-summary]');
	if (!box) return;
	const desktop = window.matchMedia('(min-width: 1024px)');
	const apply = () => {
		if (desktop.matches) box.open = true;
		else if (!box.dataset.crTouched) box.open = false;
	};
	box.addEventListener('toggle', () => {
		if (!desktop.matches) box.dataset.crTouched = '1';
	});
	desktop.addEventListener('change', apply);
	apply();
};

/* =========================================================================
 * 3 · DRAWER DEL CARRELLO
 * ========================================================================= */

const FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]), select, textarea, [tabindex]:not([tabindex="-1"])';

const cartDrawer = () => {
	const root = document.getElementById('cr-cart-drawer');
	if (!root) return null;

	const panel = root.querySelector('.cr-drawer__panel');
	const title = root.querySelector('#cr-drawer-title');
	const defaultTitle = title ? title.textContent : '';
	const addedTitle = root.getAttribute('data-cr-added-title') || defaultTitle;
	const errorTitle = root.getAttribute('data-cr-error-title') || defaultTitle;
	const alertBox = root.querySelector('.cr-drawer__alert');
	let lastFocus = null;
	let closeTimer = null;

	const visible = () => [...panel.querySelectorAll(FOCUSABLE)].filter((el) => el.offsetParent !== null);

	/** Mostra (o pulisce) l'avviso in cima al pannello. */
	const alert = (html) => {
		if (!alertBox) return;
		alertBox.innerHTML = html || '';
		alertBox.hidden = !html;
	};

	const open = (heading) => {
		clearTimeout(closeTimer);
		lastFocus = document.activeElement;
		if (title) title.textContent = heading || defaultTitle;
		root.hidden = false;
		document.documentElement.classList.add('cr-noscroll');
		// doppio rAF: il pannello deve essere in pagina PRIMA che parta la transizione
		window.requestAnimationFrame(() => window.requestAnimationFrame(() => root.classList.add('is-open')));
		const first = visible()[0];
		if (first) first.focus();
	};

	const close = () => {
		if (root.hidden) return;
		root.classList.remove('is-open');
		document.documentElement.classList.remove('cr-noscroll');
		// il timer copre anche prefers-reduced-motion, dove transitionend non arriva
		closeTimer = setTimeout(() => { root.hidden = true; }, 340);
		if (lastFocus && lastFocus.focus) lastFocus.focus();
	};

	// chiusura: scrim, X, "continua a sfogliare" — delegata, perché il contenuto
	// viene sostituito a ogni aggiornamento dei frammenti
	root.addEventListener('click', (e) => {
		if (e.target.closest('[data-cr-close]')) {
			// i link (es. "Continua a sfogliare" da carrello vuoto) devono navigare
			if (!e.target.closest('a[href]')) e.preventDefault();
			close();
		}
	});

	// Esc chiude · Tab resta dentro il pannello finché è aperto
	document.addEventListener('keydown', (e) => {
		if (root.hidden) return;
		if (e.key === 'Escape') {
			close();
			return;
		}
		if (e.key !== 'Tab') return;

		const items = visible();
		if (!items.length) return;
		const first = items[0];
		const last = items[items.length - 1];
		if (e.shiftKey && document.activeElement === first) {
			e.preventDefault();
			last.focus();
		} else if (!e.shiftKey && document.activeElement === last) {
			e.preventDefault();
			first.focus();
		}
	});

	// l'icona in header apre il drawer invece di portare via dalla pagina
	$(document).on('click', '[data-cr-cart-toggle]', (e) => {
		e.preventDefault();
		open();
	});

	return {
		open,
		close,
		alert,
		root,
		addedTitle,
		errorTitle,
	};
};

/* =========================================================================
 * 4 · AGGIUNTA AL CARRELLO — stati del comando + apertura del drawer
 * ========================================================================= */

/**
 * Aggiorna la disponibilità mostrata dopo un'aggiunta riuscita, senza ricaricare.
 *
 * Le card sono renderizzate dal server: dopo un'aggiunta in AJAX resterebbero a
 * dire "2 disponibili" anche dopo che ne hai preso uno, fino al prossimo
 * caricamento. Qui si scala il contatore e, quando arriva a zero, il comando si
 * spegne — così non si clicca su qualcosa che il server rifiuterebbe.
 * La verità torna comunque dal server al prossimo caricamento di pagina.
 */
const updateStock = (root, productId, added) => {
	if (!root || !productId) return;
	const txt = {
		incart: root.getAttribute('data-cr-txt-incart') || '',
		last: root.getAttribute('data-cr-txt-last') || '',
		left: root.getAttribute('data-cr-txt-left') || '%d',
	};

	document.querySelectorAll(`[data-cr-stock-for="${productId}"]`).forEach((el) => {
		const before = parseInt(el.getAttribute('data-cr-left'), 10);
		if (Number.isNaN(before)) return; // scorte non gestite: niente da scalare
		const now = Math.max(0, before - added);

		el.classList.remove('cr-stock--ok', 'cr-stock--low', 'cr-stock--incart');
		if (now === 0) {
			el.removeAttribute('data-cr-left');
			el.classList.add('cr-stock--incart');
			el.textContent = txt.incart;
		} else {
			el.setAttribute('data-cr-left', String(now));
			el.classList.add(now <= 3 ? 'cr-stock--low' : 'cr-stock--ok');
			el.textContent = now === 1 ? txt.last : txt.left.replace('%d', String(now));
		}

		if (now === 0) {
			document.querySelectorAll(`[data-cr-add-for="${productId}"]`).forEach((btn) => {
				btn.classList.add('cr-add--full');
				btn.classList.remove('is-done', 'is-loading');
				btn.setAttribute('disabled', 'disabled');
			});
		}
	});
};

/* =========================================================================
 * 4b · QUANTITÀ DAL DRAWER
 * ========================================================================= */

/**
 * WooCommerce non espone un endpoint per cambiare la quantità del mini-carrello
 * (sul carrello vero passa da un POST con reload), quindi si usa il nostro
 * `wc-ajax=cr_set_qty`, che risponde con i frammenti standard.
 *
 * Il contenuto del drawer viene sostituito per intero a ogni risposta: prima si
 * memorizza la posizione della lista, altrimenti a ogni "+" si tornerebbe in cima.
 */
const drawerQuantity = (drawer) => {
	if (!drawer) return;

	const endpoint = () => {
		const base = window.wc_add_to_cart_params && window.wc_add_to_cart_params.wc_ajax_url;
		return base ? base.toString().replace('%%endpoint%%', 'cr_set_qty') : '';
	};

	let timer = null;
	let busy = false;

	const push = (input) => {
		const url = endpoint();
		const nonce = drawer.root.getAttribute('data-cr-qty-nonce');
		if (!url || !nonce || busy) return;

		const list = drawer.root.querySelector('.woocommerce-mini-cart');
		const scroll = list ? list.scrollTop : 0;

		busy = true;
		drawer.root.classList.add('is-busy');

		$.post(url, {
			nonce,
			cart_item_key: input.getAttribute('data-cart_item_key'),
			quantity: input.value,
		}).done((res) => {
			if (res && res.fragments) {
				$.each(res.fragments, (key, value) => { $(key).replaceWith(value); });
				$(document.body).trigger('wc_fragments_refreshed');
			}
			const newList = drawer.root.querySelector('.woocommerce-mini-cart');
			if (newList) newList.scrollTop = scroll;
			syncQtyButtons();
		}).always(() => {
			busy = false;
			drawer.root.classList.remove('is-busy');
		});
	};

	$(document).on('change input', '#cr-cart-drawer input.qty', function onQty() {
		const input = this;
		syncQtyButtons();
		clearTimeout(timer);
		timer = setTimeout(() => push(input), QTY_DEBOUNCE);
	});
};

const addToCart = (drawer) => {
	let pendingId = null;

	// Stato "in corso" appena si clicca: il riscontro deve arrivare sotto il dito,
	// non solo nel badge in header (che su mobile può essere fuori schermo).
	//
	// Il listener è in fase di CATTURA di proposito: WooCommerce ascolta su
	// `document.body`, che nel bubbling viene prima di `document`, quindi solo da qui
	// si può fermare un secondo click prima che parta una seconda richiesta. Senza
	// guardia, tre click rapidi fanno tre chiamate: la prima porta il motivo
	// dell'errore, le altre trovano la coda avvisi già svuotata e cancellerebbero
	// il messaggio appena mostrato.
	//
	// `pointer-events: none` non è un'alternativa: sulle card il click passerebbe
	// al link sottostante e finirebbe sulla scheda prodotto — esattamente ciò che
	// non deve succedere.
	document.addEventListener('click', (e) => {
		const el = e.target.closest('.cr-add.ajax_add_to_cart, .single_add_to_cart_button.ajax_add_to_cart');
		if (!el) return;

		if (el.classList.contains('is-loading')) {
			e.preventDefault();
			e.stopPropagation();
			return;
		}

		el.classList.add('is-loading');
		el.setAttribute('aria-busy', 'true');
		// rete di sicurezza: se la richiesta non parte o fallisce, non resta a girare
		setTimeout(() => {
			el.classList.remove('is-loading');
			el.removeAttribute('aria-busy');
		}, 8000);
	}, true);

	// la quantità scelta sulla scheda prodotto deve arrivare all'AJAX
	$(document).on('change input', 'form.cart input[name="quantity"]', function syncQty() {
		const btn = document.querySelector('.single_add_to_cart_button');
		if (btn) btn.setAttribute('data-quantity', String(parseInt(this.value, 10) || 1));
	});

	/**
	 * Aggiunta fallita (esaurito, quantità non disponibile, validazione di terze parti…).
	 * WooCommerce risponde solo con un flag e si aspetta di poter reindirizzare alla
	 * scheda prodotto: il redirect è già disinnescato lato PHP, qui recuperiamo il
	 * MOTIVO e lo mostriamo dov'è l'utente, senza portarlo via dalla pagina.
	 */
	$(document).on('ajaxSuccess', (event, xhr, settings) => {
		const url = (settings && settings.url) || '';
		if (url.indexOf('add_to_cart') === -1) return;
		const res = xhr.responseJSON;
		if (!res || !res.error) return;

		document.querySelectorAll('.is-loading').forEach((el) => {
			el.classList.remove('is-loading');
			el.removeAttribute('aria-busy');
		});
		if (!drawer) return;

		$.get(url.replace(/wc-ajax=[^&]+/, 'wc-ajax=cr_notices'))
			.done((data) => {
				// una risposta vuota (coda avvisi già consumata da una richiesta
				// parallela) non deve cancellare il motivo già a schermo
				if (data && data.html) drawer.alert(data.html);
				drawer.open(drawer.errorTitle);
			})
			.fail(() => drawer.open(drawer.errorTitle));
	});

	$(document.body).on('added_to_cart', (e, fragments, cartHash, $button) => {
		const btn = $button && $button.get(0);
		if (btn) {
			btn.classList.remove('is-loading');
			btn.removeAttribute('aria-busy');
			btn.classList.add('is-done');
			setTimeout(() => btn.classList.remove('is-done'), DONE_MS);
			pendingId = btn.getAttribute('data-product_id');
			if (drawer) {
				updateStock(drawer.root, pendingId, parseInt(btn.getAttribute('data-quantity'), 10) || 1);
			}
		}
		if (!drawer) return;
		drawer.alert('');

		// i frammenti vengono sostituiti dal gestore di WooCommerce: il tick lascia
		// che finisca prima che si vada a cercare la riga appena aggiunta
		setTimeout(() => {
			drawer.open(drawer.addedTitle);
			if (!pendingId) return;
			const row = drawer.root.querySelector(`[data-cr-item="${pendingId}"]`);
			if (row) {
				row.classList.add('is-new');
				row.scrollIntoView({
					block: 'nearest',
				});
			}
			pendingId = null;
		}, 0);
	});
};

/* =========================================================================
 * avvio
 * ========================================================================= */

const shop = () => {
	document.documentElement.classList.add('cr-js');

	if (document.querySelector('.woocommerce-cart-form')) cartQuantity();
	checkoutSummary();

	qtyStepper();
	const drawer = cartDrawer();
	drawerQuantity(drawer);
	addToCart(drawer);
	syncQtyButtons();
};

export default shop;
