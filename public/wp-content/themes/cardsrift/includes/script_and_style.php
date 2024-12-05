<?php

function wp_enqueue_styles_and_scripts()
{

    if (strpos($_SERVER['REMOTE_ADDR'], 'localhost') !== false) {
        $assets_version = time();
    } else {
        $assets_version = '1.1';
    }


    wp_register_style('custom_styles', get_template_directory_uri() . '/assets/styles/app.css', null, $assets_version, 'screen');
    wp_enqueue_style('custom_styles');


    wp_register_script('custom_scripts', get_template_directory_uri() . '/assets/js/app.js', array(), $assets_version, true);
    wp_enqueue_script('custom_scripts');

    //  Variabili da passare agli script JS
    $dataToBePassed = array();
    //  1. Base url e permalink
    $dataToBePassed['baseUrl'] = site_url();
    $dataToBePassed['permalink'] = get_the_permalink();
    $dataToBePassed['templateDir'] = get_template_directory_uri();


    //  2. Current language
    if (function_exists('icl_object_id')) {
        //  Verifico che WMPL SIA ATTIVO
        $dataToBePassed['lang'] = ICL_LANGUAGE_CODE;
    } else {
        $dataToBePassed['lang'] = 0;
    }
    wp_localize_script('custom_scripts', 'phpVars', $dataToBePassed);
}

add_action('wp_enqueue_scripts', 'wp_enqueue_styles_and_scripts');