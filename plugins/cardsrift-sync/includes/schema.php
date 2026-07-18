<?php

/**
 * SCHEMA CATALOGO — attributi globali + categorie prodotto, costruiti dai vocabolari del contratto.
 * Idempotente: rilanciabile senza duplicati.
 */

if (!defined('ABSPATH')) {
	exit;
}

/** Attributi globali (pa_*) e termini iniziali (dal contratto). */
function crs_schema_attributes()
{
	return [
		'condizione' => ['label' => 'Condizione', 'terms' => crs_conditions()],
		'lingua'     => ['label' => 'Lingua', 'terms' => crs_languages()],
		'foil'       => ['label' => 'Foil', 'terms' => crs_foils()],
		'espansione' => ['label' => 'Espansione', 'terms' => []], // popolato dall'import
	];
}

/** Crea/aggiorna attributi + termini + categorie, poi segna la versione. */
function crs_schema_ensure()
{
	foreach (crs_schema_attributes() as $slug => $conf) {
		$tax = crs_ensure_attribute($slug, $conf['label']);
		if ($tax && !empty($conf['terms'])) {
			crs_ensure_terms($tax, $conf['terms']);
		}
	}
	crs_ensure_cat_terms(crs_games());
	crs_ensure_cat_terms(crs_types());

	update_option('crs_schema_ver', CRS_SCHEMA_VER);
}

/** Crea l'attributo globale se manca e registra la tassonomia nel request corrente. */
function crs_ensure_attribute($slug, $label)
{
	$id = wc_attribute_taxonomy_id_by_name($slug);
	if (!$id) {
		$id = wc_create_attribute([
			'name'         => $label,
			'slug'         => $slug,
			'type'         => 'select',
			'order_by'     => 'menu_order',
			'has_archives' => false,
		]);
		if (is_wp_error($id)) {
			return '';
		}
		delete_transient('wc_attribute_taxonomies');
	}

	$tax = wc_attribute_taxonomy_name($slug); // -> pa_slug
	if (!taxonomy_exists($tax)) {
		register_taxonomy($tax, 'product', ['hierarchical' => false, 'show_ui' => false, 'query_var' => true, 'rewrite' => false]);
	}
	return $tax;
}

/** Inserisce i termini mancanti di un attributo (match per slug). */
function crs_ensure_terms($tax, $terms)
{
	foreach ($terms as $slug => $name) {
		if (!term_exists($slug, $tax)) {
			wp_insert_term($name, $tax, ['slug' => $slug]);
		}
	}
}

/** Inserisce i termini product_cat mancanti. */
function crs_ensure_cat_terms($terms)
{
	foreach ($terms as $slug => $name) {
		if (!term_exists($slug, 'product_cat')) {
			wp_insert_term($name, 'product_cat', ['slug' => $slug]);
		}
	}
}
