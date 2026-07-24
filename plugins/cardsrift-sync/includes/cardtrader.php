<?php

/**
 * CARDTRADER — client API (Full API v2) + normalizzazione delle inserzioni.
 *
 * Import delle MIE inserzioni CardTrader (/products/export) come prodotti WooCommerce, riusando lo
 * stesso "record" e la stessa pipeline dell'import Cardmarket (parser → crs_import_row).
 *
 * Auth: Bearer token dai settings del profilo CardTrader, salvato nell'opzione crs_ct_token
 *       (nel DB del sito, MAI nel repo). Base: https://api.cardtrader.com/api/v2.
 * Rate limit: 200 req / 10s (combinato). Doc: docs/catalogo-import.md
 */

if (!defined('ABSPATH')) {
	exit;
}

const CRS_CT_BASE      = 'https://api.cardtrader.com/api/v2';
const CRS_CT_TOKEN_OPT = 'crs_ct_token';

/** Token API configurato ('' se assente). Vive in un'opzione, mai nel codice. */
function crs_ct_token()
{
	return trim((string) get_option(CRS_CT_TOKEN_OPT, ''));
}

/** True se un token è configurato. */
function crs_ct_configured()
{
	return crs_ct_token() !== '';
}

/** Sbuccia le risposte-lista che alcuni endpoint incapsulano in {"array":[...]}. */
function crs_ct_arr($d)
{
	if (is_array($d) && isset($d['array']) && is_array($d['array'])) {
		return $d['array'];
	}
	return is_array($d) ? $d : [];
}

/**
 * GET verso l'API CardTrader. Sicura: HTTPS, timeout, Bearer, gestione 401/403/429.
 * @return array{0:bool,1:mixed,2:string} [ok, data|null, error]
 */
function crs_ct_get($path, $params = [], $timeout = 20)
{
	if (crs_ct_token() === '') {
		return [false, null, 'Token API non configurato.'];
	}
	$url = CRS_CT_BASE . '/' . ltrim($path, '/');
	if ($params) {
		$url = add_query_arg($params, $url);
	}
	return crs_ct_parse(wp_safe_remote_get($url, [
		'timeout' => (int) $timeout,
		'headers' => crs_ct_headers(),
	]));
}

/** Chiamata di SCRITTURA (POST/PUT/DELETE) con corpo JSON. Ritorna [ok, data, err]. */
function crs_ct_send($method, $path, $body = null, $timeout = 30)
{
	if (crs_ct_token() === '') {
		return [false, null, 'Token API non configurato.'];
	}
	$args = [
		'method'  => strtoupper($method),
		'timeout' => (int) $timeout,
		'headers' => crs_ct_headers(true),
	];
	if ($body !== null) {
		$args['body'] = wp_json_encode($body);
	}
	$res = crs_ct_parse(wp_safe_remote_request(CRS_CT_BASE . '/' . ltrim($path, '/'), $args));
	usleep(70000); // pacing scritture (~14/s) → sotto il limite globale 200/10s anche in raffica di PUT/POST (M3)
	return $res;
}

/** Header comuni (Bearer + Accept; Content-Type JSON per le scritture). */
function crs_ct_headers($json_body = false)
{
	$h = ['Authorization' => 'Bearer ' . crs_ct_token(), 'Accept' => 'application/json'];
	if ($json_body) {
		$h['Content-Type'] = 'application/json';
	}
	return $h;
}

/** Interpreta una risposta wp_remote_* → [ok, data, err]. Sui non-2xx include il corpo d'errore API. */
function crs_ct_parse($resp)
{
	if (is_wp_error($resp)) {
		return [false, null, $resp->get_error_message()];
	}
	$code = (int) wp_remote_retrieve_response_code($resp);
	$body = (string) wp_remote_retrieve_body($resp);
	if ($code === 401 || $code === 403) {
		return [false, null, "Autenticazione rifiutata ($code): token mancante, scaduto o senza accesso API."];
	}
	if ($code === 429) {
		return [false, null, 'Rate limit superato (429): riprova tra qualche secondo.'];
	}
	$data = json_decode($body, true);
	if ($code < 200 || $code >= 300) {
		$msg = is_array($data) ? wp_json_encode($data) : $body;
		return [false, $data, "HTTP $code: " . mb_substr((string) $msg, 0, 400)];
	}
	if ($data === null && trim($body) !== 'null') {
		return [false, null, 'Risposta non-JSON dall\'API.'];
	}
	return [true, $data, ''];
}

/** GET /info — profilo autenticato (test di connessione). */
function crs_ct_info()
{
	return crs_ct_get('info');
}

/** GET /expansions — tutte le espansioni (id, game_id, code, name). */
function crs_ct_expansions()
{
	return crs_ct_get('expansions');
}

/**
 * Mappa expansion_id -> ['code'=>…, 'name'=>…], in cache 12h (le espansioni cambiano di rado).
 * Serve a tradurre l'expansion_id di un'inserzione in slug+label del termine pa_espansione.
 */
function crs_ct_expansion_map()
{
	$cached = get_transient('crs_ct_exp_v2');
	if (is_array($cached)) {
		return $cached;
	}

	list($ok, $data) = crs_ct_expansions();
	$map = [];
	if ($ok) {
		foreach (crs_ct_arr($data) as $e) {
			if (isset($e['id'])) {
				$map[(int) $e['id']] = [
					'code'    => (string) ($e['code'] ?? ''),
					'name'    => (string) ($e['name'] ?? ''),
					'game_id' => (int) ($e['game_id'] ?? 0),
				];
			}
		}
		set_transient('crs_ct_exp_v2', $map, 12 * HOUR_IN_SECONDS);
	}
	return $map;
}

/** GET /products/export — le MIE inserzioni (opz. filtro blueprint_id/expansion_id). Timeout largo: può essere corposo. */
function crs_ct_products($params = [])
{
	return crs_ct_get('products/export', $params, 45);
}

/**
 * Mappa category_id -> ['game_id'=>…, 'name'=>…] da GET /categories, cache 12h (cambiano di rado).
 * Serve a classificare un blueprint in singole/sealed/accessori (crs_ct_category_kind).
 * Ritorna NULL su errore API/token (≠ [] che sarebbe "nessuna categoria"): così una /categories in errore
 * NON viene scambiata per "nessun keyword combacia" e il chiamante ritenta invece di mis-classificare.
 */
function crs_ct_categories_map()
{
	$cached = get_transient('crs_ct_cats_v1');
	if (is_array($cached)) {
		return $cached;
	}
	list($ok, $data) = crs_ct_get('categories');
	if (!$ok) {
		return null; // errore API/token → il chiamante ritenta, non conclude "categoria ignota"
	}
	$map = [];
	foreach (crs_ct_arr($data) as $c) {
		if (isset($c['id'])) {
			$map[(int) $c['id']] = [
				'game_id' => (int) ($c['game_id'] ?? 0),
				'name'    => (string) ($c['name'] ?? ''),
			];
		}
	}
	set_transient('crs_ct_cats_v1', $map, 12 * HOUR_IN_SECONDS);
	return $map;
}

/**
 * Classifica una categoria CardTrader nel nostro `tipo`: 'singole' | 'sealed' | 'accessori'.
 * Deterministico dai NOMI reali dell'endpoint /categories (stabili): "…Single Card"/"…Singles" = singola;
 * booster/box set/bundle/blister/display/complete set/starter|precon deck/tin/prerelease/fat pack = sealed;
 * tutto il resto (sleeves, playmats, deck box, album, dadi, storage…) = accessori.
 * @return string|null  '' = categoria assente/ignota (fetch ok, nessun match) · NULL = errore API (ritenta).
 * NB: i token sealed sono multi-parola apposta ("box set", non "box") per non pescare "Deck Boxes" ecc.
 */
function crs_ct_category_kind($category_id)
{
	$category_id = (int) $category_id;
	if ($category_id < 1) {
		return '';
	}
	$map = crs_ct_categories_map();
	if ($map === null) {
		return null; // errore API su /categories → il chiamante skippa e ritenta, niente mis-classificazione
	}
	$cat = $map[$category_id] ?? null;
	if (!$cat) {
		return '';
	}
	$n = strtolower($cat['name']);
	if (strpos($n, 'single') !== false) {
		return 'singole';
	}
	foreach (['booster', 'box set', 'boxed set', 'bundle', 'fat pack', 'blister', 'display', 'complete set', 'starter deck', 'preconstructed', 'prerelease', 'tin'] as $kw) {
		if (strpos($n, $kw) !== false) {
			return 'sealed';
		}
	}
	return 'accessori';
}

/**
 * Normalizza un'inserzione CardTrader (/products/export) nel record COMUNE dell'importer,
 * lo stesso shape prodotto dal parser Cardmarket (name/expansion/condition/lang/foil/price/qty/image_url),
 * più i riferimenti CardTrader (ct_product_id, ct_blueprint_id). $expmap = crs_ct_expansion_map().
 *
 * NB: qui NON si costruisce lo SKU né si sceglie l'identità prodotto: quello è compito della fase import
 * (ancoraggio a _cardmarket_id via card_market_ids del blueprint). Questo helper è puro mapping campi.
 */
function crs_ct_normalize_product($p, $expmap = [])
{
	$props = (isset($p['properties_hash']) && is_array($p['properties_hash'])) ? $p['properties_hash'] : [];

	// lingua: la chiave dipende dal gioco (mtg_language, pokemon_language, …) → prendo la prima *_language
	$lang_code = '';
	foreach ($props as $k => $v) {
		if ($k === 'language' || substr((string) $k, -9) === '_language') {
			$lang_code = $v;
			break;
		}
	}

	$exp_id = (int) ($p['expansion_id'] ?? 0);
	$exp    = $expmap[$exp_id] ?? ['code' => '', 'name' => ''];

	// foto caricata dal venditore (per carte gradate/particolari): tenuta a parte come possibile
	// override futuro. L'immagine di catalogo (image_url) la riempie il blueprint CDN (crs_ct_enrich_from_blueprint).
	$photo = !empty($p['uploaded_images'][0]['url']) ? (string) $p['uploaded_images'][0]['url'] : '';

	$price = isset($p['price_cents']) ? round(((int) $p['price_cents']) / 100, 2) : '';

	return [
		'ct_product_id'   => (int) ($p['id'] ?? 0),
		'ct_blueprint_id' => (int) ($p['blueprint_id'] ?? 0),
		'cardmarket_id'   => '', // riempito dal blueprint (card_market_ids): ponte con l'identità Cardmarket
		'name'            => trim((string) ($p['name_en'] ?? '')),
		'expansion'       => $exp['name'],
		'expansion_code'  => $exp['code'],
		'condition'       => strtoupper(crs_ct_condition_to_slug($props['condition'] ?? '')),
		'lang'            => crs_ct_lang_to_slug($lang_code),
		'foil'            => crs_ct_foil_to_slug($props),
		'price'           => $price,
		'qty'             => max(0, (int) ($p['quantity'] ?? 0)),
		'image_url'       => '',      // immagine di catalogo dal blueprint CDN
		'ct_photo'        => $photo,  // foto del venditore (override opzionale futuro)
	];
}

/** Normalizza e VALIDA un URL immagine CardTrader ('//host'→https, '/path'→dominio). '' se non è un http(s) pulito. */
function crs_ct_image_url($u)
{
	$u = trim((string) $u);
	if ($u === '') {
		return '';
	}
	if (strpos($u, '//') === 0) {
		$u = 'https:' . $u;
	} elseif ($u[0] === '/') {
		$u = 'https://www.cardtrader.com' . $u;
	}
	// deve essere un URL http(s) pulito (niente virgolette/spazi/angoli) → chiude alla fonte il sink XSS (M1)
	return preg_match('~^https?://[^\s"\'<>]+$~', $u) ? $u : '';
}

/**
 * Blueprint di un'espansione: mappa blueprint_id -> ['image'=>URL CDN, 'cm_ids'=>[card_market_ids]].
 * In cache 6h per espansione. Ritorna NULL su errore API/token (≠ [] che significa "set senza blueprint"):
 * così il chiamante NON marca la carta come "non agganciata" per un semplice guasto di rete (fresh-M-3).
 */
function crs_ct_blueprint_map($expansion_id)
{
	$expansion_id = (int) $expansion_id;
	if ($expansion_id < 1) {
		return [];
	}
	$key    = 'crs_ct_bpm2_' . $expansion_id;
	$cached = get_transient($key);
	if (is_array($cached)) {
		return $cached;
	}

	list($ok, $data) = crs_ct_get('blueprints/export', ['expansion_id' => $expansion_id], 45);
	if (!$ok) {
		return null; // errore API/token → il chiamante ritenta, non conclude "unmatched"
	}
	$map = [];
	foreach (crs_ct_arr($data) as $b) {
		if (isset($b['id'])) {
			// il campo `image` è un oggetto con le varianti: url (piena 672×936), show, preview, social
			$img = (isset($b['image']) && is_array($b['image'])) ? $b['image'] : [];
			$map[(int) $b['id']] = [
				'name'        => (string) ($b['name'] ?? ''),
				'version'     => (string) ($b['version'] ?? ''),
				'category_id' => (int) ($b['category_id'] ?? 0), // → tipo (singole/sealed/accessori) via crs_ct_category_kind
				'image'       => crs_ct_image_url($img['preview']['url'] ?? ($b['image_url'] ?? '')),      // miniatura (listato)
				'image_full'  => crs_ct_image_url($img['url'] ?? str_replace('preview_', '', (string) ($b['image_url'] ?? ''))), // piena (scheda prodotto)
				'cm_ids'      => array_values(array_filter((array) ($b['card_market_ids'] ?? []))),
			];
		}
	}
	set_transient($key, $map, 6 * HOUR_IN_SECONDS);
	return $map;
}

/**
 * Arricchisce un record normalizzato coi dati del suo blueprint: immagine di catalogo CDN e
 * cardmarket_id (da card_market_ids → aggancio all'identità prodotto Cardmarket, se disponibile).
 * $bpmap = crs_ct_blueprint_map() dell'espansione (o unione di più).
 */
function crs_ct_enrich_from_blueprint($rec, $bpmap)
{
	$bp = is_array($bpmap) ? ($bpmap[(int) ($rec['ct_blueprint_id'] ?? 0)] ?? null) : null; // $bpmap può essere null (errore API)
	if ($bp) {
		if (($rec['image_url'] ?? '') === '' && !empty($bp['image'])) {
			$rec['image_url'] = $bp['image'];
		}
		if (empty($rec['cardmarket_id']) && !empty($bp['cm_ids'][0])) {
			$rec['cardmarket_id'] = (string) $bp['cm_ids'][0];
		}
	}
	return $rec;
}
