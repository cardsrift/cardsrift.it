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

// Hero della landing di ogni gioco (/{gioco}) — curato dalle Theme Options.
// Un tab per gioco (loop CR_GAMES): l'immagine wide + copy opzionale. Registrato da
// codice = versionato nel repo e sempre allineato ai giochi del routing.
add_action('acf/init', 'cr_register_game_hero_fields');
function cr_register_game_hero_fields()
{
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }
    $games  = defined('CR_GAMES') ? CR_GAMES : ['magic', 'pokemon', 'one-piece'];
    $fields = [[
        'key'       => 'field_cr_hero_intro',
        'type'      => 'message',
        'label'     => '',
        'message'   => __('Hero della landing di ogni gioco (/{gioco}). Carica la key-art orizzontale del set: se la lasci vuota, la landing usa lo skin di brand. Titolo, data e pulsante sono opzionali.', 'cardsrift'),
        'new_lines' => 'wpautop',
    ]];

    foreach ($games as $g) {
        $gs    = str_replace('-', '_', $g);
        $label = function_exists('cr_game_label') ? cr_game_label($g) : $g;

        $fields[] = ['key' => "field_cr_hero_{$gs}_tab", 'type' => 'tab', 'label' => $label];
        $fields[] = [
            'key'           => "field_cr_hero_{$gs}_immagine",
            'name'          => "hero_{$gs}_immagine",
            'label'         => __('Immagine hero (wide)', 'cardsrift'),
            'type'          => 'image',
            'return_format' => 'array',
            'preview_size'  => 'medium',
            'library'       => 'all',
            'mime_types'    => 'jpg,jpeg,png,webp',
            'instructions'  => __('Key-art orizzontale del set (consigliato ≥ 1600px di larghezza). Vuota = skin di brand.', 'cardsrift'),
        ];
        $fields[] = ['key' => "field_cr_hero_{$gs}_sopratitolo", 'name' => "hero_{$gs}_sopratitolo", 'label' => __('Sopratitolo', 'cardsrift'), 'type' => 'text', 'placeholder' => __('Prossima espansione', 'cardsrift')];
        $fields[] = ['key' => "field_cr_hero_{$gs}_titolo", 'name' => "hero_{$gs}_titolo", 'label' => __('Titolo', 'cardsrift'), 'type' => 'text', 'instructions' => __('Es. il nome del set. Vuoto = titolo di default del gioco.', 'cardsrift')];
        $fields[] = ['key' => "field_cr_hero_{$gs}_sottotitolo", 'name' => "hero_{$gs}_sottotitolo", 'label' => __('Sottotitolo', 'cardsrift'), 'type' => 'textarea', 'rows' => 2, 'new_lines' => ''];
        $fields[] = ['key' => "field_cr_hero_{$gs}_data", 'name' => "hero_{$gs}_data", 'label' => __('Etichetta data', 'cardsrift'), 'type' => 'text', 'placeholder' => __('Esce il 14 agosto', 'cardsrift')];
        $fields[] = ['key' => "field_cr_hero_{$gs}_cta_label", 'name' => "hero_{$gs}_cta_label", 'label' => __('Pulsante — testo (opzionale)', 'cardsrift'), 'type' => 'text'];
        $fields[] = ['key' => "field_cr_hero_{$gs}_cta_url", 'name' => "hero_{$gs}_cta_url", 'label' => __('Pulsante — link', 'cardsrift'), 'type' => 'text', 'placeholder' => '/magic/sealed/'];
    }

    acf_add_local_field_group([
        'key'        => 'group_cr_game_hero',
        'title'      => __('Hero landing di gioco', 'cardsrift'),
        'fields'     => $fields,
        'location'   => [[['param' => 'options_page', 'operator' => '==', 'value' => 'theme-general-settings']]],
        'menu_order' => 5,
        'position'   => 'normal',
        'style'      => 'default',
    ]);
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