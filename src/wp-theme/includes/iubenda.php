<?php

/**
 * IUBENDA — banner del consenso (Privacy Controls & Cookie Solution) + informative.
 *
 * Tre pezzi, e stanno insieme di proposito:
 *
 *   1. lo script del banner, in cima al <head> (lo stampa header.php con
 *      cr_iubenda_banner()). Da lì iubenda può fermare gli script che installano
 *      tracciatori PRIMA che partano: più in basso — o con `async` — quelli che
 *      dovrebbe bloccare sono già andati, e il blocco preventivo diventa finto.
 *
 *   2. iubenda.js (cdn), che trasforma i link con classe `.iubenda-embed` nella
 *      finestra dell'informativa. Serve solo al clic, quindi va in coda e async;
 *      viene caricato UNA volta sola da qui, non con lo snippet inline che
 *      iubenda dà da incollare accanto a ogni link (uno per link = due copie).
 *
 *   3. i link, costruiti da cr_iubenda_link(): l'ID del sito vive in
 *      includes/config.php, così cambiarlo non vuol dire rincorrerlo nei template.
 *
 * ⚠️ Gli ID sono quelli del sito CardsRift su iubenda. Il banner mostra e blocca
 * ciò che è configurato NEL PANNELLO iubenda, non qui: se al checkout sparissero i
 * bottoni PayPal o il campo carta, è il blocco preventivo di iubenda che li ha
 * classificati come tracciamento — si sistema nel pannello (servizi necessari),
 * non nel tema.
 */

/**
 * URL di un documento iubenda. $doc: 'privacy' | 'cookie'.
 * Stringa vuota se l'ID non è configurato: chi chiama non stampa il link.
 */
function cr_iubenda_url($doc = 'privacy')
{
	$id = defined('CR_IUBENDA_SITE_ID') ? CR_IUBENDA_SITE_ID : '';
	if (!$id) {
		return '';
	}
	$base = 'https://www.iubenda.com/privacy-policy/' . $id;
	return ('cookie' === $doc) ? $base . '/cookie-policy' : $base;
}

/**
 * Link a un'informativa, che si apre nella finestra sovrapposta di iubenda.
 *
 * ⚠️ `iubenda-nostyle` non è decorativo: senza, iubenda sostituisce il nostro
 * testo con il suo bottone bianco: nel footer scuro è un rettangolo fuori posto.
 * `iubenda-noiframe` fa caricare l'informativa nella pagina invece che in un
 * iframe (una richiesta in meno, e niente barra di scorrimento annidata).
 *
 * @param string $doc   'privacy' | 'cookie'
 * @param string $label testo del link, già tradotto
 * @param string $class classi aggiuntive (aspetto del contesto in cui sta)
 */
function cr_iubenda_link($doc, $label, $class = '')
{
	$url = cr_iubenda_url($doc);
	if (!$url) {
		return '';
	}
	return sprintf(
		'<a href="%s" title="%s" class="iubenda-nostyle iubenda-noiframe iubenda-embed %s">%s</a>',
		esc_url($url),
		esc_attr(('cookie' === $doc) ? 'Cookie Policy' : 'Privacy Policy'),
		esc_attr($class),
		esc_html($label)
	);
}

/**
 * Script del banner. Va chiamato in cima al <head> — vedi nota 1 in testa al file.
 * Niente `async` e niente `defer`: è il caso raro in cui bloccare il parser è il
 * comportamento voluto.
 */
function cr_iubenda_banner()
{
	if (!defined('CR_IUBENDA_WIDGET_ID') || !CR_IUBENDA_WIDGET_ID) {
		return;
	}
?>
	<script src="https://embeds.iubenda.com/widgets/<?= esc_attr(CR_IUBENDA_WIDGET_ID); ?>.js"></script>
<?php
}

/**
 * iubenda.js: apre le informative dei link `.iubenda-embed` (footer, checkout).
 * In coda alla pagina e async — al primo disegno non serve a nessuno.
 */
add_action('wp_enqueue_scripts', 'cr_iubenda_embed_script');
function cr_iubenda_embed_script()
{
	if (!cr_iubenda_url()) {
		return;
	}
	wp_enqueue_script('iubenda-embed', 'https://cdn.iubenda.com/iubenda.js', array(), null, true);
	wp_script_add_data('iubenda-embed', 'strategy', 'async');
}

/**
 * Informativa privacy di WooCommerce (checkout e registrazione).
 *
 * WooCommerce sostituisce il segnaposto [privacy_policy] con un link alla pagina
 * WordPress impostata come informativa; qui l'informativa sta su iubenda, quella
 * pagina non esiste e WooCommerce lascia le parole "informativa sulla privacy"
 * come testo morto, proprio nel punto in cui il link conta di più. Riattacchiamo
 * il link a iubenda, lasciando stare tutto il resto della frase (che resta
 * modificabile in WooCommerce → Impostazioni → Avanzate).
 */
add_filter('woocommerce_get_privacy_policy_text', 'cr_iubenda_wc_privacy_text', 10, 2);
function cr_iubenda_wc_privacy_text($text, $type)
{
	// Se un giorno esistesse davvero una pagina WordPress, il link lo fa già WooCommerce.
	if (!$text || (function_exists('wc_privacy_policy_page_id') && wc_privacy_policy_page_id())) {
		return $text;
	}
	$url = cr_iubenda_url('privacy');
	if (!$url) {
		return $text;
	}
	// La stessa __() che WooCommerce ha appena inserito al posto del segnaposto:
	// tradotta o no, le due stringhe coincidono sempre.
	$label = __('privacy policy', 'woocommerce');
	$link  = sprintf(
		'<a href="%s" title="Privacy Policy" class="woocommerce-privacy-policy-link iubenda-nostyle iubenda-noiframe iubenda-embed">%s</a>',
		esc_url($url),
		esc_html($label)
	);
	return str_replace($label, $link, $text);
}
