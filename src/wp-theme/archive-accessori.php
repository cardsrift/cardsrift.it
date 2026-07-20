<?php

/**
 * ACCESSORI — /accessori  (task #21, tema "Portale").
 * Unico listato GLOBALE (cross-gioco): gli accessori non sono carte → sempre card,
 * mai tasche. UI nel partial condiviso (stessi filtri/ordinamento dei listati carte).
 */

get_header();

$cr_l_game       = '';
$cr_l_tipo       = 'accessori';
$cr_l_is_singole = false;
$cr_l_base       = home_url('/accessori/');
$cr_l_eyebrow    = __('Per tutti i giochi', 'cardsrift');
$cr_l_title      = __('Accessori', 'cardsrift');
$cr_l_crumbs     = [
	['label' => __('Home', 'cardsrift'), 'url' => home_url('/')],
	['label' => __('Accessori', 'cardsrift'), 'url' => ''],
];

require get_template_directory() . '/partials/listing.php';

get_footer();
