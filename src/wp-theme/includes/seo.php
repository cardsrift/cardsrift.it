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

/** URL canonico della vista corrente. */
function cr_seo_current_url()
{
	if (is_singular()) {
		$link = get_permalink();
		return $link ?: home_url('/');
	}
	return home_url('/');
}

/** <title>: usa la mappa se c'è, altrimenti lascia decidere WordPress. */
add_filter('document_title_parts', 'cr_seo_title_parts');
function cr_seo_title_parts($parts)
{
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
	return cr_seo_default_description();
}

/** Meta description, Open Graph e canonical, in testa. */
add_action('wp_head', 'cr_seo_head_meta', 2);
function cr_seo_head_meta()
{
	$desc  = trim((string) cr_seo_description());
	$title = wp_get_document_title();
	$url   = cr_seo_current_url();

	if ($desc) {
		echo '<meta name="description" content="' . esc_attr($desc) . '">' . "\n";
	}
	// canonical: lo emette già WordPress (rel_canonical) sulle pagine singole — non duplicare.
	echo '<meta property="og:type" content="website">' . "\n";
	echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '">' . "\n";
	echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
	if ($desc) {
		echo '<meta property="og:description" content="' . esc_attr($desc) . '">' . "\n";
	}
	echo '<meta property="og:url" content="' . esc_url($url) . '">' . "\n";
	echo '<meta property="og:locale" content="it_IT">' . "\n";
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
