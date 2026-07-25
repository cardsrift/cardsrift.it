<?php

/**
 * SEO — fondamenta minime (fase rework), senza plugin: <title> moderno (title-tag),
 * meta description per pagina, Open Graph di base, canonical e dati strutturati Organization.
 * Le descrizioni per-slug stanno in cr_seo_map(); il resto ha fallback sensati.
 * Nessun indirizzo nello schema: scelta di riservatezza del venditore.
 */

/** Descrizione di riserva (brand line), usata dove non c'è nulla di più specifico. */
function cr_seo_default_description()
{
	return __('Il tuo portale per il collezionismo: carte Pokémon e Magic controllate a mano, con spedizione tracciata e condizioni oneste.', 'cardsrift');
}

/** Meta per-slug: 'title' (per il tag <title>) + 'description'. Estendibile pagina per pagina. */
function cr_seo_map()
{
	return [
		'chi-siamo' => [
			'title'       => __('Chi siamo · CardsRift — carte Pokémon e Magic', 'cardsrift'),
			'description' => __('CardsRift è carte Pokémon e Magic controllate a mano, con il 100% di valutazioni positive su Cardmarket: spedizione tracciata e condizioni dette come stanno.', 'cardsrift'),
		],
		'compriamo-le-tue-carte' => [
			'title'       => __('Compriamo le tue carte · CardsRift — vendi Pokémon e Magic', 'cardsrift'),
			'description' => __('Vendi le tue carte Pokémon e Magic. Valutiamo singole, sealed e bulk sui prezzi di Cardmarket: offerta scritta senza impegno, pagamento tracciato in 24/48h.', 'cardsrift'),
		],
		'guida-alle-condizioni' => [
			'title'       => __('Guida alle condizioni delle carte · CardsRift — la scala Cardmarket', 'cardsrift'),
			'description' => __('Cosa significano Mint, Near Mint, Excellent, Good, Light Played, Played e Poor: la scala ufficiale di Cardmarket spiegata grado per grado. Classifichiamo sempre per difetto.', 'cardsrift'),
		],
		'spedizioni-e-resi' => [
			'title'       => __('Spedizioni e resi · CardsRift — spedizione tracciata e reso in 14 giorni', 'cardsrift'),
			'description' => __('Come spediamo — Poste Italiane, tracciata, imballaggio protetto, in 24/48h — e come funziona il reso: hai 14 giorni per il diritto di recesso. Spedizione in Italia a 10,90 €.', 'cardsrift'),
		],
		'contatti' => [
			'title'       => __('Contatti · CardsRift — scrivici su Telegram, email o Instagram', 'cardsrift'),
			'description' => __('Come contattare CardsRift: scrivici su Telegram, per email o su Instagram. Scegli il canale che preferisci, di solito rispondiamo entro un giorno.', 'cardsrift'),
		],
		'dichiarazione-accessibilita' => [
			'title'       => __('Dichiarazione di accessibilità · CardsRift', 'cardsrift'),
			'description' => __('A che punto è CardsRift sull’accessibilità: standard di riferimento, cosa funziona, le limitazioni note dichiarate una per una e come segnalarci un ostacolo.', 'cardsrift'),
		],
		'faq' => [
			'title'       => __('Domande frequenti · CardsRift — carte Pokémon e Magic', 'cardsrift'),
			'description' => __('Le risposte alle domande più comuni: originalità, condizioni, spedizioni, resi e come vendere le tue carte. Tutto quello che serve prima di ordinare su CardsRift.', 'cardsrift'),
		],
	];
}

/** Slug della vista corrente (home inclusa). */
function cr_seo_current_slug()
{
	if (is_front_page() || is_home()) {
		return 'home';
	}
	$id = get_queried_object_id();
	return $id ? (string) get_post_field('post_name', $id) : '';
}

/** Parametri di filtro/ordinamento del listato. Fonte unica: la rilegge partials/listing.php. */
function cr_seo_facet_keys()
{
	return ['pa_espansione', 'pa_condizione', 'pa_lingua', 'pa_foil', 'cr_min', 'cr_max', 'cr_ord'];
}

/** La vista corrente è un listato filtrato/ordinato? */
function cr_seo_is_faceted()
{
	foreach (cr_seo_facet_keys() as $k) {
		if (isset($_GET[$k]) && $_GET[$k] !== '') {
			return true;
		}
	}
	return false;
}

/**
 * URL canonico della vista corrente: senza i parametri di filtro, con la paginazione.
 *
 * ⚠️ Non si può passare da get_permalink() fuori dalle pagine singole: il routing
 * per-gioco marca landing e listati come archivi (cr_scope_main_query in routing.php),
 * e prima di questa funzione og:url puntava alla home su TUTTI i listati.
 * `$wp->request` dà il percorso già risolto ("magic/singole/page/2"), cioè esattamente
 * la forma pulita che vogliamo dichiarare canonica.
 */
function cr_seo_canonical_url()
{
	if (is_singular()) {
		$link = get_permalink();
		return $link ?: home_url('/');
	}
	global $wp;
	$req = isset($wp->request) ? trim((string) $wp->request, '/') : '';
	return $req ? trailingslashit(home_url($req)) : home_url('/');
}

/**
 * I tratti che distinguono una carta dall'altra (espansione · condizione · lingua).
 *
 * Servono a title e description: il nome del prodotto arriva dalla colonna `Name` di
 * Cardmarket ed è IDENTICO per tutte le varianti della stessa carta (NM, EX, LP…),
 * che però sono prodotti separati con URL separati. Senza questi tratti il negozio
 * pubblica decine di pagine con lo stesso titolo e la stessa descrizione.
 */
function cr_seo_product_bits($product)
{
	if (!$product instanceof WC_Product) {
		return [];
	}
	$cond = $product->get_attribute('condizione');
	return array_values(array_filter([
		$product->get_attribute('espansione'),
		$cond ? strtoupper($cond) : '',
		$product->get_attribute('lingua'),
	]));
}

/** <title>: usa la mappa se c'è, altrimenti lascia decidere WordPress. */
add_filter('document_title_parts', 'cr_seo_title_parts');
function cr_seo_title_parts($parts)
{
	// Prodotti: il nome da solo si ripete su ogni variante della stessa carta.
	if (is_singular('product')) {
		$product = wc_get_product(get_queried_object_id());
		$bits    = $product ? cr_seo_product_bits($product) : [];
		if ($product && $bits) {
			$parts['title'] = $product->get_name() . ' (' . implode(' · ', $bits) . ')';
		}
		return $parts;
	}

	$map  = cr_seo_map();
	$slug = cr_seo_current_slug();
	if ($slug && !empty($map[$slug]['title'])) {
		return ['title' => $map[$slug]['title']];
	}
	return $parts;
}

/** Descrizione della vista: mappa → excerpt → brand line. */
function cr_seo_description()
{
	$map  = cr_seo_map();
	$slug = cr_seo_current_slug();
	if ($slug && !empty($map[$slug]['description'])) {
		return $map[$slug]['description'];
	}
	if (is_singular()) {
		$ex = get_the_excerpt();
		if ($ex) {
			return wp_strip_all_tags($ex);
		}
	}
	// Prodotti importati: excerpt e contenuto restano vuoti, quindi senza questo ramo
	// TUTTE le schede del catalogo condividerebbero la stessa descrizione di riserva.
	if (is_singular('product')) {
		$product = wc_get_product(get_queried_object_id());
		$bits    = $product ? cr_seo_product_bits($product) : [];
		if ($product && $bits) {
			return sprintf(
				/* translators: 1: nome della carta, 2: espansione · condizione · lingua */
				__('%1$s — %2$s. Controllata a mano e spedita tracciata in 24/48h.', 'cardsrift'),
				$product->get_name(),
				implode(' · ', $bits)
			);
		}
	}
	return cr_seo_default_description();
}

/**
 * Immagine per le anteprime social: foto del prodotto, altrimenti il logo.
 * Per i prodotti passa da cr_product_image_url() perché get_image_id() è 0 su tutto
 * il catalogo sincronizzato (vedi il docblock di cr_product_has_image()).
 */
function cr_seo_share_image()
{
	if (is_singular('product') && function_exists('cr_product_image_url')) {
		$product = wc_get_product(get_queried_object_id());
		$url     = $product ? cr_product_image_url($product) : '';
		if ($url) {
			return $url;
		}
	}
	if (is_singular() && has_post_thumbnail()) {
		return (string) get_the_post_thumbnail_url(get_queried_object_id(), 'large');
	}
	$logo = function_exists('get_field') ? get_field('logo', 'option') : '';
	return is_array($logo) ? (string) ($logo['url'] ?? '') : (string) $logo;
}

/** Meta description, Open Graph e canonical, in testa. */
add_action('wp_head', 'cr_seo_head_meta', 2);
function cr_seo_head_meta()
{
	$desc  = trim((string) cr_seo_description());
	$title = wp_get_document_title();
	$url   = cr_seo_canonical_url();
	$img   = cr_seo_share_image();

	if ($desc) {
		echo '<meta name="description" content="' . esc_attr($desc) . '">' . "\n";
	}

	// Canonical: il rel_canonical del core esce SOLO su is_singular(). Landing e listati
	// sono archivi per il routing custom, quindi lì non lo emette nessuno — e ogni
	// combinazione di filtri resterebbe un URL a sé, senza nulla che la riporti a casa.
	if (!is_singular() && !is_404() && !is_search()) {
		echo '<link rel="canonical" href="' . esc_url($url) . '">' . "\n";
	}

	echo '<meta property="og:type" content="' . (is_singular('product') ? 'product' : 'website') . '">' . "\n";
	echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '">' . "\n";
	echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
	if ($desc) {
		echo '<meta property="og:description" content="' . esc_attr($desc) . '">' . "\n";
	}
	echo '<meta property="og:url" content="' . esc_url($url) . '">' . "\n";
	echo '<meta property="og:locale" content="it_IT">' . "\n";

	if ($img) {
		echo '<meta property="og:image" content="' . esc_url($img) . '">' . "\n";
		echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
		echo '<meta name="twitter:title" content="' . esc_attr($title) . '">' . "\n";
		if ($desc) {
			echo '<meta name="twitter:description" content="' . esc_attr($desc) . '">' . "\n";
		}
		echo '<meta name="twitter:image" content="' . esc_url($img) . '">' . "\n";
	}
}

/**
 * Robots: fuori indice ciò che non ha senso indicizzare.
 *
 * — Listati filtrati: ogni combinazione di espansione × condizione × lingua × foil ×
 *   prezzo × ordinamento è un URL diverso con contenuto quasi identico alla pagina
 *   pulita. `follow` resta attivo: i link interni continuano a passare valore.
 * — Pagina "Design System": è un campionario di primitive, con h1 e componenti
 *   ripetuti per costruzione.
 * Carrello, checkout, account e ricerca sono già coperti da WooCommerce e dal core.
 */
add_filter('wp_robots', 'cr_seo_robots');
function cr_seo_robots($robots)
{
	if (cr_seo_is_faceted() || is_page_template('template_styleguide.php')) {
		$robots['noindex'] = true;
		$robots['follow']  = true;
		unset($robots['index']);
	}
	return $robots;
}

/** Dati strutturati Organization (sito-wide). Niente indirizzo: scelta di riservatezza. */
add_action('wp_head', 'cr_seo_org_schema', 3);
function cr_seo_org_schema()
{
	$logo     = function_exists('get_field') ? get_field('logo', 'option') : '';
	$logo_url = is_array($logo) ? ($logo['url'] ?? '') : (string) $logo;

	$same_as = array_values(array_filter([
		defined('CR_CM_URL') ? CR_CM_URL : '',
		defined('CR_TELEGRAM_URL') ? CR_TELEGRAM_URL : '',
		'https://www.instagram.com/cardsrift/',
	]));

	$schema = [
		'@context'    => 'https://schema.org',
		'@type'       => 'Organization',
		'name'        => get_bloginfo('name'),
		'url'         => home_url('/'),
		'description' => cr_seo_default_description(),
	];
	if ($logo_url) {
		$schema['logo'] = $logo_url;
	}
	if ($same_as) {
		$schema['sameAs'] = $same_as;
	}

	echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
}

/**
 * WebSite + SearchAction (solo in home): è il prerequisito della casella di ricerca
 * nei risultati Google. Lo generava WooCommerce su `woocommerce_before_main_content`,
 * hook che il tema non chiama più da quando ha i suoi archivi.
 */
add_action('wp_head', 'cr_seo_website_schema', 3);
function cr_seo_website_schema()
{
	if (!is_front_page()) {
		return;
	}
	$schema = [
		'@context'        => 'https://schema.org',
		'@type'           => 'WebSite',
		'name'            => get_bloginfo('name'),
		'url'             => home_url('/'),
		'potentialAction' => [
			'@type'       => 'SearchAction',
			'target'      => ['@type' => 'EntryPoint', 'urlTemplate' => home_url('/?s={search_term_string}')],
			'query-input' => 'required name=search_term_string',
		],
	];
	echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
}

/**
 * BreadcrumbList JSON-LD da un array di briciole.
 * Accetta sia ['name'=>,'url'=>] (PDP) sia ['label'=>,'url'=>] (listati), che è la
 * forma già usata da $cr_l_crumbs: così i template non devono convertire nulla.
 * L'ultima voce ha url vuoto (pagina corrente) ed esce senza `item`, come da spec.
 *
 * @param array $crumbs
 */
function cr_breadcrumb_schema($crumbs)
{
	$items = [];
	foreach ((array) $crumbs as $c) {
		$name = $c['name'] ?? ($c['label'] ?? '');
		if ($name === '') {
			continue;
		}
		$item = ['@type' => 'ListItem', 'position' => count($items) + 1, 'name' => $name];
		if (!empty($c['url'])) {
			$item['item'] = $c['url'];
		}
		$items[] = $item;
	}
	if (!$items) {
		return;
	}
	echo '<script type="application/ld+json">' . wp_json_encode([
		'@context'        => 'https://schema.org/',
		'@type'           => 'BreadcrumbList',
		'itemListElement' => $items,
	], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
}

/**
 * Sitemap XML: fuori gli URL che non servono mai contenuto proprio.
 *
 * Gli archivi grezzi di product_cat/product_tag li redirige sempre cr_guard_no_mixed()
 * (routing.php) verso le rotte pulite /{gioco}/{tipo}/, e carrello/checkout/account
 * sono noindex: elencarli spreca crawl budget e riempie Search Console di avvisi
 * "pagina con reindirizzamento" che coprono i problemi veri.
 */
add_filter('wp_sitemaps_taxonomies', 'cr_seo_sitemap_taxonomies');
function cr_seo_sitemap_taxonomies($taxonomies)
{
	unset($taxonomies['product_cat'], $taxonomies['product_tag']);
	return $taxonomies;
}

add_filter('wp_sitemaps_posts_query_args', 'cr_seo_sitemap_posts_args', 10, 2);
function cr_seo_sitemap_posts_args($args, $post_type)
{
	if ($post_type !== 'page' || !function_exists('wc_get_page_id')) {
		return $args;
	}
	$exclude = array_filter([
		wc_get_page_id('cart'),
		wc_get_page_id('checkout'),
		wc_get_page_id('myaccount'),
		wc_get_page_id('shop'),
	], function ($id) {
		return $id > 0;
	});
	if ($exclude) {
		$args['post__not_in'] = array_merge($args['post__not_in'] ?? [], $exclude);
	}
	return $args;
}
