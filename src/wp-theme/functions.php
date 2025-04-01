<?php
// Aggiunta Featured Image
add_theme_support('post-thumbnails');

// INCLUDES
include('includes/image_size.php');
include('includes/helpers.php');
include('includes/script_and_style.php');
include('includes/acf.php');

// show_admin_bar(true);


add_action('after_setup_theme', 'register_my_menu');
function register_my_menu()
{
	register_nav_menu('header', __('Header', 'theme-slug'));
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

add_filter( 'product_cat_class', 'filter_product_cat_class', 10, 3 );
function filter_product_cat_class( $classes, $class, $category ){
    // Only on shop page
    if( is_shop() )
        $classes[] = 'custom_cat';

    return $classes;
}
