<?php

/**
 * DISPLAY — immagine di fallback dal catalogo Cardmarket, via proxy con cache su disco.
 *
 * Cardmarket blocca l'hotlinking (403 senza referer cardmarket.com), quindi l'URL diretto non
 * caricherebbe. Il proxy scarica lato server col referer giusto e mette in CACHE SU DISCO (file,
 * non allegati → zero righe DB). Precedenze: foto in evidenza manuale > immagine Cardmarket > placeholder.
 *
 * Robustezza: host allowlist con confine di dominio, wp_safe_remote_get (anti-SSRF), limite dimensione,
 * validazione che sia davvero un'immagine, scrittura atomica, lock anti-thundering-herd, negative-cache.
 */

if (!defined('ABSPATH')) {
	exit;
}

/** Percorsi della cache immagini su disco (nessun allegato, nessuna riga DB). */
function crs_img_cache($pid)
{
	$u = wp_upload_dir();
	$base = $u['basedir'] . '/cardsrift-sync/img-cache/' . (int) $pid;
	return [
		'file' => $base . '.jpg',
		'url'  => $u['baseurl'] . '/cardsrift-sync/img-cache/' . (int) $pid . '.jpg',
		'fail' => $base . '.fail',
		'tmp'  => $base . '.tmp',
		'lock' => $base . '.lock',
	];
}

/** Butta la cache immagine di un prodotto (usato quando l'URL sorgente cambia al re-import). */
function crs_img_cache_clear($pid)
{
	$c = crs_img_cache($pid);
	@unlink($c['file']);
	@unlink($c['fail']);
}

/** URL da mettere nell'<img>: file statico se in cache, '' se fallito di recente, altrimenti il proxy. */
function crs_cm_src($pid)
{
	$c = crs_img_cache($pid);
	if (file_exists($c['file'])) {
		return $c['url'];
	}
	if (file_exists($c['fail']) && (time() - filemtime($c['fail']) < HOUR_IN_SECONDS)) {
		return ''; // scaricamento fallito da poco → placeholder, niente ri-tentativi a raffica
	}
	return add_query_arg(['action' => 'crs_img', 'pid' => (int) $pid], admin_url('admin-ajax.php'));
}

/** È davvero un'immagine? (il content-type di Cardmarket è inaffidabile: controllo i byte) */
function crs_is_image($body)
{
	if ($body === '') {
		return false;
	}
	if (function_exists('getimagesizefromstring')) {
		return @getimagesizefromstring($body) !== false;
	}
	return substr($body, 0, 3) === "\xFF\xD8\xFF"                // jpeg
		|| substr($body, 0, 8) === "\x89PNG\r\n\x1a\n"           // png
		|| substr($body, 0, 4) === 'RIFF';                       // webp
}

/* ---- Immagine nelle griglie / raccoglitore / shop (WC_Product::get_image) ---- */
add_filter('woocommerce_product_get_image', 'crs_cm_fallback_image', 10, 6);
function crs_cm_fallback_image($html, $product, $size, $attr, $placeholder, $image)
{
	if (!$product || $product->get_image_id()) {
		return $html; // c'è una foto in evidenza → vince quella
	}
	$pid = $product->get_id();
	if (!get_post_meta($pid, CRS_META_CM_IMAGE, true)) {
		return $html;
	}
	$src = crs_cm_src($pid);
	if (!$src) {
		return $html; // fallito di recente → placeholder
	}

	$size_name = is_string($size) ? $size : 'woocommerce_thumbnail';
	$dims = function_exists('wc_get_image_size') ? wc_get_image_size($size) : [];
	$w = !empty($dims['width']) ? (int) $dims['width'] : 0;
	$h = !empty($dims['height']) ? (int) $dims['height'] : 0;

	return sprintf(
		'<img src="%s" alt="%s" class="attachment-%s size-%s crs-cm-image" %s%sloading="lazy" decoding="async" />',
		esc_url($src),
		esc_attr($product->get_name()),
		esc_attr($size_name),
		esc_attr($size_name),
		$w ? 'width="' . $w . '" ' : '',
		$h ? 'height="' . $h . '" ' : ''
	);
}

/* ---- Immagine principale della scheda prodotto (PDP) ---- */
add_filter('woocommerce_single_product_image_thumbnail_html', 'crs_cm_single_image', 10, 2);
function crs_cm_single_image($html, $thumbnail_id)
{
	if ($thumbnail_id) {
		return $html;
	}
	$product = wc_get_product(get_the_ID());
	if (!$product || $product->get_image_id() || !get_post_meta($product->get_id(), CRS_META_CM_IMAGE, true)) {
		return $html;
	}
	$src = crs_cm_src($product->get_id());
	if (!$src) {
		return $html;
	}
	return sprintf(
		'<div class="woocommerce-product-gallery__image--placeholder crs-cm-single"><img src="%s" alt="%s" class="wp-post-image crs-cm-image" /></div>',
		esc_url($src),
		esc_attr($product->get_name())
	);
}

/* ---- Proxy immagine (endpoint pubblico) ---- */
add_action('wp_ajax_crs_img', 'crs_img_proxy');
add_action('wp_ajax_nopriv_crs_img', 'crs_img_proxy');
function crs_img_proxy()
{
	$pid  = (int) ($_GET['pid'] ?? 0);
	$src  = $pid ? get_post_meta($pid, CRS_META_CM_IMAGE, true) : '';
	$host = $src ? strtolower((string) wp_parse_url($src, PHP_URL_HOST)) : '';

	// allowlist con confine di dominio (niente bypass tipo evilcardmarket.com)
	if (!$src || !($host === 'cardmarket.com' || substr($host, -15) === '.cardmarket.com')) {
		status_header(404);
		exit;
	}

	$c = crs_img_cache($pid);
	if (file_exists($c['file'])) {
		crs_img_stream($c['file']);
	}
	if (file_exists($c['fail']) && (time() - filemtime($c['fail']) < HOUR_IN_SECONDS)) {
		status_header(404);
		exit;
	}

	wp_mkdir_p(dirname($c['file']));

	// lock: collassa i miss concorrenti sullo stesso pid in un solo fetch
	$lock = fopen($c['lock'], 'c');
	if ($lock) {
		flock($lock, LOCK_EX);
		if (file_exists($c['file'])) { // un'altra richiesta l'ha appena scaricata
			flock($lock, LOCK_UN);
			fclose($lock);
			crs_img_stream($c['file']);
		}
	}

	$resp = wp_safe_remote_get($src, [
		'timeout'             => 12,
		'limit_response_size' => 8 * MB_IN_BYTES,
		'headers'             => ['Referer' => 'https://www.cardmarket.com/'],
	]);
	$ok   = !is_wp_error($resp) && wp_remote_retrieve_response_code($resp) === 200;
	$body = $ok ? wp_remote_retrieve_body($resp) : '';

	if (!crs_is_image($body)) {
		@touch($c['fail']); // negative-cache: non ri-martellare per un'ora
		if ($lock) {
			flock($lock, LOCK_UN);
			fclose($lock);
		}
		status_header(502);
		exit;
	}

	file_put_contents($c['tmp'], $body);
	@rename($c['tmp'], $c['file']); // scrittura atomica
	@unlink($c['fail']);
	if ($lock) {
		flock($lock, LOCK_UN);
		fclose($lock);
	}
	crs_img_stream($c['file']);
}

/** Serve un file immagine dalla cache con header di lunga durata. */
function crs_img_stream($file)
{
	$ct = 'image/jpeg';
	if (function_exists('mime_content_type')) {
		$m = @mime_content_type($file);
		if ($m) {
			$ct = $m;
		}
	}
	header('Content-Type: ' . $ct);
	header('Cache-Control: public, max-age=31536000, immutable');
	header('Content-Length: ' . filesize($file));
	readfile($file);
	exit;
}
