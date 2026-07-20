<?php

/**
 * PARTIAL LISTATO "PORTALE" — condiviso da archive-game-tipo.php e archive-accessori.php.
 * Filtri e ordinamento sono realizzati con parametri GET (nessun JS): li applica
 * cr_scope_main_query() in includes/routing.php. Qui si disegna solo la UI.
 *
 * Variabili attese (impostate dal template chiamante prima del require):
 *   $cr_l_game       slug gioco ('' su accessori)
 *   $cr_l_tipo       'sealed' | 'singole' | 'accessori'
 *   $cr_l_is_singole bool → griglia "tasche" invece di card
 *   $cr_l_base       URL base del listato (per form, reset, paginazione)
 *   $cr_l_eyebrow    occhiello
 *   $cr_l_title      titolo (h1)
 *   $cr_l_crumbs     array di ['label'=>, 'url'=>] (ultimo con url vuoto = pagina corrente)
 */

global $wp_query;

$facets = function_exists('cr_listing_facets') ? cr_listing_facets($cr_l_game ?? '', $cr_l_tipo ?? '') : [];
$total  = (int) $wp_query->found_posts;

// filtri attivi (per selected/valore campi, reset e paginazione che li preserva)
$keep   = ['pa_espansione', 'pa_condizione', 'pa_lingua', 'pa_foil', 'cr_min', 'cr_max', 'cr_ord'];
$active = [];
foreach ($keep as $k) {
	if (isset($_GET[$k]) && $_GET[$k] !== '') {
		$active[$k] = sanitize_text_field(wp_unslash($_GET[$k]));
	}
}
$orderby = $active['cr_ord'] ?? '';
?>

<main class="cr-sec" data-th="light">
	<div class="tw-container tw-section">
		<div class="py-10 lg:py-14">

			<nav class="cr-eyebrow !text-th-soft !tracking-[.18em] mb-4" aria-label="<?php esc_attr_e('Percorso', 'cardsrift'); ?>">
				<?php foreach ($cr_l_crumbs as $i => $cb) : ?>
					<?php if ($i) : ?><span class="opacity-40"> / </span><?php endif; ?>
					<?php if (!empty($cb['url'])) : ?>
						<a class="no-underline hover:text-th-acc transition-colors" href="<?= esc_url($cb['url']); ?>"><?= esc_html($cb['label']); ?></a>
					<?php else : ?>
						<span class="text-th-acc"><?= esc_html($cb['label']); ?></span>
					<?php endif; ?>
				<?php endforeach; ?>
			</nav>

			<div class="flex flex-wrap items-end justify-between gap-4">
				<div>
					<div class="cr-eyebrow"><?= esc_html($cr_l_eyebrow); ?></div>
					<h1 class="font-metropolis font-bold !text-2xl lg:!text-4xl mt-2"><?= esc_html($cr_l_title); ?></h1>
				</div>
				<span class="font-metropolis text-sm text-th-muted whitespace-nowrap"><?php printf(esc_html(_n('%s risultato', '%s risultati', $total, 'cardsrift')), esc_html(number_format_i18n($total))); ?></span>
			</div>

			<!-- TOOLBAR: filtri + ordinamento (GET, no-JS) -->
			<form method="get" action="<?= esc_url($cr_l_base); ?>" class="mt-6 flex flex-wrap items-end gap-3 lg:gap-4 pb-6 border-b border-th-line">

				<?php foreach ($facets as $txn => $f) : $sel = $active[$txn] ?? ''; ?>
					<label class="flex flex-col gap-1.5">
						<span class="cr-label !mb-0"><?= esc_html($f['label']); ?></span>
						<select name="<?= esc_attr($txn); ?>" class="cr-select !w-auto min-w-[128px]">
							<option value=""><?php esc_html_e('Tutte', 'cardsrift'); ?></option>
							<?php foreach ($f['terms'] as $t) : ?>
								<option value="<?= esc_attr($t->slug); ?>" <?php selected($sel, $t->slug); ?>><?= esc_html($t->name); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
				<?php endforeach; ?>

				<label class="flex flex-col gap-1.5">
					<span class="cr-label !mb-0"><?php esc_html_e('Prezzo €', 'cardsrift'); ?></span>
					<span class="flex items-center gap-1.5">
						<input type="number" inputmode="numeric" min="0" step="1" name="cr_min" value="<?= esc_attr($active['cr_min'] ?? ''); ?>" placeholder="<?php esc_attr_e('min', 'cardsrift'); ?>" class="cr-input !w-[74px]" aria-label="<?php esc_attr_e('Prezzo minimo', 'cardsrift'); ?>">
						<span class="text-th-soft">–</span>
						<input type="number" inputmode="numeric" min="0" step="1" name="cr_max" value="<?= esc_attr($active['cr_max'] ?? ''); ?>" placeholder="<?php esc_attr_e('max', 'cardsrift'); ?>" class="cr-input !w-[74px]" aria-label="<?php esc_attr_e('Prezzo massimo', 'cardsrift'); ?>">
					</span>
				</label>

				<label class="flex flex-col gap-1.5">
					<span class="cr-label !mb-0"><?php esc_html_e('Ordina', 'cardsrift'); ?></span>
					<select name="cr_ord" class="cr-select !w-auto min-w-[150px]">
						<option value="" <?php selected($orderby, ''); ?>><?php esc_html_e('Novità', 'cardsrift'); ?></option>
						<option value="prezzo-asc" <?php selected($orderby, 'prezzo-asc'); ?>><?php esc_html_e('Prezzo crescente', 'cardsrift'); ?></option>
						<option value="prezzo-desc" <?php selected($orderby, 'prezzo-desc'); ?>><?php esc_html_e('Prezzo decrescente', 'cardsrift'); ?></option>
						<option value="nome" <?php selected($orderby, 'nome'); ?>><?php esc_html_e('Nome A-Z', 'cardsrift'); ?></option>
					</select>
				</label>

				<button type="submit" class="cr-btn cr-btn-solid !py-3"><?php esc_html_e('Applica', 'cardsrift'); ?></button>
				<?php if ($active) : ?>
					<a href="<?= esc_url($cr_l_base); ?>" class="font-metropolis font-semibold text-sm text-th-muted hover:text-th-acc no-underline self-center py-3"><?php esc_html_e('Azzera', 'cardsrift'); ?></a>
				<?php endif; ?>
			</form>

			<!-- RISULTATI -->
			<?php if (have_posts()) : ?>

				<?php if (!empty($cr_l_is_singole)) : ?>
					<div class="cr-glass rounded-[20px] p-4 lg:p-5 shadow-th mt-8">
						<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
							<?php while (have_posts()) : the_post(); ?>
								<?php cr_pocket_card(get_the_ID()); ?>
							<?php endwhile; ?>
						</div>
					</div>
				<?php else : ?>
					<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 lg:gap-4 mt-8">
						<?php while (have_posts()) : the_post(); ?>
							<?php cr_product_card(get_the_ID()); ?>
						<?php endwhile; ?>
					</div>
				<?php endif; ?>

				<?php
				$pages = paginate_links([
					'base'      => trailingslashit($cr_l_base) . '%_%',
					'format'    => 'page/%#%/',
					'current'   => max(1, (int) get_query_var('paged')),
					'total'     => (int) $wp_query->max_num_pages,
					'add_args'  => $active,
					'prev_text' => '‹',
					'next_text' => '›',
					'mid_size'  => 1,
					'type'      => 'array',
				]);
				?>
				<?php if ($pages) : ?>
					<nav class="cr-pagenav mt-10" aria-label="<?php esc_attr_e('Pagine', 'cardsrift'); ?>">
						<?= implode('', $pages); ?>
					</nav>
				<?php endif; ?>

			<?php else : ?>
				<div class="mt-10 py-16 text-center">
					<p class="font-metropolis font-semibold text-lg text-th-ink"><?php esc_html_e('Nessun risultato', 'cardsrift'); ?></p>
					<p class="text-th-muted mt-1"><?= $active ? esc_html__('Prova a togliere qualche filtro.', 'cardsrift') : esc_html__('Ancora niente qui. Torna presto.', 'cardsrift'); ?></p>
					<?php if ($active) : ?>
						<a href="<?= esc_url($cr_l_base); ?>" class="cr-btn cr-btn-ghost mt-5"><?php esc_html_e('Azzera i filtri', 'cardsrift'); ?></a>
					<?php endif; ?>
				</div>
			<?php endif; ?>

		</div>
	</div>
</main>
