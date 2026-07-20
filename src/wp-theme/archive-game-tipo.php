<?php

/**
 * LISTATO GIOCO + TIPO — /{gioco}/{tipo}  (task #21, tema "Portale").
 * Sempre gioco-scoped: mai misto (il carrello/checkout restano globali).
 * Sealed → card prodotto; Singole → tasche del raccoglitore. UI nel partial condiviso.
 */

get_header();

$game = get_query_var('cr_game');
$tipo = get_query_var('cr_tipo');

$cr_l_game       = $game;
$cr_l_tipo       = $tipo;
$cr_l_is_singole = ($tipo === 'singole');
$cr_l_base       = home_url('/' . $game . '/' . $tipo . '/');
$cr_l_eyebrow    = cr_game_label($game);
$cr_l_title      = cr_tipo_label($tipo);
$cr_l_crumbs     = [
	['label' => __('Home', 'cardsrift'), 'url' => home_url('/')],
	['label' => cr_game_label($game), 'url' => home_url('/' . $game . '/')],
	['label' => cr_tipo_label($tipo), 'url' => ''],
];

require get_template_directory() . '/partials/listing.php';

get_footer();
