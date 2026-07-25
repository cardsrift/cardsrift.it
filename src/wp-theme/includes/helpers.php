<?php

// debug function
function debug($var, $absolute = false)
{
    if (!$absolute) {
        echo '<br/>';
        echo '<hr/>';
    }
    if ($absolute) {
        echo '<pre style="background-color: #000000; color: lime;font-size: 11px;text-align: left; position: absolute; top: 0; right: 0; z-index: 9999; width: 100%;">';
    } else {
        echo '<pre style="background-color: #000000; color: lime;font-size: 11px;text-align: left;">';
    }
    var_dump($var);
    echo '</pre>';
    if (!$absolute) {
        echo '<hr/>';
        echo '<br/>';
    }
}

/*
	I menu di WordPress non alimentano più niente: la navigazione dell'header si genera
	da CR_GAMES × CR_TIPI_CARTE (includes/nav.php) e il footer ha il copy in codice.
	Tolti il 25/07/2026 insieme a nav-menu.php: `add_theme_support('menus')`,
	`register_nav_menu('header')` e i lettori `sort_wp_nav()` /
	`get_nav_menu_items_by_location()` / `iterHierarchy()`.
	Per rimettere un menu gestito da wp-admin servono di nuovo il theme support e la
	location — ma prima chiedersi se il menu deve davvero essere un dato del DB.
*/