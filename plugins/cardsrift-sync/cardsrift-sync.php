<?php

/**
 * Plugin Name: CardsRift — Sync
 * Description: Pipeline dati del catalogo: import da Cardmarket (CSV) e — in arrivo — sync con CardTrader. Crea attributi e categorie del catalogo. Il tema resta solo presentazione.
 * Version: 0.1.0
 * Author: CardsRift
 * Text Domain: cardsrift-sync
 */

if (!defined('ABSPATH')) {
	exit;
}

define('CRS_VER', '0.1.0');
define('CRS_DIR', plugin_dir_path(__FILE__));
define('CRS_SCHEMA_VER', 1); // bump quando cambiano attributi/termini/categorie

require_once CRS_DIR . 'includes/contract.php';
require_once CRS_DIR . 'includes/schema.php';
require_once CRS_DIR . 'includes/parser.php';
require_once CRS_DIR . 'includes/importer.php';
require_once CRS_DIR . 'includes/display.php';
require_once CRS_DIR . 'includes/admin.php';

/** All'attivazione crea lo schema catalogo (richiede WooCommerce attivo). */
register_activation_hook(__FILE__, function () {
	if (function_exists('wc_create_attribute')) {
		crs_schema_ensure();
	}
});

/** Rete di sicurezza: (ri)crea lo schema se il plugin gira prima di WooCommerce o è a una versione vecchia. */
add_action('admin_init', function () {
	if ((int) get_option('crs_schema_ver') < CRS_SCHEMA_VER && function_exists('wc_create_attribute')) {
		crs_schema_ensure();
	}
});

/** Guard anti-bloat: i prodotti non devono accumulare revisioni. */
add_filter('wp_revisions_to_keep', function ($num, $post) {
	return ($post && $post->post_type === 'product') ? 0 : $num;
}, 10, 2);
