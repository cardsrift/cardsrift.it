<?php

/**
 * PAGE BUILDER — template generico per tutte le Pagine.
 * Stesso loop della homepage: il flexible content ACF "components" rende
 * ogni componente riutilizzabile su qualunque pagina (Chi siamo, Bulk, ecc.).
 * Se la pagina non usa componenti, fallback sul contenuto WYSIWYG classico.
 * Documentazione: docs/rework-fase-1.md
 */
get_header(); ?>

<?php
$sections = function_exists('get_field') ? (get_field('components') ?: []) : [];

if ($sections) {
	foreach ($sections as $component) {
		$component_name = str_replace('_', '-', $component['acf_fc_layout']);
		set_query_var('component_data', $component);
		get_template_part("global-components/$component_name/template");
	}
} else {
	// fallback: pagina classica
	while (have_posts()) : the_post();
		the_content();
	endwhile;
}
?>

<?php get_footer(); ?>
