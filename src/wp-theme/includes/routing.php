<?php

/**
 * ROUTING PER-GIOCO + GUARDIANO ANTI-MISTO
 *
 * Regola di business: i giochi non si mescolano MAI. L'utente vede solo listati
 * gioco-scoped (`/{gioco}/{tipo}`), mai un catalogo globale.
 *
 *  Rotte pulite (rewrite):
 *   /{gioco}                     → landing di gioco            (archive-game.php)
 *   /{gioco}/{tipo}              → listato gioco+tipo          (archive-game-tipo.php)
 *   /accessori                   → listato globale accessori   (archive-accessori.php)
 *   ( + /page/N per la paginazione )
 *
 *  Guardiano: /shop e ogni archivio "misto" (tipo puro, product_tag) vengono
 *  reindirizzati; gli archivi grezzi di WooCommerce (/product-category/…) vengono
 *  ricondotti alla rotta pulita. Vedi docs/catalogo-import.md e i prototipi.
 *
 * NB: gioco/tipo sono termini `product_cat`. Lo slug del gioco resta 'magic' anche
 *     se il display è "Magic: The Gathering" (vedi cr_game_label()).
 */

if (!defined('ABSPATH')) {
	exit;
}

define('CR_ROUTING_VER', '1');                       // bump per forzare il flush delle rewrite
define('CR_GAMES', ['magic', 'pokemon', 'one-piece']); // slug gioco (== termini product_cat)
define('CR_TIPI_CARTE', ['sealed', 'singole']);        // tipi per-gioco (l'ordine è Sealed → Singole)

/** Etichetta display di un gioco (lo slug resta invariato per URL/tassonomia). */
function cr_game_label($slug)
{
	$map = ['magic' => 'Magic: The Gathering', 'pokemon' => 'Pokémon', 'one-piece' => 'One Piece'];
	return $map[$slug] ?? ucfirst((string) $slug);
}

/** Etichetta display di un tipo. */
function cr_tipo_label($slug)
{
	$map = ['sealed' => 'Sealed', 'singole' => 'Carte singole', 'accessori' => 'Accessori'];
	return $map[$slug] ?? ucfirst((string) $slug);
}

/* ------------------------------------------------------------------ *
 *  1) Rewrite rules + query vars
 * ------------------------------------------------------------------ */
add_action('init', 'cr_register_routes', 10);
function cr_register_routes()
{
	$games = implode('|', CR_GAMES);
	$tipi  = implode('|', CR_TIPI_CARTE);

	// /{gioco}/{tipo}[/page/N]
	add_rewrite_rule("^($games)/($tipi)/page/([0-9]+)/?$", 'index.php?cr_game=$matches[1]&cr_tipo=$matches[2]&paged=$matches[3]', 'top');
	add_rewrite_rule("^($games)/($tipi)/?$",               'index.php?cr_game=$matches[1]&cr_tipo=$matches[2]', 'top');
	// /accessori[/page/N] (globale)
	add_rewrite_rule('^accessori/page/([0-9]+)/?$', 'index.php?cr_tipo=accessori&paged=$matches[1]', 'top');
	add_rewrite_rule('^accessori/?$',               'index.php?cr_tipo=accessori', 'top');
	// /{gioco} (landing)
	add_rewrite_rule("^($games)/?$", 'index.php?cr_game=$matches[1]&cr_landing=1', 'top');

	// flush una tantum quando cambia la versione delle regole (niente salvataggio manuale dei permalink)
	if (get_option('cr_rewrite_ver') !== CR_ROUTING_VER) {
		flush_rewrite_rules(false);
		update_option('cr_rewrite_ver', CR_ROUTING_VER);
	}
}

add_filter('query_vars', 'cr_register_query_vars');
function cr_register_query_vars($vars)
{
	$vars[] = 'cr_game';
	$vars[] = 'cr_tipo';
	$vars[] = 'cr_landing';
	return $vars;
}
add_action('after_switch_theme', 'flush_rewrite_rules');

/* ------------------------------------------------------------------ *
 *  2) La query principale diventa un archivio prodotti gioco-scoped
 * ------------------------------------------------------------------ */
add_action('pre_get_posts', 'cr_scope_main_query');
function cr_scope_main_query($q)
{
	if (is_admin() || !$q->is_main_query()) {
		return;
	}
	$game = $q->get('cr_game');
	$tipo = $q->get('cr_tipo');
	if (!$game && !$tipo) {
		return; // rotta non nostra
	}

	// tax_query = gioco AND tipo (uno dei due può mancare: landing = solo gioco, accessori = solo tipo)
	$tax = ['relation' => 'AND'];
	if ($game) {
		$tax[] = ['taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => [$game]];
	}
	if ($tipo) {
		$tax[] = ['taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => [$tipo]];
	}

	// Filtri faccetta dai parametri GET (solo listati, non la landing). Nomi param = taxonomy.
	if (!$q->get('cr_landing')) {
		foreach (['pa_espansione', 'pa_condizione', 'pa_lingua', 'pa_foil'] as $txn) {
			if (!empty($_GET[$txn])) {
				$tax[] = ['taxonomy' => $txn, 'field' => 'slug', 'terms' => [sanitize_title(wp_unslash($_GET[$txn]))]];
			}
		}
	}

	$q->set('post_type', 'product');
	$q->set('post_status', 'publish');
	$q->set('tax_query', $tax);
	$q->set('posts_per_page', $q->get('cr_landing') ? 8 : 24);

	// Prezzo (min/max) + ordinamento — solo listati.
	if (!$q->get('cr_landing')) {
		$min = (isset($_GET['cr_min']) && $_GET['cr_min'] !== '') ? (float) $_GET['cr_min'] : null;
		$max = (isset($_GET['cr_max']) && $_GET['cr_max'] !== '') ? (float) $_GET['cr_max'] : null;
		if ($min !== null || $max !== null) {
			$pq = ['key' => '_price', 'type' => 'NUMERIC'];
			if ($min !== null && $max !== null) {
				$pq['value']   = [$min, $max];
				$pq['compare'] = 'BETWEEN';
			} elseif ($min !== null) {
				$pq['value']   = $min;
				$pq['compare'] = '>=';
			} else {
				$pq['value']   = $max;
				$pq['compare'] = '<=';
			}
			$q->set('meta_query', [$pq]);
		}
		switch ($_GET['cr_ord'] ?? '') {
			case 'prezzo-asc':
				$q->set('meta_key', '_price');
				$q->set('orderby', 'meta_value_num');
				$q->set('order', 'ASC');
				break;
			case 'prezzo-desc':
				$q->set('meta_key', '_price');
				$q->set('orderby', 'meta_value_num');
				$q->set('order', 'DESC');
				break;
			case 'nome':
				$q->set('orderby', 'title');
				$q->set('order', 'ASC');
				break;
			default: // novità
				$q->set('orderby', 'date');
				$q->set('order', 'DESC');
		}
	}

	// facciamola riconoscere come archivio (niente 404/home) e lascia agire i filtri prodotto di WC
	$q->is_home              = false;
	$q->is_page              = false;
	$q->is_archive           = true;
	$q->is_post_type_archive = true;
}

/* ------------------------------------------------------------------ *
 *  3) template_include: carica il template a tema giusto
 * ------------------------------------------------------------------ */
add_filter('template_include', 'cr_route_template');
function cr_route_template($template)
{
	$game    = get_query_var('cr_game');
	$tipo    = get_query_var('cr_tipo');
	$landing = get_query_var('cr_landing');

	if ($landing && $game) {
		$t = locate_template('archive-game.php');
		return $t ?: $template;
	}
	if ($tipo === 'accessori') {
		$t = locate_template('archive-accessori.php');
		return $t ?: $template;
	}
	if ($game && $tipo) {
		$t = locate_template('archive-game-tipo.php');
		return $t ?: $template;
	}
	return $template;
}

/* ------------------------------------------------------------------ *
 *  4) GUARDIANO anti-misto: niente /shop né viste globali
 * ------------------------------------------------------------------ */
add_action('template_redirect', 'cr_guard_no_mixed', 1);
function cr_guard_no_mixed()
{
	if (is_admin()) {
		return;
	}

	// le NOSTRE rotte gioco-scoped (/{gioco}, /{gioco}/{tipo}, /accessori) sono legittime
	if (get_query_var('cr_game') || get_query_var('cr_tipo')) {
		return;
	}

	// /shop e archivio prodotti globale → home
	if (function_exists('is_shop') && (is_shop() || is_post_type_archive('product'))) {
		wp_safe_redirect(home_url('/'), 302);
		exit;
	}

	// archivi grezzi product_cat di WooCommerce → rotta pulita o home
	if (is_tax('product_cat')) {
		$term = get_queried_object();
		if ($term && !empty($term->slug)) {
			$tipi = defined('CR_CAT_TIPO') ? CR_CAT_TIPO : ['singole', 'sealed', 'accessori'];
			if ($term->slug === 'accessori') {
				wp_safe_redirect(home_url('/accessori/'), 301);
				exit;
			}
			if (in_array($term->slug, $tipi, true)) {
				wp_safe_redirect(home_url('/'), 302); // tipo puro = misto tra giochi
				exit;
			}
			if (in_array($term->slug, CR_GAMES, true)) {
				wp_safe_redirect(home_url('/' . $term->slug . '/'), 301); // gioco → landing pulita
				exit;
			}
		}
	}

	// product_tag → potenzialmente misto tra giochi
	if (is_tax('product_tag')) {
		wp_safe_redirect(home_url('/'), 302);
		exit;
	}
}

/* ------------------------------------------------------------------ *
 *  5) Contesto di gioco corrente (per header game-switcher + subnav)
 * ------------------------------------------------------------------ */

/** Slug del gioco di un prodotto (primo product_cat che è un gioco). '' se assente. */
function cr_product_game_slug($pid)
{
	$terms = get_the_terms($pid, 'product_cat');
	if (!$terms || is_wp_error($terms)) {
		return '';
	}
	foreach ($terms as $t) {
		if (in_array($t->slug, CR_GAMES, true)) {
			return $t->slug;
		}
	}
	return '';
}

/** Slug del tipo di un prodotto (singole/sealed/accessori). '' se assente. */
function cr_product_tipo_slug($pid)
{
	$tipi = defined('CR_CAT_TIPO') ? CR_CAT_TIPO : ['singole', 'sealed', 'accessori'];
	$terms = get_the_terms($pid, 'product_cat');
	if (!$terms || is_wp_error($terms)) {
		return '';
	}
	foreach ($terms as $t) {
		if (in_array($t->slug, $tipi, true)) {
			return $t->slug;
		}
	}
	return '';
}

/** Gioco attivo nel contesto (rotta gioco o pagina prodotto). '' su home/globale. */
function cr_current_game()
{
	$g = get_query_var('cr_game');
	if ($g) {
		return $g;
	}
	if (is_singular('product')) {
		return cr_product_game_slug(get_the_ID());
	}
	return '';
}

/** Tipo attivo nel contesto (listato o prodotto). '' su landing/globale. */
function cr_current_tipo()
{
	$t = get_query_var('cr_tipo');
	if ($t) {
		return $t;
	}
	if (is_singular('product')) {
		return cr_product_tipo_slug(get_the_ID());
	}
	return '';
}

/** Barra subnav del gioco attivo (Sealed → Singole). Nessun output fuori da un gioco. */
function cr_render_game_subnav()
{
	$game = cr_current_game();
	if (!$game) {
		return; // home / accessori (globale) → niente subnav di gioco
	}
	$tipo = cr_current_tipo();
	$ht   = defined('CR_HEADER_THEME') ? CR_HEADER_THEME : 'light';
	$tipi = defined('CR_TIPI_CARTE') ? CR_TIPI_CARTE : ['sealed', 'singole'];
?>
	<div class="cr-subnav bg-th-sur2 border-b border-th-line" data-th="<?= esc_attr($ht); ?>">
		<div class="tw-container tw-section">
			<div class="flex items-center gap-2 py-2.5 font-metropolis text-sm overflow-x-auto">
				<a class="font-bylon uppercase tracking-[.16em] text-xs text-th-acc no-underline whitespace-nowrap mr-2" href="<?= esc_url(home_url('/' . $game . '/')); ?>"><?= esc_html(cr_game_label($game)); ?> ›</a>
				<?php foreach ($tipi as $t) : $is = ($t === $tipo); ?>
					<a class="font-semibold px-3 py-1.5 rounded-lg whitespace-nowrap transition-colors <?= $is ? 'bg-th-accsoft text-th-acc' : 'text-th-muted hover:text-th-ink'; ?>" href="<?= esc_url(home_url('/' . $game . '/' . $t . '/')); ?>"><?= esc_html(cr_tipo_label($t)); ?></a>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
<?php
}
