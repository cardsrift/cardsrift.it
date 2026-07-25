<?php

/**
 * PAGE BUILDER — template generico per tutte le Pagine.
 * Se esiste un manifest content/{slug}.php la pagina è composta dal codice (engine
 * cr_render_page); altrimenti fallback sul contenuto WYSIWYG classico.
 * Documentazione: docs/rework-fase-1.md
 */
get_header(); ?>

<?php // <main>: il landmark del contenuto principale. Mancava proprio sulle pagine più
// battute (pagine-manifest, carrello, checkout, conferma d'ordine), mentre schede
// prodotto, listati e ricerca ce l'avevano già — chi usa uno screen reader perdeva
// il comando "vai al contenuto" a metà del percorso d'acquisto. ?>
<main>

<?php
$slug     = get_post_field('post_name', get_queried_object_id());
$manifest = $slug ? cr_load_manifest($slug) : [];

if ($manifest) {
	cr_render_page($slug);
} else {
	// Fallback: pagina classica (contenuto dell'editor). Il contenitore sta qui perché
	// .base non ha più gutter e una pagina scritta nell'editor resterebbe a filo schermo.
	//
	// ⚠️ ECCEZIONE: carrello, checkout e conferma d'ordine passano di qui (sono pagine
	// con shortcode) ma i loro override portano GIÀ il proprio `tw-container tw-section`
	// (cr_shop_open_section, checkout/thankyou.php). Avvolgerli faceva 24+24 = 48px di
	// margine laterale su telefono, e teneva le sezioni `cr-sec` — la banda lilla della
	// conferma — lontane dal bordo dello schermo invece che a tutta larghezza.
	$cr_own_layout = function_exists('is_cart') && (is_cart() || is_checkout() || is_account_page());

	if (!$cr_own_layout) {
		echo '<div class="tw-container tw-section">';
	}
	while (have_posts()) : the_post();
		the_content();
	endwhile;
	if (!$cr_own_layout) {
		echo '</div>';
	}
}
?>

</main>

<?php get_footer(); ?>
