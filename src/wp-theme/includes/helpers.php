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

// Show Menu in appereance

add_theme_support('menus');


/**
 * Get nav menu items by location
 *
 * @param $location The menu location id
 */
function get_nav_menu_items_by_location($location, $args = [])
{

    // Get all locations
    $locations = get_nav_menu_locations();

    // Get object id by location
    $object = wp_get_nav_menu_object($locations[$location]);

    // Get menu items by menu name
    $menu_items = wp_get_nav_menu_items($object->name, $args);

    // Return menu post objects
    return $menu_items;
}

// Sort del menu di wordpress in un array ordinato
function sort_wp_nav($location)
{
  $results = [];
  $array = get_nav_menu_items_by_location($location);
  for ($i = 0; $i < count($array); $i++) {
    if ($array[$i]->menu_item_parent == 0) {
      $results[] = (array)$array[$i];
    } else {
      iterHierarchy($results, $array[$i]);
    }
  }
  return $results;
}
function iterHierarchy(&$results, $item)
{
  for ($i = 0; $i < count($results); $i++) {
    if ($results[$i]['ID'] == $item->menu_item_parent) {
      $results[$i]['children'][] = (array)$item;
      return;
    } elseif (isset($results[$i]['children'])) {
      iterHierarchy($results[$i]['children'], $item);
    }
  }
}