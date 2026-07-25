<?php

/**
 * RISULTATI DI RICERCA — solo prodotti (cr_scope_search in includes/nav.php).
 *
 * La regola anti-misto vale anche qui, ma in forma esplicita invece che per
 * costruzione: ogni card porta il chip del suo gioco e i chip in alto filtrano
 * per gioco. Se la ricerca parte da dentro un gioco arriva già ristretta a
 * quello (parametro `cr_g`), con "Tutti i giochi" come via d'uscita.
 */

get_header();

global $wp_query;

$cr_term  = get_search_query();
$cr_game  = function_exists('cr_search_game') ? cr_search_game() : '';
$cr_total = (int) $wp_query->found_posts;
$cr_games = defined('CR_GAMES') ? CR_GAMES : [];
?>

<main class="cr-sec" data-th="light">
	<div class="tw-container tw-section">
		<div class="py-10 lg:py-14">

			<nav class="cr-eyebrow !text-th-soft !tracking-[.18em] mb-4" aria-label="<?php esc_attr_e('Percorso', 'cardsrift'); ?>">
				<a class="no-underline hover:text-th-acc transition-colors" href="<?= esc_url(home_url('/')); ?>"><?php esc_html_e('Home', 'cardsrift'); ?></a>
				<span class="opacity-40"> / </span><span class="text-th-acc"><?php esc_html_e('Ricerca', 'cardsrift'); ?></span>
			</nav>

			<div class="flex flex-wrap items-end justify-between gap-4">
				<div>
					<?php // l'occhiello dice l'ambito, non ripete "Ricerca" già presente nel percorso ?>
					<div class="cr-eyebrow"><?= esc_html($cr_game ? cr_game_label($cr_game) : __('Tutto il catalogo', 'cardsrift')); ?></div>
					<h1 class="font-metropolis font-bold !text-2xl lg:!text-4xl mt-2">
						<?php if ($cr_term !== '') : ?>
							<?php printf(esc_html__('Risultati per «%s»', 'cardsrift'), esc_html($cr_term)); ?>
						<?php else : ?>
							<?php esc_html_e('Cerca nel catalogo', 'cardsrift'); ?>
						<?php endif; ?>
					</h1>
				</div>
				<?php // il conteggio dice anche DOVE si sta cercando: il filtro attivo si legge, non si indovina ?>
				<span class="font-metropolis text-sm text-th-muted whitespace-nowrap tabular-nums">
					<?php if ($cr_game) : ?>
						<?php printf(esc_html(_n('%1$s risultato in %2$s', '%1$s risultati in %2$s', $cr_total, 'cardsrift')), esc_html(number_format_i18n($cr_total)), esc_html(cr_game_label($cr_game))); ?>
					<?php else : ?>
						<?php printf(esc_html(_n('%s risultato', '%s risultati', $cr_total, 'cardsrift')), esc_html(number_format_i18n($cr_total))); ?>
					<?php endif; ?>
				</span>
			</div>

			<!-- Campo di ricerca: sempre presente, così si corregge il termine senza tornare su -->
			<form role="search" method="get" action="<?= esc_url(home_url('/')); ?>" class="mt-6 flex flex-wrap items-center gap-3">
				<span class="flex items-center gap-2.5 h-12 px-4 rounded-[10px] bg-th-surface border border-th-lines transition-colors focus-within:border-th-acc flex-1 min-w-[240px]">
					<svg class="w-[18px] h-[18px] stroke-th-soft fill-transparent shrink-0" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7" /><path d="M16.5 16.5L21 21" /></svg>
					<label class="sr-only" for="cr-search-page"><?php esc_attr_e('Cerca', 'cardsrift'); ?></label>
					<input id="cr-search-page" type="search" name="s" value="<?= esc_attr($cr_term); ?>" placeholder="<?php esc_attr_e('Cerca una carta o un set…', 'cardsrift'); ?>" class="w-full bg-transparent border-0 p-0 font-metropolis text-base text-th-ink placeholder:text-th-soft focus:outline-none">
				</span>
				<?php if ($cr_game) : ?><input type="hidden" name="cr_g" value="<?= esc_attr($cr_game); ?>"><?php endif; ?>
				<button type="submit" class="cr-btn cr-btn-solid"><?php esc_html_e('Cerca', 'cardsrift'); ?></button>
			</form>

			<!-- Filtro per gioco: i giochi restano distinti anche fra i risultati.
				 ⚠️ .cr-chip nasce GIÀ con fondo accsoft e testo accent: per far vedere quale è
				 attivo serve un altro stato, non una sfumatura dello stesso (il selezionato è
				 pieno, con la spunta). Le utility vincono sulla primitiva perché @layer
				 utilities viene dopo @layer components. -->
			<?php if ($cr_games && $cr_term !== '') : ?>
				<?php
				$cr_filtri = [['slug' => '', 'label' => __('Tutti i giochi', 'cardsrift')]];
				foreach ($cr_games as $cr_g) {
					$cr_filtri[] = ['slug' => $cr_g, 'label' => cr_game_label($cr_g)];
				}
				?>
				<div class="flex flex-wrap items-center gap-2 mt-5 pb-6 border-b border-th-line" role="group" aria-label="<?php esc_attr_e('Filtra per gioco', 'cardsrift'); ?>">
					<span class="cr-label !mb-0 mr-1"><?php esc_html_e('Gioco', 'cardsrift'); ?></span>
					<?php foreach ($cr_filtri as $cr_f) : $cr_is = ($cr_f['slug'] === $cr_game); ?>
						<a class="cr-chip inline-flex items-center gap-1.5 no-underline transition-colors duration-200 <?= $cr_is
								? 'bg-th-acc text-th-pg'
								: 'ring-1 ring-inset ring-transparent hover:ring-th-acc2'; ?>"
							href="<?= esc_url(cr_search_url($cr_term, $cr_f['slug'])); ?>"
							<?= $cr_is ? 'aria-current="true"' : ''; ?>>
							<?php if ($cr_is) : ?>
								<svg class="w-3 h-3 stroke-current fill-transparent shrink-0" viewBox="0 0 24 24" stroke-width="3.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7" /></svg>
							<?php endif; ?>
							<?= esc_html($cr_f['label']); ?>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<!-- RISULTATI -->
			<?php if (have_posts()) : ?>

				<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 lg:gap-4 mt-8" data-fx-stagger>
					<?php while (have_posts()) : the_post(); ?>
						<?php cr_product_card(get_the_ID()); ?>
					<?php endwhile; ?>
				</div>

				<?php
				$cr_pages = paginate_links([
					'current'   => max(1, (int) get_query_var('paged')),
					'total'     => (int) $wp_query->max_num_pages,
					'add_args'  => $cr_game ? ['cr_g' => $cr_game] : [],
					'prev_text' => '‹',
					'next_text' => '›',
					'mid_size'  => 1,
					'type'      => 'array',
				]);
				?>
				<?php if ($cr_pages) : ?>
					<nav class="cr-pagenav mt-10" aria-label="<?php esc_attr_e('Pagine', 'cardsrift'); ?>">
						<?= implode('', $cr_pages); ?>
					</nav>
				<?php endif; ?>

			<?php else : ?>

				<div class="mt-10 py-16 text-center">
					<p class="font-metropolis font-semibold text-lg text-th-ink">
						<?= $cr_term !== '' ? esc_html__('Nessun risultato', 'cardsrift') : esc_html__('Scrivi cosa stai cercando', 'cardsrift'); ?>
					</p>
					<p class="text-th-muted mt-1 max-w-[46ch] mx-auto">
						<?php if ($cr_term !== '' && $cr_game) : ?>
							<?php printf(esc_html__('Niente in %s. Prova a cercare in tutti i giochi.', 'cardsrift'), esc_html(cr_game_label($cr_game))); ?>
						<?php elseif ($cr_term !== '') : ?>
							<?php esc_html_e('Il catalogo è piccolo e curato: se non c’è, spesso conviene sfogliare.', 'cardsrift'); ?>
						<?php else : ?>
							<?php esc_html_e('Oppure sfoglia: il catalogo è piccolo, si gira in fretta.', 'cardsrift'); ?>
						<?php endif; ?>
					</p>
					<div class="flex flex-wrap items-center justify-center gap-2 mt-6">
						<?php if ($cr_term !== '' && $cr_game) : ?>
							<a class="cr-btn cr-btn-solid" href="<?= esc_url(cr_search_url($cr_term)); ?>"><?php esc_html_e('Cerca in tutti i giochi', 'cardsrift'); ?></a>
						<?php endif; ?>
						<?php foreach ($cr_games as $cr_g) : ?>
							<a class="cr-chip no-underline ring-1 ring-inset ring-transparent hover:ring-th-acc2 transition-colors duration-200" href="<?= esc_url(home_url('/' . $cr_g . '/')); ?>"><?= esc_html(cr_game_label($cr_g)); ?></a>
						<?php endforeach; ?>
						<a class="cr-chip no-underline ring-1 ring-inset ring-transparent hover:ring-th-acc2 transition-colors duration-200" href="<?= esc_url(home_url('/accessori/')); ?>"><?php esc_html_e('Accessori', 'cardsrift'); ?></a>
					</div>
				</div>

			<?php endif; ?>

		</div>
	</div>
</main>

<?php get_footer(); ?>
