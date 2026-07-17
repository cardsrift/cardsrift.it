<?php

// Theme Options nella Dashboard — registrata su acf/init (non al caricamento del file):
// evita l'esecuzione anticipata di ACF, che su WP 6.7 fa scattare il notice
// "_load_textdomain_just_in_time ... dominio acf ... troppo presto".
add_action('acf/init', 'cr_add_options_page');
function cr_add_options_page()
{
    if (function_exists('acf_add_options_page')) {
        acf_add_options_page(array(
            'page_title' => 'Theme General Settings',
            'menu_title' => 'Theme Options',
            'menu_slug'  => 'theme-general-settings',
            'capability' => 'edit_posts',
            'redirect'   => true,
        ));
    }
}

add_filter('acf/settings/save_json', 'my_acf_json_save_point');

function my_acf_json_save_point($path)
{

    // update path
    $path = ABSPATH . '/acf-json';


    // return
    return $path;
}

add_filter('acf/settings/load_json', 'my_acf_json_load_point');

function my_acf_json_load_point($paths)
{

    // remove original path (optional)
    unset($paths[0]);


    // append path
    $paths[] = ABSPATH . '/acf-json';


    // return
    return $paths;
}