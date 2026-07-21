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

	// Mappa attributi (aggiunge espansione solo se disponibile).
	$attr_map = [
		'condizione' => strtolower($rec['condition']),
		'lingua'     => strtolower($lang),
		'foil'       => 'normale',
	];
	if ($esp_code) {
		$attr_map['espansione'] = $esp_code;
	}

	if ($creating) {
		$product->set_name($rec['name']);
		$product->set_sku($sku);
		$product->set_status('publish');
		$product->set_catalog_visibility('visible');
		// attributi impostati PRIMA del save → un solo save (niente doppio giro)
		$attrs = crs_build_attributes($attr_map);
		if ($attrs) {
			$product->set_attributes($attrs);
		}
	} elseif ($esp_code) {
		// backfill: aggiunge SOLO l'espansione se manca (un re-import sistema le carte già dentro),
		// senza toccare gli altri attributi eventualmente modificati a mano.
		$existing = $product->get_attributes();
		if (empty($existing['pa_espansione'])) {
			$add = crs_build_attributes(['espansione' => $esp_code]);
			if ($add) {
				$product->set_attributes(array_merge($existing, $add));
			}
		}
	}

	if ($rec['price'] !== '') {
		$product->set_regular_price((string) $rec['price']);
	}
	$product->set_manage_stock(true);
	$product->set_stock_quantity((int) $rec['qty']);
	$product->set_stock_status((int) $rec['qty'] > 0 ? 'instock' : 'outofstock');

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
	} elseif ($esp_code) {
		crs_assign_attr_terms($pid, ['espansione' => $esp_code]); // backfill del filtro espansione
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
 * ⚠️ Il file "full" deve rappresentare lo stock COMPLETO di quel gioco+tipo.
 * @return int quanti prodotti azzerati
 */
function crs_finalize_sold($job, $present)
{
	if ($job['mode'] !== 'full') {
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

	$zeroed = 0;
	foreach ($ids as $pid) {
		$p = wc_get_product($pid);
		if (!$p || isset($present[$p->get_sku()])) {
			continue; // ancora presente nell'export → non toccare
		}
		if ($p->get_stock_status() === 'outofstock' && (int) $p->get_stock_quantity() === 0) {
			continue; // già a zero
		}
		$p->set_stock_quantity(0);
		$p->set_stock_status('outofstock');
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
