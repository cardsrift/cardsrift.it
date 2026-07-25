<?php

/**
 * 404 — sobria. Tema dark base, contenuto centrato nel viewport. Ritmo verticale con un solo `gap`
 * (niente margin sparsi): numero → titolo/sottotitolo → ricerca → navigazione. Solo token th-/cr-.
 */

defined('ABSPATH') || exit;

get_header();

$games = ['magic' => 'Magic', 'pokemon' => 'Pokémon'];
?>

<main>
	<section class="cr-sec" data-th="dark">
		<div class="tw-container tw-section">
			<div class="min-h-[calc(100vh_-_290px)] py-16 flex flex-col items-center justify-center text-center">
				<div class="flex flex-col items-center gap-8 w-full max-w-md">

					<p class="font-metropolis font-bold !text-7xl lg:!text-8xl !leading-none text-th-acc tabular-nums">404</p>

					<div class="flex flex-col gap-2">
						<h1 class="font-metropolis font-semibold !text-2xl lg:!text-3xl"><?php esc_html_e('Pagina non trovata', 'cardsrift'); ?></h1>
						<p class="font-adelle text-th-muted"><?php esc_html_e('L’indirizzo è sbagliato o la pagina non esiste più.', 'cardsrift'); ?></p>
					</div>

					<form role="search" method="get" action="<?= esc_url(home_url('/')); ?>" class="w-full flex items-stretch gap-2">
						<?php // niente post_type nascosto: la ricerca è già solo prodotti (cr_scope_search),
						// e così l'URL resta uguale a quello del campo in header ?>
						<input type="search" name="s" class="cr-input flex-1" placeholder="<?php esc_attr_e('Cerca una carta…', 'cardsrift'); ?>" aria-label="<?php esc_attr_e('Cerca', 'cardsrift'); ?>">
						<button type="submit" class="cr-btn cr-btn-solid shrink-0"><?php esc_html_e('Cerca', 'cardsrift'); ?></button>
					</form>

					<div class="flex flex-wrap items-center justify-center gap-2">
						<a class="cr-chip no-underline ring-1 ring-inset ring-transparent hover:ring-th-acc2 transition-colors duration-200" href="<?= esc_url(home_url('/')); ?>"><?php esc_html_e('Home', 'cardsrift'); ?></a>
						<?php foreach ($games as $slug => $label) : ?>
							<a class="cr-chip no-underline ring-1 ring-inset ring-transparent hover:ring-th-acc2 transition-colors duration-200" href="<?= esc_url(home_url('/' . $slug . '/')); ?>"><?= esc_html($label); ?></a>
						<?php endforeach; ?>
					</div>

				</div>
			</div>
		</div>
	</section>
</main>

<?php get_footer(); ?>
