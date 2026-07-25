<?php
// Aggiunta Featured Image
add_theme_support('post-thumbnails');

// INCLUDES
include('includes/helpers.php');
include('includes/config.php');
include('includes/rework.php');
include('includes/routing.php');
include('includes/nav.php');
include('includes/page-builder.php');
include('includes/seo.php');
include('includes/account.php');
include('includes/shop.php');
include('includes/script_and_style.php');
include('includes/acf.php');

// show_admin_bar(true);


add_action('after_setup_theme', 'cr_theme_setup');
function cr_theme_setup()
{
	// Traduzioni del tema: le stringhe __()/esc_html__() con text domain 'cardsrift'
	// vengono caricate da languages/cardsrift-{locale}.mo (setup per la traduzione EN).
	load_theme_textdomain('cardsrift', get_template_directory() . '/languages');

	add_theme_support('title-tag'); // <title> gestito da WordPress (rimosso wp_title() da header.php)

	// Nessuna location di menu: dal 25/07/2026 la navigazione si genera da CR_GAMES ×
	// CR_TIPI_CARTE (includes/nav.php), non dai menu di WordPress.
}


// workaround function lower php.7
if (!function_exists('str_contains')) {
	function str_contains(string $haystack, string $needle): bool
	{
		return '' === $needle || false !== strpos($haystack, $needle);
	}
}
function insert_jquery()
{
	wp_enqueue_script('jquery', false, array(), false, false);
}
add_filter('wp_enqueue_scripts', 'insert_jquery', 1);

add_filter('woocommerce_account_menu_items', 'remove_my_account_tabs', 999);

function remove_my_account_tabs($items) {

    unset($items['downloads']);
    
	return $items;
}

add_filter( 'woocommerce_product_tabs', 'remove_woocommerce_product_tabs', 98 );
function remove_woocommerce_product_tabs( $tabs ) {
    unset( $tabs['description'] );    // Rimuove la scheda "Descrizione"
    unset( $tabs['reviews'] );        // Rimuove la scheda "Recensioni"
    unset( $tabs['additional_information'] ); // Rimuove "Informazioni aggiuntive"
    return $tabs;
}

// rewmove lightbox
function remove_image_zoom_support() {
    remove_theme_support( 'wc-product-gallery-zoom' );
    remove_theme_support( 'wc-product-gallery-lightbox' );
}
add_action( 'wp', 'remove_image_zoom_support', 100 );

