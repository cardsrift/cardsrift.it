<?php

function wp_enqueue_styles_and_scripts()
{

    //  Cache-busting: versione = data di modifica del file, si aggiorna da sola ad ogni build/deploy
    $css_path = get_template_directory() . '/assets/styles/app.css';
    $js_path  = get_template_directory() . '/assets/js/app.js';
    $css_version = file_exists($css_path) ? (string) filemtime($css_path) : '1.1';
    $js_version  = file_exists($js_path) ? (string) filemtime($js_path) : '1.1';


    wp_register_style('custom_styles', get_template_directory_uri() . '/assets/styles/app.css', null, $css_version, 'screen');
    wp_enqueue_style('custom_styles');


    //  Dipendenza 'jquery': il bundle usa la jQuery di WordPress (webpack externals), non una copia propria
    wp_register_script('custom_scripts', get_template_directory_uri() . '/assets/js/app.js', array('jquery'), $js_version, true);
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