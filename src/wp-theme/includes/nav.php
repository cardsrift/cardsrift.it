<?php

/**
 * NAVIGAZIONE DELL'HEADER + RICERCA PRODOTTI
 *
 * Il menu NON viene dai menu di WordPress: si genera da CR_GAMES × CR_TIPI_CARTE
 * (includes/routing.php), così un gioco nuovo compare da solo in barra, nel menu
 * mobile e nei filtri della ricerca — senza data entry in wp-admin.
 *
 * Ricerca: il sito vende prodotti, quindi la ricerca cerca SOLO prodotti
 * (cr_scope_search). La regola anti-misto resta rispettata perché ogni risultato
 * porta il chip del suo gioco e, se stai già dentro un gioco, la ricerca parte
 * ristretta a quello (parametro `cr_g`, con la via d'uscita "tutti i giochi").
 *
 * ⚠️ Il filtro usa `cr_g`, NON `cr_game`: quest'ultimo è una query var registrata
 *    dal routing e trasformerebbe la ricerca in un archivio di gioco.
 */

if (!defined('ABSPATH')) {
	exit;
}

/* ------------------------------------------------------------------ *
 *  1) Modello del menu
 * ------------------------------------------------------------------ */

/** Descrizione breve di un tipo, per le tendine del menu. */
function cr_tipo_desc($slug)
{
	$map = [
		'sealed'    => __('Box, buste e prodotti sigillati', 'cardsrift'),
		'singole'   => __('Controllate una a una, con foto reale', 'cardsrift'),
		'accessori' => __('Bustine, raccoglitori, deck box', 'cardsrift'),
	];
	return $map[$slug] ?? '';
}

/** I giochi con le loro due porte: [slug, label, url, porte[]]. */
function cr_nav_games()
{
	$games = defined('CR_GAMES') ? CR_GAMES : [];
	$tipi  = defined('CR_TIPI_CARTE') ? CR_TIPI_CARTE : [];
	$out   = [];

	foreach ($games as $g) {
		$porte = [];
		foreach ($tipi as $t) {
			$porte[] = [
				'slug'  => $t,
				'label' => cr_tipo_label($t),
				'desc'  => cr_tipo_desc($t),
				'url'   => home_url('/' . $g . '/' . $t . '/'),
			];
		}
		$out[] = [
			'slug'  => $g,
			// in barra e nel menu l'etichetta è quella corta: "Magic", non "Magic: The Gathering"
			'label' => cr_game_label_short($g),
			'url'   => home_url('/' . $g . '/'),
			'porte' => $porte,
		];
	}
	return $out;
}

/**
 * Pagine di servizio. $with_about aggiunge "Chi siamo": sta nel menu mobile,
 * non nella riga utility del desktop (che deve restare corta a 1024px).
 */
function cr_nav_info($with_about = false)
{
	$links = [];
	if ($with_about) {
		$links[] = ['label' => __('Chi siamo', 'cardsrift'), 'url' => home_url('/chi-siamo/')];
	}
	return array_merge($links, [
		['label' => __('Spedizioni e resi', 'cardsrift'),     'url' => home_url('/spedizioni-e-resi/')],
		['label' => __('Guida alle condizioni', 'cardsrift'), 'url' => home_url('/guida-alle-condizioni/')],
		['label' => __('Domande frequenti', 'cardsrift'),     'url' => home_url('/faq/')],
		['label' => __('Contatti', 'cardsrift'),              'url' => home_url('/contatti/')],
	]);
}

/* ------------------------------------------------------------------ *
 *  2) Ricerca
 * ------------------------------------------------------------------ */

/** Gioco a cui è ristretta la ricerca corrente ('' = tutti). */
function cr_search_game()
{
	$g = isset($_GET['cr_g']) ? sanitize_title(wp_unslash($_GET['cr_g'])) : '';
	return ($g && defined('CR_GAMES') && in_array($g, CR_GAMES, true)) ? $g : '';
}

/** URL della ricerca con termine e (opzionale) gioco. */
function cr_search_url($term, $game = '')
{
	$args = ['s' => $term];
	if ($game) {
		$args['cr_g'] = $game;
	}
	return add_query_arg(array_map('rawurlencode', $args), home_url('/'));
}

/* ------------------------------------------------------------------ *
 *  3) Campo di ricerca con tendina di suggerimenti
 * ------------------------------------------------------------------ */

/**
 * Il campo di ricerca dell'header — uno solo, usato sia su desktop sia nel menu
 * mobile: cambia solo il vestito, non il comportamento.
 *
 * $args: id (obbligatorio, serve agli aria-*), class (wrapper), box (la scatola
 * visibile), placeholder, game (restringe la ricerca a un gioco).
 */
function cr_search_form($args = [])
{
	$id    = $args['id'] ?? 'cr-search';
	$game  = $args['game'] ?? '';
	$listy = $id . '-list';
	// endpoint della tendina: da PHP, non da una variabile JS globale di WooCommerce
	// (`wc_add_to_cart_params` non è garantita fuori dalle pagine negozio)
	$url   = class_exists('WC_AJAX') ? WC_AJAX::get_endpoint('cr_suggest') : home_url('/?wc-ajax=cr_suggest');
?>
	<form role="search" method="get" action="<?= esc_url(home_url('/')); ?>" class="relative <?= esc_attr($args['class'] ?? ''); ?>" data-cr-suggest="<?= esc_url($url); ?>">
		<div class="flex items-center gap-2.5 h-full px-3.5 rounded-[10px] bg-th-surface border border-th-lines transition-colors focus-within:border-th-acc">
			<svg class="w-[17px] h-[17px] stroke-th-soft fill-transparent shrink-0" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7" /><path d="M16.5 16.5L21 21" /></svg>
			<label class="sr-only" for="<?= esc_attr($id); ?>"><?= esc_attr__('Cerca', 'cardsrift'); ?></label>
			<input id="<?= esc_attr($id); ?>" type="search" name="s" value="<?= esc_attr(get_search_query()); ?>"
				placeholder="<?= esc_attr($args['placeholder'] ?? __('Cerca una carta o un set…', 'cardsrift')); ?>"
				class="w-full bg-transparent border-0 p-0 font-metropolis text-sm text-th-ink placeholder:text-th-soft focus:outline-none"
				role="combobox" aria-expanded="false" aria-controls="<?= esc_attr($listy); ?>" aria-autocomplete="list" autocomplete="off">
			<?php if ($game) : ?><input type="hidden" name="cr_g" value="<?= esc_attr($game); ?>"><?php endif; ?>
		</div>
		<?php // la tendina è più larga del campo e su desktop cresce verso sinistra, per non uscire dalla barra ?>
		<div id="<?= esc_attr($listy); ?>" role="listbox" aria-label="<?= esc_attr__('Suggerimenti', 'cardsrift'); ?>" hidden
			class="cr-suggest absolute z-50 top-[calc(100%+8px)] left-0 right-0 lg:left-auto lg:w-[380px] p-1.5 rounded-2xl bg-th-surface border border-th-line shadow-th overflow-hidden"></div>
	</form>
<?php
}

/** Una riga della tendina. Prezzo sempre da get_price_html(), come da design system. */
function cr_suggest_row($pid, $index)
{
	$product = wc_get_product($pid);
	if (!$product) {
		return '';
	}

	// ⚠️ miniatura: NON $product->get_image(), che cr_full_res_image() porterebbe a 960px.
	// Qui basta l'anteprima del plugin di sync (~180px) o la size piccola della libreria.
	$img_id = $product->get_image_id();
	$src    = $img_id ? wp_get_attachment_image_url($img_id, 'woocommerce_thumbnail') : get_post_meta($pid, '_ct_image', true);
	$chip   = function_exists('cr_product_chip') ? cr_product_chip($pid) : '';

	ob_start();
?>
	<?php // duration-200: col default del progetto (400ms) la selezione da tastiera arriva
	// dopo il tasto successivo. L'anello serve a chi naviga con le frecce: la sola
	// tinta accsoft (13%) su fondo bianco è troppo debole per indicare "sei qui". ?>
	<a class="flex items-center gap-3 px-2.5 py-2 rounded-xl no-underline transition-colors duration-200 hover:bg-th-accsoft aria-selected:bg-th-accsoft aria-selected:ring-1 aria-selected:ring-inset aria-selected:ring-th-acc2"
		role="option" id="<?= esc_attr('cr-sg-' . $index); ?>" aria-selected="false" href="<?= esc_url(get_permalink($pid)); ?>">
		<span class="w-10 h-[52px] shrink-0 rounded-md bg-white-pure overflow-hidden grid place-items-center">
			<?php if ($src) : ?><img class="w-full h-full object-contain" src="<?= esc_url($src); ?>" alt="" loading="lazy" decoding="async"><?php endif; ?>
		</span>
		<span class="min-w-0 flex-1">
			<span class="block font-metropolis text-sm font-medium text-th-ink truncate"><?= esc_html(get_the_title($pid)); ?></span>
			<?php if ($chip) : ?><span class="block font-bylon uppercase text-[10px] tracking-[.16em] text-th-soft mt-0.5"><?= esc_html($chip); ?></span><?php endif; ?>
		</span>
		<span class="shrink-0 font-metropolis text-sm font-semibold text-th-acc tabular-nums"><?= wp_kses_post($product->get_price_html()); ?></span>
	</a>
<?php
	return ob_get_clean();
}

/**
 * Endpoint della tendina: restituisce HTML già pronto (come i frammenti del
 * mini-carrello), così il markup resta in PHP e Tailwind lo vede.
 */
add_action('wc_ajax_cr_suggest', 'cr_ajax_suggest');
add_action('wc_ajax_nopriv_cr_suggest', 'cr_ajax_suggest');
function cr_ajax_suggest()
{
	$term = isset($_GET['term']) ? sanitize_text_field(wp_unslash($_GET['term'])) : '';
	$game = isset($_GET['cr_g']) ? sanitize_title(wp_unslash($_GET['cr_g'])) : '';
	$game = ($game && defined('CR_GAMES') && in_array($game, CR_GAMES, true)) ? $game : '';

	if (mb_strlen($term) < 2) {
		wp_send_json(['html' => '']);
	}

	$tax = [];
	if ($game) {
		$tax[] = ['taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => [$game]];
	}
	if (taxonomy_exists('product_visibility')) {
		// rispetta i prodotti che il negozio ha escluso dalla ricerca
		$tax[] = ['taxonomy' => 'product_visibility', 'field' => 'name', 'terms' => 'exclude-from-search', 'operator' => 'NOT IN'];
	}

	$q = new WP_Query([
		'post_type'              => 'product',
		'post_status'            => 'publish',
		'posts_per_page'         => 6,
		's'                      => $term,
		'cr_suggest'             => true,   // attiva il match sul solo titolo (filtro qui sotto)
		'tax_query'              => $tax,
		'ignore_sticky_posts'    => true,
		'no_found_rows'          => true,
		'update_post_term_cache' => false,
	]);

	ob_start();
	if ($q->have_posts()) {
		$i = 0;
		foreach ($q->posts as $post) {
			echo cr_suggest_row($post->ID, $i++);
		}
		echo '<span class="block h-px bg-th-line mx-2.5 my-1.5"></span>';
		printf(
			'<a class="flex items-center justify-between gap-3 px-2.5 py-2 rounded-xl no-underline font-metropolis text-[13px] font-semibold text-th-acc transition-colors duration-200 hover:bg-th-accsoft aria-selected:bg-th-accsoft aria-selected:ring-1 aria-selected:ring-inset aria-selected:ring-th-acc2" role="option" id="cr-sg-%d" aria-selected="false" href="%s">%s <span aria-hidden="true">→</span></a>',
			$i,
			esc_url(cr_search_url($term, $game)),
			sprintf(esc_html__('Vedi tutti i risultati per «%s»', 'cardsrift'), esc_html($term))
		);
	} else {
		printf(
			'<p class="px-3 py-3 font-metropolis text-sm text-th-muted">%s</p>',
			$game
				? sprintf(esc_html__('Niente in %s. Prova a cercare in tutti i giochi.', 'cardsrift'), esc_html(cr_game_label($game)))
				: esc_html__('Nessun suggerimento. Prova con meno lettere.', 'cardsrift')
		);
		printf(
			'<a class="flex items-center justify-between gap-3 px-2.5 py-2 rounded-xl no-underline font-metropolis text-[13px] font-semibold text-th-acc transition-colors duration-200 hover:bg-th-accsoft aria-selected:bg-th-accsoft aria-selected:ring-1 aria-selected:ring-inset aria-selected:ring-th-acc2" role="option" id="cr-sg-0" aria-selected="false" href="%s">%s <span aria-hidden="true">→</span></a>',
			esc_url(cr_search_url($term)),
			esc_html__('Cerca in tutto il catalogo', 'cardsrift')
		);
	}
	wp_reset_postdata();

	wp_send_json(['html' => ob_get_clean()]);
}

/**
 * Nei suggerimenti si cerca SOLO nel titolo: la ricerca piena guarda anche la
 * descrizione, ma in una tendina da sei righe darebbe risultati che a chi scrive
 * sembrano sbagliati (la parola non si vede da nessuna parte nella riga).
 */
add_filter('posts_search', 'cr_suggest_search_title_only', 10, 2);
function cr_suggest_search_title_only($search, $q)
{
	if (!$q->get('cr_suggest') || !$q->get('s')) {
		return $search;
	}
	global $wpdb;
	return $wpdb->prepare(" AND {$wpdb->posts}.post_title LIKE %s ", '%' . $wpdb->esc_like($q->get('s')) . '%');
}

/**
 * La ricerca è una ricerca di prodotti. Se arriva `cr_g` la restringe a un gioco:
 * è così che l'header propone "Cerca in Magic" quando sei già dentro Magic.
 */
add_action('pre_get_posts', 'cr_scope_search');
function cr_scope_search($q)
{
	if (is_admin() || !$q->is_main_query() || !$q->is_search()) {
		return;
	}

	$q->set('post_type', 'product');
	$q->set('post_status', 'publish');
	$q->set('posts_per_page', 24);

	$game = cr_search_game();
	if ($game) {
		$q->set('tax_query', [[
			'taxonomy' => 'product_cat',
			'field'    => 'slug',
			'terms'    => [$game],
		]]);
	}
}
