<?php

/**
 * IMPORTER — upsert di un record Cardmarket (già aggregato per SKU) in un prodotto WooCommerce semplice.
 * Idempotente. Un solo save() per prodotto. In "full" azzera lo stock delle carte sparite dall'export.
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Importa un record aggregato. $opts = [game, tipo, lang, mode(full|add), images(bool), dry_run(bool)].
 * @return array{status:string,sku:string,id?:int,msg?:string}
 */
function crs_import_row($rec, $opts)
{
	if (empty($rec['cardmarket_id']) || empty($rec['name'])) {
		return ['status' => 'error', 'sku' => $rec['sku'] ?? '', 'msg' => 'record senza id/nome'];
	}

	$sku = $rec['sku'];
	$lang = !empty($rec['lang']) ? $rec['lang'] : $opts['lang']; // per-riga dall'export, fallback al default del form
	$existing_id = wc_get_product_id_by_sku($sku);

	if ($existing_id && $opts['mode'] === 'add') {
		return ['status' => 'skipped', 'sku' => $sku, 'id' => $existing_id];
	}
	if (!empty($opts['dry_run'])) {
		return ['status' => $existing_id ? 'updated' : 'created', 'sku' => $sku, 'id' => (int) $existing_id];
	}

	$product = $existing_id ? wc_get_product($existing_id) : new WC_Product_Simple();
	if (!$product) {
		return ['status' => 'error', 'sku' => $sku, 'msg' => 'prodotto non caricato'];
	}
	$creating = !$existing_id;

	// Espansione: termine pa_espansione con slug = codice set (stabile tra le lingue),
	// label = nome espansione. Alimenta il fatto in PDP E il filtro del listato.
	$esp_code = !empty($rec['expansion_code']) ? sanitize_title($rec['expansion_code']) : '';
	$esp_name = trim((string) ($rec['expansion'] ?? ''));
	if ($esp_code && $esp_name) {
		crs_ensure_terms(wc_attribute_taxonomy_name('espansione'), [$esp_code => $esp_name]);
	} else {
		$esp_code = ''; // senza nome non creiamo un termine muto
	}

	// Rarità: attributo di CATALOGO (filtro listato + PDP), NON parte dell'identità/SKU/prezzo (è propria della
	// carta). Slug dal valore, label = valore. Valori per-gioco (Magic: Rare/Mythic…; Pokémon: Holo/Ultra/Secret Rare).
	$rar_name = trim((string) ($rec['rarity'] ?? ''));
	$rar_slug = $rar_name !== '' ? sanitize_title($rar_name) : '';
	if ($rar_slug) {
		crs_ensure_terms(wc_attribute_taxonomy_name('rarita'), [$rar_slug => $rar_name]);
	}

	// Mappa attributi (aggiunge espansione/rarità solo se disponibili).
	$attr_map = [
		'condizione' => strtolower($rec['condition']),
		'lingua'     => strtolower($lang),
		'foil'       => $rec['foil'] ?? 'normale', // dal CSV (colonna ReverseHolo) → identità coerente col pull
	];
	if ($esp_code) {
		$attr_map['espansione'] = $esp_code;
	}
	if ($rar_slug) {
		$attr_map['rarita'] = $rar_slug;
	}

	if ($creating) {
		$product->set_name($rec['name']);
		$product->set_sku($sku);
		// nasce in BOZZA: si pubblica quando ha un prezzo definitivo (pull CardTrader o _crs_price_pinned).
		// Vedi ciclo di vita in sync.php (crs_ct_pull). I prodotti fuori dal flusso CardTrader
		// vanno pubblicati fissando il prezzo a mano.
		$product->set_status('draft');
		$product->set_catalog_visibility('visible');
		// attributi impostati PRIMA del save → un solo save (niente doppio giro)
		$attrs = crs_build_attributes($attr_map);
		if ($attrs) {
			$product->set_attributes($attrs);
		}
	} else {
		// backfill: aggiunge SOLO gli attributi di catalogo mancanti (espansione, rarità) — un re-import sistema
		// le carte già dentro — senza toccare gli altri attributi eventualmente modificati a mano.
		$existing = $product->get_attributes();
		$bf = [];
		if ($esp_code && empty($existing['pa_espansione'])) {
			$bf['espansione'] = $esp_code;
		}
		if ($rar_slug && empty($existing['pa_rarita'])) {
			$bf['rarita'] = $rar_slug;
		}
		if ($bf) {
			$add = crs_build_attributes($bf);
			if ($add) {
				$product->set_attributes(array_merge($existing, $add));
			}
		}
	}

	// Il PREZZO lo possiede CardTrader (autopricer → pull): l'import lo imposta SOLO alla creazione
	// (prezzo provvisorio per il primo push). Sui prodotti esistenti aggiorna solo lo STOCK, così un
	// re-import NON sovrascrive il prezzo di mercato riportato dal pull né i prezzi fissati a mano.
	if ($creating && $rec['price'] !== '') {
		$product->set_regular_price((string) $rec['price']);
	}

	// STOCK — modello DELTA multi-canale: l'import applica la VARIAZIONE di Cardmarket allo stock WC,
	// NON lo sovrascrive. Così un restock su Cardmarket si somma e le vendite fatte sul sito/CardTrader
	// non vengono cancellate. Ancora = _cm_stock (ultima qty Cardmarket vista).
	$product->set_manage_stock(true);
	$new_cm = (int) $rec['qty'];
	if ($creating) {
		$stock_final = $new_cm; // prodotto nuovo: lo stock parte dall'export
		$product->set_stock_quantity($stock_final);
	} elseif (!metadata_exists('post', $existing_id, CRS_META_CM_STOCK)) {
		// PRIMO import dopo il passaggio a delta: registro solo la baseline, NON tocco lo stock WC
		// (lo assumo già corretto) → non posso cancellare nessuna vendita già avvenuta.
		$stock_final = (int) $product->get_stock_quantity();
	} else {
		$last_cm     = (int) $product->get_meta(CRS_META_CM_STOCK);
		$stock_final = max(0, (int) $product->get_stock_quantity() + ($new_cm - $last_cm));
		$product->set_stock_quantity($stock_final);
	}
	$product->update_meta_data(CRS_META_CM_STOCK, $new_cm); // aggiorna l'ancora del delta
	$product->set_stock_status($stock_final > 0 ? 'instock' : 'outofstock');
	// Visibilità a stock 0: la SINGOLA esce dal catalogo (ricompare al restock); il SEALED resta visibile "Esaurito".
	if (($opts['tipo'] ?? '') === 'singole') {
		$product->set_catalog_visibility($stock_final > 0 ? 'visible' : 'hidden');
	} else {
		$product->set_catalog_visibility('visible');
	}

	$product->update_meta_data(CRS_META_CM_ID, $rec['cardmarket_id']);
	$product->update_meta_data(CRS_META_CM_ARTICLE, $rec['article_id']);
	if ($rec['expansion']) {
		$product->update_meta_data(CRS_META_CM_EXP, $rec['expansion']);
	}
	if ($rec['product_url']) {
		$product->update_meta_data(CRS_META_CM_URL, $rec['product_url']);
	}
	if ($rec['image_url']) {
		// se l'URL immagine cambia, butto la cache su disco così si ri-scarica la nuova
		if ($existing_id && $product->get_meta(CRS_META_CM_IMAGE) !== $rec['image_url']) {
			crs_img_cache_clear($existing_id);
		}
		$product->update_meta_data(CRS_META_CM_IMAGE, $rec['image_url']);
	}

	$pid = $product->save();
	if (!$pid) {
		return ['status' => 'error', 'sku' => $sku, 'msg' => 'save fallito'];
	}

	// categorie: gioco + tipo
	$cat_ids = [];
	foreach ([$opts['game'], $opts['tipo']] as $cslug) {
		$t = get_term_by('slug', $cslug, 'product_cat');
		if ($t) {
			$cat_ids[] = (int) $t->term_id;
		}
	}
	if ($cat_ids) {
		wp_set_object_terms($pid, $cat_ids, 'product_cat');
	}

	// termini attributo (relazioni tassonomiche) → alimentano i filtri del listato
	if ($creating) {
		crs_assign_attr_terms($pid, $attr_map);
	} else {
		$bf = [];
		if ($esp_code) {
			$bf['espansione'] = $esp_code;
		}
		if ($rar_slug) {
			$bf['rarita'] = $rar_slug; // backfill del filtro rarità
		}
		if ($bf) {
			crs_assign_attr_terms($pid, $bf);
		}
	}

	if ($creating) {
		if (!empty($opts['images']) && $rec['image_url'] && !$product->get_image_id()) {
			$att = crs_sideload_image($rec['image_url'], $pid);
			if (is_wp_error($att)) {
				error_log('[cardsrift-sync] sideload immagine fallito (' . $sku . '): ' . $att->get_error_message());
			} elseif ($att) {
				set_post_thumbnail($pid, $att);
			}
		}
	}

	return ['status' => $creating ? 'created' : 'updated', 'sku' => $sku, 'id' => $pid];
}

/** Costruisce gli oggetti WC_Product_Attribute (globali) da impostare sul prodotto prima del save. */
function crs_build_attributes($map)
{
	$attrs = [];
	foreach ($map as $slug => $term_slug) {
		if (!$term_slug) {
			continue;
		}
		$tax  = wc_attribute_taxonomy_name($slug);
		$term = get_term_by('slug', $term_slug, $tax);
		if (!$term) {
			continue;
		}
		$a = new WC_Product_Attribute();
		$a->set_id(wc_attribute_taxonomy_id_by_name($slug));
		$a->set_name($tax);
		$a->set_options([(int) $term->term_id]);
		$a->set_visible(true);
		$a->set_variation(false);
		$attrs[$tax] = $a;
	}
	return $attrs;
}

/** Assegna i termini degli attributi al prodotto (relazioni tassonomiche, per i filtri). */
function crs_assign_attr_terms($pid, $map)
{
	foreach ($map as $slug => $term_slug) {
		if (!$term_slug) {
			continue;
		}
		$tax  = wc_attribute_taxonomy_name($slug);
		$term = get_term_by('slug', $term_slug, $tax);
		if ($term) {
			wp_set_object_terms($pid, [(int) $term->term_id], $tax);
		}
	}
}

/**
 * FINALIZE "venduti" (solo modalità full): azzera lo stock dei prodotti dello stesso gioco+tipo,
 * importati da noi (_cardmarket_id), il cui SKU NON è più nell'export corrente.
 * $present = crs_ndjson_present(): ['skus'=>…, 'tuples'=>…]. Azzera SOLO dentro le varianti
 * (condizione+lingua+foil) che l'export copre davvero → un export filtrato non tocca le altre (#2).
 * ⚠️ Il file "full" deve rappresentare lo stock COMPLETO di quel gioco+tipo per le varianti che contiene.
 * @return int quanti prodotti azzerati
 */
function crs_finalize_sold($job, $present)
{
	if ($job['mode'] !== 'full') {
		return 0;
	}
	$skus   = is_array($present) ? ($present['skus'] ?? []) : [];
	$tuples = is_array($present) ? ($present['tuples'] ?? []) : [];
	// SICUREZZA: se l'elenco "presenti" è vuoto NON azzerare nulla. Copre il caso catastrofico in cui la GC
	// ha cancellato l'.ndjson (import > 24h) → $present vuoto → altrimenti azzererebbe l'INTERO gioco+tipo (H6c).
	if (empty($skus)) {
		error_log('[cardsrift-sync] finalize_sold SALTATO: elenco presenti vuoto (file .ndjson mancante/scaduto?).');
		return 0;
	}

	$ids = wc_get_products([
		'status'     => 'publish',
		'limit'      => -1,
		'return'     => 'ids',
		'tax_query'  => [
			'relation' => 'AND',
			['taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => [$job['game']]],
			['taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => [$job['tipo']]],
		],
		'meta_query' => [['key' => CRS_META_CM_ID, 'compare' => 'EXISTS']],
	]);
	crs_ct_prime_caches($ids); // legge i termini condizione/lingua/foil in poche query (N+1)

	// candidati = importati da noi, non più nell'export, MA nella stessa variante coperta dall'export, ancora in stock.
	// $in_scope = prodotti che stanno nelle varianti coperte dall'export → denominatore della soglia di sicurezza:
	// dev'essere la popolazione che un export completo di QUELLE varianti dovrebbe elencare, non l'intero gioco+tipo.
	$to_zero  = [];
	$in_scope = 0;
	foreach ($ids as $pid) {
		$p = wc_get_product($pid);
		if (!$p) {
			continue;
		}
		// variant-scoping: se l'export NON copre questa (condizione,lingua,foil), è un export filtrato → non toccare (#2)
		$vk = crs_variant_key(crs_first_term_slug($pid, 'pa_condizione'), crs_first_term_slug($pid, 'pa_lingua'), crs_first_term_slug($pid, 'pa_foil'));
		if (!isset($tuples[$vk])) {
			continue;
		}
		$in_scope++;
		if (isset($skus[$p->get_sku()])) {
			continue; // ancora presente nell'export → non toccare
		}
		if ($p->get_stock_status() === 'outofstock' && (int) $p->get_stock_quantity() === 0) {
			continue; // già a zero
		}
		$to_zero[] = $p;
	}

	// SOGLIA: se azzererebbe una quota enorme dei prodotti NELLE VARIANTI COPERTE, quasi certamente l'export è
	// parziale/corrotto (un full completo le elencherebbe quasi tutte) → abortisci invece di svuotare quella fetta
	// (H6b, ora denominato sullo scope della variante e non sull'intero gioco+tipo — #2).
	if ($in_scope > 20 && count($to_zero) > $in_scope * 0.6) {
		error_log(sprintf('[cardsrift-sync] finalize_sold ABORT: azzererebbe %d/%d prodotti in-scope (>60%%) — export "full" incompleto?', count($to_zero), $in_scope));
		return 0;
	}

	$hide   = ($job['tipo'] ?? '') === 'singole'; // singola esaurita → fuori catalogo; sealed resta visibile "Esaurito"
	$zeroed = 0;
	foreach ($to_zero as $p) {
		$p->set_stock_quantity(0);
		$p->set_stock_status('outofstock');
		$p->update_meta_data(CRS_META_CM_STOCK, 0); // ancora: Cardmarket ora ha 0 di questa carta
		if ($hide) {
			$p->set_catalog_visibility('hidden');
		}
		$p->save();
		$zeroed++;
	}
	return $zeroed;
}

/** Scarica un'immagine da URL e la allega al prodotto. Ritorna l'attachment id o WP_Error. */
function crs_sideload_image($url, $pid)
{
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	// L2: scarica solo da host Cardmarket (l'URL viene dal CSV) e solo http(s) → niente fetch verso host arbitrari
	$host = strtolower((string) wp_parse_url($url, PHP_URL_HOST));
	$scheme = strtolower((string) wp_parse_url($url, PHP_URL_SCHEME));
	if (!in_array($scheme, ['http', 'https'], true) || !($host === 'cardmarket.com' || substr($host, -15) === '.cardmarket.com')) {
		return new WP_Error('crs_bad_image_host', 'host immagine non consentito: ' . $host);
	}

	$tmp = download_url($url);
	if (is_wp_error($tmp)) {
		return $tmp;
	}
	$file = ['name' => basename(parse_url($url, PHP_URL_PATH)), 'tmp_name' => $tmp];
	$id = media_handle_sideload($file, $pid);
	if (is_wp_error($id)) {
		@unlink($tmp);
	}
	return $id;
}
