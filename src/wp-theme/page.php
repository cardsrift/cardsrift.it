<?php

/**
 * PAGE BUILDER — template generico per tutte le Pagine.
 * Se esiste un manifest content/{slug}.php la pagina è composta dal codice (engine
 * cr_render_page); altrimenti fallback sul contenuto WYSIWYG classico.
 * Documentazione: docs/rework-fase-1.md
 */
get_header(); ?>

<?php
$slug     = get_post_field('post_name', get_queried_object_id());
$manifest = $slug ? cr_load_manifest($slug) : [];

if ($manifest) {
	cr_render_page($slug);
} else {
	// fallback: pagina classica (contenuto dell'editor)
	while (have_posts()) : the_post();
		the_content();
	endwhile;
}
?>

<?php get_footer(); ?>
