<?php

/**
 * SHOP — integrazione WooCommerce del flusso d'acquisto (carrello → checkout → account).
 *
 * I template vivono in `woocommerce/` (override risolti da wc_locate_template); qui sta
 * la logica condivisa: chrome di sezione, passi del flusso, chip di condizione/lingua/foil
 * delle carte, stato ordine, obiettivo spedizione gratuita, e la messa a punto dei campi
 * e degli asset di WooCommerce.
 *
 * ⚠️ Volutamente NON dichiariamo add_theme_support('woocommerce'): cambierebbe il template
 * loader dell'archivio shop, che qui è gestito dal routing custom (includes/routing.php).
 * Gli override in `woocommerce/` funzionano comunque: passano da wc_locate_template().
 *
 * Documentazione: docs/design-system.md · docs/rework-fase-1.md
 */

defined('ABSPATH') || exit;

/* =====================================================================
 * 1 · ASSET — via il CSS di WooCommerce, il flusso è vestito dal design system
 * ===================================================================== */

/**
 * Fogli di stile di WooCommerce: non ne serve nessuno, il flusso è interamente
 * ridisegnato (shop.css). Le poche utility che il plugin dava per scontate
 * (screen-reader-text, overlay di caricamento) sono in shop.css.
 */
add_filter('woocommerce_enqueue_styles', '__return_empty_array');

/**
 * Segnala alle pagine che in fondo c'è una barra fissa (carrello con articoli, scheda
 * prodotto acquistabile), così il footer può riservarle spazio — vedi `.cr-has-stickybar`
 * in shop.css. Prima lo spazio lo metteva uno spaziatore dentro cart.php, cioè PRIMA del
 * footer: la barra finiva sopra le icone di pagamento e la riga Privacy/Cookie.
 */
add_filter('body_class', 'cr_body_stickybar_class');
function cr_body_stickybar_class($classes)
{
	if (is_admin() || !function_exists('is_cart')) {
		return $classes;
	}
	$cart_full = is_cart() && WC()->cart && !WC()->cart->is_empty();
	$buyable   = false;
	if (is_product()) {
		$p = wc_get_product(get_the_ID());
		// stessa condizione della barra in single-product.php: se non c'è nulla da
		// comprare (in arrivo, esaurito, già tutto nel carrello) la barra non esce
		$buyable = $p && $p->is_in_stock() && $p->is_purchasable()
			&& !get_field('data_uscita', $p->get_id())
			&& cr_stock_left($p) !== 0;
	}
	if ($cart_full || $buyable) {
		$classes[] = 'cr-has-stickybar';
	}
	return $classes;
}

/**
 * selectWoo/select2: i menu a tendina restano nativi — più leggeri, accessibili e
 * con il picker di sistema su mobile. `wc-country-select` resta caricato: senza
 * selectWoo salta solo l'abbellimento, mentre l'aggiornamento del campo Provincia
 * al cambio di Nazione continua a funzionare.
 */
add_action('wp_enqueue_scripts', 'cr_shop_dequeue_select2', 99);
function cr_shop_dequeue_select2()
{
	wp_dequeue_style('select2');
	wp_deregister_style('select2');
	wp_dequeue_script('selectWoo');
	wp_dequeue_script('select2');
}

/* =====================================================================
 * 2 · CHROME DI SEZIONE — apertura/chiusura condivise da carrello e checkout
 * ===================================================================== */

/**
 * Apre la sezione di una pagina transazionale: tema, container, testata con
 * eyebrow, titolo e indicatore di passo. Va chiusa con cr_shop_close_section().
 *
 * @param array $args { title, eyebrow?, step?: 'cart'|'checkout'|'done', lead?, theme? }
 */
function cr_shop_open_section($args = [])
{
	$a = wp_parse_args($args, [
		'title'   => '',
		'eyebrow' => '',
		'step'    => '',
		'lead'    => '',
		'theme'   => 'light',
		'width'   => 'tw-container',
	]);
?>
	<section class="cr-sec" data-th="<?= esc_attr($a['theme']); ?>">
		<div class="<?= esc_attr($a['width']); ?> tw-section">
			<div class="py-8 lg:py-12">

				<?php // Lo spazio sotto i passi appartiene ai passi, non a ciò che segue:
				// quando il titolo non c'è (carrello svuotato) il primo elemento è un
				// avviso, e senza questo margine finiva incollato all'indicatore. ?>
				<?php if ($a['step']) : ?>
					<div class="mb-7"><?php cr_flow_steps($a['step']); ?></div>
				<?php endif; ?>

				<?php if ($a['title']) : ?>
					<header class="mb-7 lg:mb-9">
						<?php if ($a['eyebrow']) : ?>
							<div class="cr-eyebrow mb-2.5"><?= esc_html($a['eyebrow']); ?></div>
						<?php endif; ?>
						<h1 class="font-metropolis font-bold !text-2xl lg:!text-4xl !leading-tight"><?= esc_html($a['title']); ?></h1>
						<?php if ($a['lead']) : ?>
							<p class="text-th-muted text-sm lg:text-base mt-3 max-w-[58ch]"><?= esc_html($a['lead']); ?></p>
						<?php endif; ?>
					</header>
				<?php endif; ?>
<?php
}

function cr_shop_close_section()
{
?>
			</div>
		</div>
	</section>
<?php
}

/**
 * Indicatore dei passi del flusso. Tre tappe, sempre le stesse: sapere dove si è
 * (e quanto manca) riduce l'ansia da checkout.
 */
function cr_flow_steps($current = 'cart')
{
	$steps = [
		'cart'     => __('Carrello', 'cardsrift'),
		'checkout' => __('Dati e pagamento', 'cardsrift'),
		'done'     => __('Conferma', 'cardsrift'),
	];
	$order = array_keys($steps);
	$pos   = array_search($current, $order, true);
	$pos   = ($pos === false) ? 0 : $pos;
?>
	<nav class="cr-steps" aria-label="<?php esc_attr_e('Avanzamento ordine', 'cardsrift'); ?>">
		<?php foreach ($order as $i => $key) :
			$state = ($i === $pos) ? ' cr-steps__i--on' : (($i < $pos) ? ' cr-steps__i--done' : '');
		?>
			<?php if ($i > 0) : ?><span class="cr-steps__sep" aria-hidden="true"></span><?php endif; ?>
			<span class="cr-steps__i<?= $state; ?>"<?= ($i === $pos) ? ' aria-current="step"' : ''; ?>>
				<span class="cr-steps__n" aria-hidden="true"><?= $i < $pos ? '✓' : ($i + 1); ?></span>
				<?= esc_html($steps[$key]); ?>
			</span>
		<?php endforeach; ?>
	</nav>
<?php
}

/**
 * Banda di rassicurazione a fondo pagina (tema scuro, come i "correlati" della PDP).
 * Le stesse tre promesse della scheda prodotto: la fiducia si ripete, non si spiega.
 */
function cr_shop_trust_band()
{
	$items = [
		[
			'title' => __('Controllate una a una', 'cardsrift'),
			'text'  => __('Ogni carta passa dalle nostre mani prima di partire: condizione verificata, niente sorprese.', 'cardsrift'),
			'path'  => 'M20 6L9 17l-5-5',
		],
		[
			'title' => __('Imballo protettivo', 'cardsrift'),
			'text'  => __('Bustina, toploader e cartoncino rigido. Le imballiamo come se restassero nella nostra collezione.', 'cardsrift'),
			'path'  => 'M21 8l-9-5-9 5v8l9 5 9-5V8zM3 8l9 5 9-5M12 13v8',
		],
		[
			'title' => __('Spedizione tracciata in 24/48h', 'cardsrift'),
			'text'  => sprintf(__('%s di valutazioni positive su Cardmarket: il negozio è nuovo, noi no.', 'cardsrift'), defined('CR_CM_POSITIVE') ? CR_CM_POSITIVE : '100%'),
			'path'  => 'M3 7h11v8H3zM14 10h4l3 3v2h-7zM7.5 19a1.5 1.5 0 100-3 1.5 1.5 0 000 3zM17.5 19a1.5 1.5 0 100-3 1.5 1.5 0 000 3z',
		],
	];
?>
	<section class="cr-sec cr-patt" data-th="dark">
		<div class="tw-container tw-section">
			<div class="py-12 lg:py-14 grid grid-cols-1 tb:grid-cols-3 gap-7 lg:gap-10">
				<?php foreach ($items as $it) : ?>
					<div class="flex flex-col gap-2.5">
						<svg class="w-6 h-6 stroke-th-acc fill-transparent" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="<?= esc_attr($it['path']); ?>" /></svg>
						<div class="font-metropolis font-semibold text-base text-th-ink"><?= esc_html($it['title']); ?></div>
						<p class="text-sm leading-relaxed text-th-muted"><?= esc_html($it['text']); ?></p>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
<?php
}

/* =====================================================================
 * 3 · IDENTITÀ DELLA CARTA — condizione, lingua, foil, espansione
 * Per una singola questi attributi SONO il prodotto: senza, il cliente non
 * può verificare cosa sta comprando. Vanno mostrati ovunque compaia la riga.
 * ===================================================================== */

/**
 * Chip identificativi di un prodotto in riga (carrello, riepilogo, ordine).
 * Ritorna un array di ['label' => ..., 'cond' => bool]. Funziona sia sui prodotti
 * semplici (attributi di prodotto) sia sulle variazioni (attributo selezionato).
 */
function cr_item_chips($product)
{
	if (!$product instanceof WC_Product) {
		return [];
	}
	$chips = [];
	$cond  = $product->get_attribute('condizione');
	if ($cond) {
		$chips[] = ['label' => strtoupper(trim(explode(',', $cond)[0])), 'cond' => true];
	}
	foreach (['lingua', 'foil'] as $attr) {
		$val = trim((string) $product->get_attribute($attr));
		// "Normale" è il default del foil: dirlo è rumore, non informazione
		if ($val === '' || ($attr === 'foil' && stripos($val, 'normale') !== false)) {
			continue;
		}
		$chips[] = ['label' => trim(explode(',', $val)[0]), 'cond' => false];
	}
	return $chips;
}

/** Stampa i chip di un prodotto (niente output se non ce ne sono). */
function cr_item_chips_html($product)
{
	$chips = cr_item_chips($product);
	if (!$chips) {
		return;
	}
	echo '<span class="flex flex-wrap gap-1 mt-1">';
	foreach ($chips as $c) {
		printf(
			'<span class="cr-cchip%s">%s</span>',
			$c['cond'] ? ' cr-cchip--cond' : '',
			esc_html($c['label'])
		);
	}
	echo '</span>';
}

/**
 * Gli stessi attributi finiscono come meta della riga d'ordine: così la conferma,
 * l'email e lo storico dicono esattamente QUALE copia della carta è stata venduta,
 * anche se in futuro il prodotto cambia o sparisce dal catalogo.
 */
add_action('woocommerce_checkout_create_order_line_item', 'cr_order_item_card_meta', 10, 3);
function cr_order_item_card_meta($item, $cart_item_key, $values)
{
	$product = $values['data'] ?? null;
	if (!$product instanceof WC_Product) {
		return;
	}
	$map = [
		'condizione' => __('Condizione', 'cardsrift'),
		'lingua'     => __('Lingua', 'cardsrift'),
		'foil'       => __('Foil', 'cardsrift'),
		'espansione' => __('Espansione', 'cardsrift'),
	];
	// L'espansione identifica una SINGOLA ("Pichu — Expedition Base Set"); su un sealed
	// ripeterebbe il nome del prodotto ("Avatar: The Last Airbender Play Booster"). La
	// condizione è ciò che distingue le due cose: ce l'hanno solo le singole.
	$is_single = trim((string) $product->get_attribute('condizione')) !== '';

	foreach ($map as $attr => $label) {
		$val = trim((string) $product->get_attribute($attr));
		if ($val === '' || ($attr === 'foil' && stripos($val, 'normale') !== false)) {
			continue;
		}
		if ($attr === 'espansione' && !$is_single) {
			continue;
		}
		$item->add_meta_data($label, trim(explode(',', $val)[0]), true);
	}
}

/**
 * Chip di una riga d'ordine: prima dai meta salvati all'acquisto (verità storica),
 * con ripiego sul prodotto per gli ordini fatti prima di questa versione.
 */
function cr_order_item_chips_html($item)
{
	$cond   = $item->get_meta(__('Condizione', 'cardsrift'));
	$lingua = $item->get_meta(__('Lingua', 'cardsrift'));
	$foil   = $item->get_meta(__('Foil', 'cardsrift'));

	if (!$cond && !$lingua && !$foil) {
		$product = $item->get_product();
		cr_item_chips_html($product);
		return;
	}

	echo '<span class="flex flex-wrap gap-1 mt-1">';
	if ($cond) {
		printf('<span class="cr-cchip cr-cchip--cond">%s</span>', esc_html(strtoupper($cond)));
	}
	foreach ([$lingua, $foil] as $val) {
		if ($val) {
			printf('<span class="cr-cchip">%s</span>', esc_html($val));
		}
	}
	echo '</span>';
}

/**
 * Gli attributi mostrati come chip non vanno ripetuti dall'elenco generico di
 * WooCommerce (wc_get_formatted_cart_item_data): resta solo l'eventuale dato
 * aggiunto da terze parti.
 */
add_filter('woocommerce_get_item_data', 'cr_hide_duplicated_item_data', 20, 2);
function cr_hide_duplicated_item_data($item_data, $cart_item)
{
	$hidden = ['condizione', 'lingua', 'foil', 'espansione'];
	return array_values(array_filter($item_data, function ($d) use ($hidden) {
		$key = strtolower(wp_strip_all_tags($d['key'] ?? ''));
		return !in_array($key, $hidden, true);
	}));
}

/**
 * Toglie dai meta "stampabili" della riga d'ordine gli attributi che i nostri
 * template mostrano già come chip. Va applicata SOLO dove serve (scoped
 * add_filter/remove_filter nei template): nelle email e in wp-admin quei meta
 * devono restare, sono la descrizione esatta di cosa è stato venduto.
 */
function cr_strip_card_meta($formatted_meta)
{
	$hidden = [__('Condizione', 'cardsrift'), __('Lingua', 'cardsrift'), __('Foil', 'cardsrift')];
	foreach ($formatted_meta as $id => $meta) {
		if (in_array(wp_strip_all_tags($meta->display_key), $hidden, true)) {
			unset($formatted_meta[$id]);
		}
	}
	return $formatted_meta;
}

/* =====================================================================
 * 4 · STATO ORDINE
 * ===================================================================== */

/** Chip di stato ordine: colore semantico (in corso / concluso / fermo). */
function cr_order_status_chip($order)
{
	if (!$order instanceof WC_Order) {
		return;
	}
	$status = $order->get_status();
	$mod    = '';
	if (in_array($status, ['completed'], true)) {
		$mod = ' cr-ostat--ok';
	} elseif (in_array($status, ['on-hold', 'pending'], true)) {
		$mod = ' cr-ostat--wait';
	} elseif (in_array($status, ['cancelled', 'failed', 'refunded'], true)) {
		$mod = ' cr-ostat--dead';
	}
	printf('<span class="cr-ostat%s">%s</span>', esc_attr($mod), esc_html(wc_get_order_status_name($status)));
}

/**
 * Frase di stato in linguaggio umano: cosa sta succedendo all'ordine, adesso.
 * Gli stati WooCommerce ("in lavorazione") non dicono nulla al cliente.
 */
function cr_order_status_line($order)
{
	switch ($order->get_status()) {
		case 'pending':
			return __('Aspettiamo la conferma del pagamento.', 'cardsrift');
		case 'on-hold':
			return __('Ordine registrato: parte appena riceviamo il pagamento.', 'cardsrift');
		case 'processing':
			return __('Stiamo preparando il pacco: parte entro 24/48h con spedizione tracciata.', 'cardsrift');
		case 'completed':
			return __('Ordine spedito. Grazie: sei parte del portale.', 'cardsrift');
		case 'cancelled':
			return __('Ordine annullato.', 'cardsrift');
		case 'refunded':
			return __('Ordine rimborsato.', 'cardsrift');
		case 'failed':
			return __('Il pagamento non è andato a buon fine.', 'cardsrift');
	}
	return '';
}

/* =====================================================================
 * 5 · SPEDIZIONE GRATUITA — barra di avanzamento, se e solo se la soglia esiste
 * ===================================================================== */

/**
 * Obiettivo "spedizione gratuita" per il carrello corrente.
 * Ritorna null se nessun metodo free_shipping attivo con importo minimo:
 * il componente si accende da solo il giorno in cui la soglia viene impostata
 * in WooCommerce → Spedizione, senza toccare il tema.
 *
 * @return array|null { min, remaining, pct, reached }
 */
function cr_free_shipping_goal()
{
	if (!function_exists('WC') || !WC()->cart || !WC()->cart->needs_shipping()) {
		return null;
	}

	$min = null;
	foreach (WC()->shipping()->get_packages() as $package) {
		$zone = function_exists('wc_get_shipping_zone') ? wc_get_shipping_zone($package) : null;
		if (!$zone) {
			continue;
		}
		foreach ($zone->get_shipping_methods(true) as $method) {
			if ($method->id !== 'free_shipping') {
				continue;
			}
			if (!in_array($method->get_option('requires'), ['min_amount', 'either', 'both'], true)) {
				continue;
			}
			$amount = (float) $method->get_option('min_amount');
			if ($amount > 0 && ($min === null || $amount < $min)) {
				$min = $amount;
			}
		}
	}
	if ($min === null) {
		return null;
	}

	// stessa base di calcolo usata da WC_Shipping_Free_Shipping::is_available()
	$total = (float) WC()->cart->get_displayed_subtotal();
	if (WC()->cart->display_prices_including_tax()) {
		$total = round($total - (WC()->cart->get_discount_total() + WC()->cart->get_discount_tax()), wc_get_price_decimals());
	} else {
		$total = round($total - WC()->cart->get_discount_total(), wc_get_price_decimals());
	}

	return [
		'min'       => $min,
		'remaining' => max(0, $min - $total),
		'pct'       => (int) min(100, round(($total / $min) * 100)),
		'reached'   => $total >= $min,
	];
}

/** Blocco visuale dell'obiettivo spedizione gratuita (niente output se non configurato). */
function cr_free_shipping_bar()
{
	$goal = cr_free_shipping_goal();
	if (!$goal) {
		return;
	}
?>
	<div class="rounded-xl px-4 py-3.5 bg-th-accsoft">
		<p class="text-sm text-th-ink">
			<?php if ($goal['reached']) : ?>
				<strong class="font-metropolis font-semibold"><?php esc_html_e('Spedizione gratuita sbloccata.', 'cardsrift'); ?></strong>
			<?php else : ?>
				<?php printf(
					wp_kses(__('Ti mancano <strong class="font-metropolis font-semibold text-th-acc">%s</strong> alla spedizione gratuita.', 'cardsrift'), ['strong' => ['class' => []]]),
					wp_kses_post(wc_price($goal['remaining']))
				); ?>
			<?php endif; ?>
		</p>
		<?php // senza aria-label lo screen reader annuncia solo "58%, barra di avanzamento" ?>
		<div class="cr-shipbar" role="progressbar" aria-label="<?php esc_attr_e('Avanzamento verso la spedizione gratuita', 'cardsrift'); ?>" aria-valuenow="<?= esc_attr($goal['pct']); ?>" aria-valuemin="0" aria-valuemax="100">
			<div class="cr-shipbar__fill" style="width:<?= esc_attr($goal['pct']); ?>%"></div>
		</div>
	</div>
<?php
}

/* =====================================================================
 * 6 · CARRELLO — comportamento
 * ===================================================================== */

/**
 * I totali li stampiamo noi nella colonna riepilogo (cart.php): via dal
 * collaterals, che resta libero per i cross-sell e per le estensioni.
 */
add_action('init', 'cr_shop_rehook');
function cr_shop_rehook()
{
	remove_action('woocommerce_cart_collaterals', 'woocommerce_cart_totals', 10);
	// il carrello vuoto ha una sua pagina (cart-empty.php): niente notice di sistema
	remove_action('woocommerce_cart_is_empty', 'wc_empty_cart_message', 10);
	// il pagamento lo stampa form-checkout.php nella colonna sinistra, in coda al percorso
	remove_action('woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20);

	// I messaggi del checkout uscivano PRIMA del template, quindi fuori dalla nostra
	// sezione (banda bianca sopra la pagina). Spostandoli su `woocommerce_before_checkout_form`
	// finiscono dentro il layout. Sul ramo "carrello con errori" li stampa cart-errors.php.
	remove_action('woocommerce_before_checkout_form_cart_notices', 'woocommerce_output_all_notices', 10);
}

/**
 * "Aggiunto al carrello": sulla pagina del carrello il pulsante "Visualizza carrello"
 * rimanda a dove sei già. Resta il messaggio, sparisce il vicolo cieco.
 */
add_filter('wc_add_to_cart_message_html', 'cr_add_to_cart_message', 10, 1);
function cr_add_to_cart_message($message)
{
	if (function_exists('is_cart') && (is_cart() || is_checkout())) {
		return preg_replace('~<a [^>]*class="[^"]*button[^"]*"[^>]*>.*?</a>~is', '', $message);
	}
	return $message;
}

/** Cross-sell: una riga da 4, come le griglie del sito. */
add_filter('woocommerce_cross_sells_total', function () {
	return 4;
});
add_filter('woocommerce_cross_sells_columns', function () {
	return 4;
});

/* =====================================================================
 * 6b · DRAWER DEL CARRELLO — il recap che si apre dopo l'aggiunta
 * ===================================================================== */

/**
 * Il contenuto del drawer è un frammento AJAX di WooCommerce: a ogni aggiunta o
 * rimozione il plugin lo rigenera e lo sostituisce da solo, senza codice nostro
 * per il refresh. È lo stesso meccanismo del widget carrello di WooCommerce.
 */
add_filter('woocommerce_add_to_cart_fragments', 'cr_minicart_fragment');
function cr_minicart_fragment($fragments)
{
	ob_start();
	echo '<div class="cr-drawer__content widget_shopping_cart_content">';
	woocommerce_mini_cart();
	echo '</div>';
	$fragments['div.cr-drawer__content'] = ob_get_clean();
	return $fragments;
}

/**
 * Aggiunta fallita → NIENTE redirect alla scheda prodotto.
 *
 * Di suo WooCommerce, quando l'aggiunta in AJAX non va a buon fine, risponde
 * `{error:true, product_url:…}` e add-to-cart.js fa `window.location = product_url`:
 * chi stava sfogliando una griglia si ritrova catapultato sulla pagina del prodotto,
 * perdendo il posto. Svuotando l'URL la condizione del redirect diventa falsa;
 * l'errore lo mostriamo dov'è l'utente, nel drawer (vedi components/shop.js).
 */
add_filter('woocommerce_cart_redirect_after_error', 'cr_no_redirect_on_add_error');
function cr_no_redirect_on_add_error()
{
	// ⚠️ Il motivo dell'errore vive negli avvisi di sessione, e WooCommerce la sessione
	// la persiste solo se c'è già un cookie. A carrello vuoto — il caso in cui l'errore
	// è più probabile — il messaggio andrebbe perso e l'utente vedrebbe un rifiuto muto.
	// Qui il cookie lo forziamo: costa nulla e succede solo quando qualcosa è andato storto.
	if (function_exists('WC') && WC()->session) {
		WC()->session->set_customer_session_cookie(true);
	}
	return '';
}

/**
 * Endpoint leggero che restituisce gli avvisi rimasti in sessione.
 * Serve al caso sopra: la risposta d'errore di WooCommerce contiene solo un flag,
 * non il motivo ("prodotto esaurito", "quantità non disponibile"…), e il motivo è
 * esattamente ciò che va detto. Una sola chiamata in più, e solo quando fallisce.
 */
add_action('wc_ajax_cr_notices', 'cr_ajax_notices');
add_action('wc_ajax_nopriv_cr_notices', 'cr_ajax_notices');
function cr_ajax_notices()
{
	$errors = function_exists('wc_get_notices') ? wc_get_notices('error') : [];

	if ($errors) {
		// Consegnati: si tolgono dalla coda, altrimenti ricomparirebbero al prossimo
		// caricamento. Si toccano SOLO gli errori: eventuali messaggi di altro tipo
		// restano in attesa di essere mostrati dove devono.
		$queue = wc_get_notices();
		unset($queue['error']);
		wc_set_notices($queue);
	}

	ob_start();
	if ($errors) {
		wc_get_template('notices/error.php', ['notices' => $errors]);
	}
	wp_send_json(['html' => ob_get_clean()]);
}

/**
 * Cambio quantità dal drawer.
 *
 * WooCommerce non ha un endpoint per questo: sul carrello vero la quantità passa da
 * un POST del form con reload. Qui serve senza ricaricare, quindi l'endpoint è nostro.
 * Restituisce i frammenti standard, così drawer e contatore in header si aggiornano
 * con lo stesso meccanismo di sempre.
 */
add_action('wc_ajax_cr_set_qty', 'cr_ajax_set_qty');
add_action('wc_ajax_nopriv_cr_set_qty', 'cr_ajax_set_qty');
function cr_ajax_set_qty()
{
	check_ajax_referer('cr-set-qty', 'nonce');

	$key = isset($_POST['cart_item_key']) ? wc_clean(wp_unslash($_POST['cart_item_key'])) : '';
	$qty = isset($_POST['quantity']) ? wc_stock_amount(wp_unslash($_POST['quantity'])) : 0;
	$cart = WC()->cart;
	$item = $key ? $cart->get_cart_item($key) : null;

	if (!$item) {
		wp_send_json(['error' => true]);
	}

	if ($qty <= 0) {
		$cart->remove_cart_item($key);
	} else {
		// Il limite lo rimette anche il server: l'attributo `max` del campo è una
		// comodità dell'interfaccia, non una garanzia (basta un DevTools aperto).
		$product = $item['data'];
		$max     = $product->is_sold_individually() ? 1 : null;
		if ($max === null && $product->managing_stock() && !$product->backorders_allowed()) {
			$max = $product->get_stock_quantity();
		}
		if ($max !== null && $qty > $max) {
			$qty = $max;
			wc_add_notice(sprintf(
				/* translators: 1: nome prodotto, 2: quantità disponibile */
				_n('Di %1$s abbiamo solo %2$d pezzo.', 'Di %1$s abbiamo solo %2$d pezzi.', $max, 'cardsrift'),
				$product->get_name(),
				$max
			), 'error');
		}
		$cart->set_quantity($key, $qty, true);
	}

	WC_AJAX::get_refreshed_fragments();
}

/**
 * Guscio del drawer, stampato una volta a fine pagina (footer.php).
 * Non serve dove il carrello è già la pagina: nel carrello e nel checkout.
 * Il contenuto è renderizzato lato server, quindi è corretto già al primo caricamento
 * e funziona anche prima che parta un qualsiasi AJAX.
 */
function cr_cart_drawer()
{
	if (!function_exists('WC') || !WC()->cart) {
		return;
	}
	if (is_cart() || is_checkout()) {
		return;
	}
?>
	<?php // il titolo cambia a seconda di come si apre: dall'icona, da un'aggiunta o da un errore ?>
	<div class="cr-drawer" id="cr-cart-drawer" data-th="light"
		data-cr-added-title="<?php esc_attr_e('Aggiunto al carrello', 'cardsrift'); ?>"
		data-cr-error-title="<?php esc_attr_e('Non l’abbiamo aggiunto', 'cardsrift'); ?>"
		<?php // testi per l'aggiornamento della disponibilità senza ricaricare (components/shop.js) ?>
		data-cr-txt-incart="<?php esc_attr_e('Tutto nel carrello', 'cardsrift'); ?>"
		data-cr-txt-one="<?php esc_attr_e('%d disponibile', 'cardsrift'); ?>"
		data-cr-txt-left="<?php esc_attr_e('%d disponibili', 'cardsrift'); ?>"
		data-cr-qty-nonce="<?= esc_attr(wp_create_nonce('cr-set-qty')); ?>"
		hidden>
		<div class="cr-drawer__scrim" data-cr-close></div>

		<aside class="cr-drawer__panel" role="dialog" aria-modal="true" aria-labelledby="cr-drawer-title">
			<header class="cr-drawer__head">
				<h2 class="font-metropolis font-semibold !text-lg m-0" id="cr-drawer-title"><?php esc_html_e('Il tuo carrello', 'cardsrift'); ?></h2>
				<button type="button" class="cr-drawer__x" data-cr-close aria-label="<?php esc_attr_e('Chiudi il carrello', 'cardsrift'); ?>">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12" /></svg>
				</button>
			</header>

			<?php // fuori da __content: i frammenti AJAX sostituiscono solo quello, l'avviso deve sopravvivere ?>
			<div class="cr-drawer__alert" hidden></div>

			<div class="cr-drawer__content widget_shopping_cart_content"><?php woocommerce_mini_cart(); ?></div>
		</aside>
	</div>
<?php
}

/* =====================================================================
 * 7 · CHECKOUT — campi, ordine, copy
 * ===================================================================== */

/**
 * Campi del checkout: etichette nostre, niente placeholder ridondanti (l'etichetta
 * resta sempre visibile — Baymard: i placeholder come etichetta fanno perdere il filo),
 * e priorità che rispettano l'ordine di scrittura di un indirizzo italiano.
 */
add_filter('woocommerce_checkout_fields', 'cr_checkout_fields', 20);
function cr_checkout_fields($fields)
{
	// Contatti: email prima di tutto (serve per la conferma d'ordine)
	if (isset($fields['billing']['billing_email'])) {
		$fields['billing']['billing_email']['priority'] = 5;
		$fields['billing']['billing_email']['label']    = __('Email', 'cardsrift');
		$fields['billing']['billing_email']['description'] = __('Ti mandiamo qui la conferma e il codice di tracciamento. Nient’altro.', 'cardsrift');
	}
	if (isset($fields['billing']['billing_phone'])) {
		$fields['billing']['billing_phone']['priority'] = 6;
		$fields['billing']['billing_phone']['label']    = __('Telefono', 'cardsrift');
		$fields['billing']['billing_phone']['description'] = __('Lo diamo al corriere solo se serve per la consegna.', 'cardsrift');
	}

	foreach (['billing', 'shipping', 'account'] as $group) {
		foreach ($fields[$group] ?? [] as $key => $field) {
			// l'etichetta sta sopra il campo: il placeholder che la ripete è rumore
			$fields[$group][$key]['placeholder'] = '';

			// l'azienda è rara in un negozio di carte: resta, ma facoltativa e chiara
			if (substr($key, -8) === '_company') {
				$fields[$group][$key]['label'] = __('Azienda', 'cardsrift');
			}

			// address_2 nasconde l'etichetta e affida tutto al placeholder: senza
			// placeholder resterebbe un campo muto. Etichetta visibile e niente ambiguità.
			if (substr($key, -10) === '_address_2') {
				$fields[$group][$key]['label']       = __('Interno, scala, presso', 'cardsrift');
				$fields[$group][$key]['label_class'] = [];
				$fields[$group][$key]['required']    = false;
			}
		}
	}

	if (isset($fields['order']['order_comments'])) {
		$fields['order']['order_comments']['label']       = __('Note per noi', 'cardsrift');
		$fields['order']['order_comments']['placeholder'] = __('Istruzioni per la consegna, richieste sull’imballo…', 'cardsrift');
	}

	return $fields;
}

/**
 * Campi indirizzo — sorgente unica.
 *
 * I placeholder non basta toglierli dal checkout: `address-i18n.js` li riscrive
 * lato client a ogni cambio di nazione, leggendoli dal locale "default" (che NON
 * passa da `woocommerce_get_country_locale`). La sorgente vera di quel locale è
 * `get_default_address_fields()` — quindi si interviene qui, e la correzione vale
 * anche per il modulo indirizzi dell'account.
 *
 * Inoltre address_2 nasconde l'etichetta e affida il significato al solo
 * placeholder: senza placeholder resterebbe un campo muto. Etichetta visibile.
 */
add_filter('woocommerce_default_address_fields', 'cr_default_address_fields', 20);
function cr_default_address_fields($fields)
{
	foreach ($fields as $key => $field) {
		unset($fields[$key]['placeholder']);
	}

	if (isset($fields['address_2'])) {
		$fields['address_2']['label']       = __('Interno, scala, presso', 'cardsrift');
		$fields['address_2']['label_class'] = [];
		$fields['address_2']['required']    = false;
	}

	if (isset($fields['company'])) {
		$fields['company']['label'] = __('Azienda', 'cardsrift');
	}

	return $fields;
}

/** Il secondo indirizzo si chiama "spedizione": diciamolo con parole nostre. */
add_filter('woocommerce_ship_to_different_address_checked', '__return_false');

/**
 * Nome del pacco nel riepilogo: WooCommerce lo chiama "Shipment" e la stringa resta
 * in inglese anche a sito italiano. Qui spediamo un pacco solo, quindi è una riga
 * di riepilogo, non un contatore.
 */
add_filter('woocommerce_shipping_package_name', function ($name, $package_id, $package, $total = 1) {
	return $total > 1 ? $name : __('Spedizione', 'cardsrift');
}, 20, 4);

/** Copy dei pulsanti e dei messaggi del checkout. */
add_filter('woocommerce_order_button_text', function () {
	return __('Concludi l’ordine', 'cardsrift');
});
add_filter('woocommerce_checkout_must_be_logged_in_message', function () {
	return __('Per concludere l’ordine devi accedere al tuo account.', 'cardsrift');
});

/**
 * Metodi di pagamento: nome e descrizione in italiano.
 *
 * WooPayments e PayPal arrivano con le loro etichette in inglese ("Card",
 * "Pay via PayPal.", "Proceed to PayPal") e non passano da nessun campo di
 * amministrazione: in negozio si leggevano così com'erano. Il filtro tocca solo
 * il testo — id, icone e comportamento restano quelli del gateway.
 */
add_filter('woocommerce_available_payment_gateways', 'cr_gateway_copy', 20);
function cr_gateway_copy($gateways)
{
	if (is_admin() && !wp_doing_ajax()) {
		return $gateways;
	}

	foreach ($gateways as $id => $gateway) {
		if ('woocommerce_payments' === $id) {
			$gateway->title = __('Carta di credito o debito', 'cardsrift');
		}

		if ('ppcp-gateway' === $id) {
			$gateway->title             = __('PayPal', 'cardsrift');
			$gateway->description       = cr_paypal_description();
			$gateway->order_button_text = __('Vai a PayPal', 'cardsrift');
		}
	}

	return $gateways;
}

/**
 * Ordine dei metodi: prima la carta, poi PayPal.
 *
 * Il primo della lista è anche quello **preselezionato**: `set_current_gateway()`
 * marca come scelto il primo gateway disponibile quando la sessione non ne ha già
 * uno. Quindi questa funzione decide due cose insieme — l'ordine e il default.
 *
 * ⚠️ Sta qui e non in WooCommerce → Pagamenti perché quell'ordine vive nel database:
 * andrebbe rifatto a mano in produzione e si perderebbe a ogni ripartenza. Il rovescio
 * della medaglia: trascinare le righe in wp-admin non ha più effetto sul negozio,
 * l'ordine si cambia da questo array.
 */
add_filter('woocommerce_available_payment_gateways', 'cr_gateway_order', 30);
function cr_gateway_order($gateways)
{
	$ordine = ['woocommerce_payments', 'ppcp-gateway'];
	$primi  = [];

	foreach ($ordine as $id) {
		if (isset($gateways[$id])) {
			$primi[$id] = $gateways[$id];
			unset($gateways[$id]);
		}
	}

	// quelli non elencati (bonifico, contrassegno…) restano in coda nel loro ordine
	return $primi + $gateways;
}

/**
 * I bottoni PayPal dentro il riquadro del metodo, non sotto al blocco pagamento.
 *
 * Il plugin li stampa di default su `woocommerce_review_order_after_payment`, cioè
 * fuori da `#payment`: comparivano staccati, sotto l'informativa privacy. Qui il
 * punto d'aggancio diventa un'azione nostra, che `payment-method.php` esegue dentro
 * il `payment_box` di PayPal — così i bottoni **nascono** nel riquadro.
 *
 * ⚠️ Spostarli via JavaScript non è un'alternativa: sono iframe di PayPal (zoid), e
 * ri-appenderli altrove nel DOM li fa ricaricare e morire. L'unico modo è cambiare
 * dove vengono creati.
 *
 * ⚠️ Sullo stesso hook il plugin stampa anche i bottoni del gateway "carta PayPal"
 * (`ppcp-card-button-gateway`, oggi spento): se un domani lo si accende, finirebbe
 * anche lui dentro il riquadro di PayPal.
 */
add_filter('woocommerce_paypal_payments_checkout_button_renderer_hook', 'cr_ppcp_buttons_hook');
function cr_ppcp_buttons_hook()
{
	return 'cr_ppcp_checkout_buttons';
}

/**
 * Niente bottoni PayPal nel carrello: si passa sempre dal checkout.
 *
 * Il pulsante "Paga con PayPal" accanto a "Vai al checkout" salta il nostro percorso
 * (contatti, indirizzo, note) e porta l'ordine dritto a PayPal. Qui il punto
 * d'aggancio dei bottoni del carrello diventa un'azione che **non eseguiamo mai**:
 * il plugin la registra, nessuno la chiama, i bottoni non nascono.
 *
 * ⚠️ Il messaggio "paga in 3 rate" del carrello usava come default lo stesso hook dei
 * bottoni: senza il secondo filtro sparirebbe anche lui, che invece è solo
 * un'informazione e resta dov'era (priorità 19, sopra il pulsante).
 *
 * Nel drawer non compaiono già oggi, ma non per una scelta del plugin: il nostro
 * `cart/mini-cart.php` non esegue `woocommerce_widget_shopping_cart_after_buttons`.
 * Se un domani quell'hook torna nel template, tornano anche i bottoni.
 */
add_filter('woocommerce_paypal_payments_proceed_to_checkout_button_renderer_hook', 'cr_ppcp_cart_buttons_hook');
function cr_ppcp_cart_buttons_hook()
{
	return 'cr_ppcp_bottoni_carrello_mai';
}

add_filter('woocommerce_paypal_payments_cart_messages_renderer_hook', 'cr_ppcp_cart_message_hook');
function cr_ppcp_cart_message_hook()
{
	return 'woocommerce_proceed_to_checkout';
}

/**
 * ⚠️ PayPal non legge `$gateway->description`: il suo `get_description()` prende il
 * testo dalle proprie opzioni e ricade sulla proprietà solo se quella chiave non
 * esiste — cioè quasi mai, perché il plugin la salva vuota. Il testo passa da qui.
 */
add_filter('woocommerce_paypal_payments_gateway_description', 'cr_paypal_description', 20);
function cr_paypal_description()
{
	return __('Confermi su PayPal e torni qui. Paghi con il saldo, il conto collegato o una carta salvata.', 'cardsrift');
}

/**
 * Le poche stringhe di WooPayments che restano in inglese anche con WordPress in
 * italiano: stanno nel dominio del plugin, quindi non passano dai nostri .po.
 * `<number>` e `<a>` non sono HTML: sono segnaposto che il plugin sostituisce dopo.
 */
add_filter('gettext_woocommerce-payments', 'cr_gateway_strings', 20, 2);
function cr_gateway_strings($translated, $original)
{
	$nostre = [
		// separatore dell'express checkout (Apple Pay / Google Pay)
		'OR' => __('oppure', 'cardsrift'),
		// nota della modalità test: si vede solo finché i pagamenti non sono live
		'Use test card <number>%s</number> or refer to our <a>testing guide</a>.'
			=> __('Modalità test: usa la carta <number>%s</number> o consulta la <a>guida alle prove</a>.', 'cardsrift'),
	];

	return $nostre[$original] ?? $translated;
}

/**
 * "Crea un account" al checkout: la registrazione va giustificata, non chiesta.
 * Il vantaggio è concreto e già configurato (coupon di benvenuto).
 */
add_action('woocommerce_before_checkout_registration_form', 'cr_checkout_register_reason');
function cr_checkout_register_reason()
{
	$pct = defined('CR_WELCOME_PCT') ? CR_WELCOME_PCT : '5%';
?>
	<p class="text-sm text-th-muted leading-relaxed mb-4">
		<?php printf(
			esc_html__('Con l’account segui la spedizione, ritrovi gli indirizzi al prossimo ordine e ricevi subito un codice −%s da usare quando vuoi.', 'cardsrift'),
			esc_html($pct)
		); ?>
	</p>
<?php
}

/* =====================================================================
 * 8 · ACCOUNT
 * ===================================================================== */

/** Titolo della pagina account: cambia con l'endpoint (l'H1 dice sempre dove sei). */
function cr_account_title()
{
	if (!is_user_logged_in()) {
		return __('Accedi', 'cardsrift');
	}
	global $wp;
	$endpoint = '';
	foreach (array_keys(WC()->query->get_query_vars()) as $key) {
		if (isset($wp->query_vars[$key])) {
			$endpoint = $key;
			break;
		}
	}
	switch ($endpoint) {
		case 'orders':
			return __('I tuoi ordini', 'cardsrift');
		case 'view-order':
			return __('Dettaglio ordine', 'cardsrift');
		case 'edit-address':
			return __('Indirizzi', 'cardsrift');
		case 'edit-account':
			return __('Dati dell’account', 'cardsrift');
		case 'customer-logout':
			return __('Esci', 'cardsrift');
	}
	return __('Il tuo account', 'cardsrift');
}

/** Icone della navigazione account (stesso tratto delle icone dell'header: 1.8, round). */
function cr_account_nav_icon($endpoint)
{
	$paths = [
		'dashboard'       => 'M4 10.5L12 4l8 6.5V20H4z',
		'orders'          => 'M6 3h9l4 4v14H6zM9 12h7M9 16h5',
		'edit-address'    => 'M12 21s7-6 7-11a7 7 0 10-14 0c0 5 7 11 7 11z M12 12a2.2 2.2 0 100-4.4 2.2 2.2 0 000 4.4z',
		'edit-account'    => 'M12 12a4 4 0 100-8 4 4 0 000 8zM4 21c0-4 4-6 8-6s8 2 8 6',
		'customer-logout' => 'M15 17l5-5-5-5M20 12H9M12 4H5v16h7',
	];
	$d = $paths[$endpoint] ?? 'M5 12h14';
	// aria-hidden come tutte le altre icone del tema: il nome della voce è nel testo accanto
	return '<svg class="cr-accnav__ico" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="' . esc_attr($d) . '"/></svg>';
}

/* =====================================================================
 * 9 · HEADER FOCALIZZATO SUL CHECKOUT
 * ===================================================================== */

/**
 * Nel checkout la navigazione è una via di fuga: Baymard la indica fra le prime
 * cause di abbandono evitabili. In quella pagina (e solo lì) l'header resta
 * ridotto a logo + rassicurazione. Il logo continua a portare alla home.
 */
function cr_is_focused_checkout()
{
	return function_exists('is_checkout') && is_checkout() && !is_order_received_page();
}
