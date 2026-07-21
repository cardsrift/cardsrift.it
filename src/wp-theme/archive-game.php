<?php

/**
 * LANDING DI GIOCO — /{gioco}  (task #20 · direzione "Portale").
 * Caricata da includes/routing.php (template_include) quando cr_landing + cr_game.
 *
 * Hero cinematografico ispirato ai product-landing degli editori: full-bleed con la
 * key-art del gioco, curata a mano dalle Theme Options (cr_game_hero → gruppo "Hero
 * landing di gioco"). Il colore lo porta l'immagine; se non c'è, l'hero fa fallback
 * allo skin di brand (pattern + glow su token). Sotto: le due porte del gioco
 * (Sealed / Singole), la vetrina in evidenza e la lista "In arrivo".
 * Nessun colore hardcoded: solo token th- e cr-.
 */

get_header();

$cr_game  = get_query_var('cr_game');
$cr_label = cr_game_label($cr_game);

$cr_sealed_url  = home_url('/' . $cr_game . '/sealed/');
$cr_singole_url = home_url('/' . $cr_game . '/singole/');

// Hero curato dalle Theme Options; copy vuoto → default sensati qui sotto.
$h        = function_exists('cr_game_hero') ? cr_game_hero($cr_game) : [];
$h_img    = $h['img'] ?? '';
$h_over   = ($h['sopratitolo'] ?? '') ?: ($h_img ? __('Prossima espansione', 'cardsrift') : $cr_label);
$h_title  = ($h['titolo'] ?? '') ?: sprintf(__('Tutto %s, scelto una carta alla volta.', 'cardsrift'), $cr_label);
$h_sub    = ($h['sottotitolo'] ?? '') ?: ($h_img
	? __('Sealed e singole del set in arrivo, controllate una carta alla volta.', 'cardsrift')
	: __('Sealed e singole selezionate una per una, condizioni oneste e foto reali. Scegli da dove partire.', 'cardsrift'));
$h_date   = $h['data'] ?? '';
$h_cta_l  = $h['cta_label'] ?? '';
$h_cta_u  = $h['cta_url'] ?? '';

// "In arrivo" di QUESTO gioco (indipendente dall'hero): prodotti con data_uscita.
$cr_upcoming = function_exists('cr_preorder_products')
	? array_values(array_filter(cr_preorder_products(0), fn($pid) => cr_product_game_slug($pid) === $cr_game))
	: [];
?>

<main>

	<!-- ============ HERO CINEMATOGRAFICO ============ -->
	<section class="cr-sec relative overflow-hidden <?= $h_img ? '' : 'cr-patt'; ?>" data-th="dark">

		<?php if ($h_img) : ?>
			<img class="absolute inset-0 w-full h-full object-cover" src="<?= esc_url($h_img); ?>" alt="<?= esc_attr($h_title); ?>" fetchpriority="high" decoding="async">
			<!-- scrim costruito sul token di pagina (dark): leggibilità del testo in basso a sinistra -->
			<span class="absolute inset-0 pointer-events-none" style="background:linear-gradient(to top, var(--cr-pg) 3%, rgba(29,33,37,.10) 58%, transparent 100%)"></span>
			<span class="absolute inset-0 pointer-events-none" style="background:linear-gradient(to right, var(--cr-pg) 0%, transparent 64%)"></span>
		<?php else : ?>
			<!-- fallback skin di brand: glow d'accento su token (nessuna immagine caricata) -->
			<span class="absolute inset-0 pointer-events-none" style="background:radial-gradient(90% 75% at 15% 15%, var(--cr-accsoft), transparent 55%)"></span>
		<?php endif; ?>

		<div class="relative tw-container tw-section">
			<div class="min-h-[74vh] lg:min-h-[84vh] flex flex-col justify-end pt-10 pb-16 lg:pb-24">

				<nav class="cr-eyebrow !tracking-[.18em] !text-th-soft mb-5" aria-label="<?php esc_attr_e('Percorso', 'cardsrift'); ?>">
					<a class="no-underline hover:text-th-acc transition-colors" href="<?= esc_url(home_url('/')); ?>"><?php esc_html_e('Home', 'cardsrift'); ?></a>
					<span class="opacity-40"> / </span><span class="text-th-acc"><?= esc_html($cr_label); ?></span>
				</nav>

				<div class="cr-eyebrow mb-3"><?= esc_html($h_over); ?></div>

				<h1 class="font-metropolis font-bold !text-4xl sm:!text-6xl lg:!text-7xl !leading-[0.98] max-w-[16ch] [text-wrap:balance]"><?= esc_html($h_title); ?></h1>

				<div class="flex flex-wrap items-center gap-x-4 gap-y-2 mt-5">
					<span class="cr-chip"><?= esc_html($cr_label); ?></span>
					<?php if ($h_date) : ?>
						<span class="font-bylon uppercase tracking-[.14em] text-sm lg:text-base text-th-acc"><?= esc_html($h_date); ?></span>
					<?php endif; ?>
				</div>

				<p class="mt-5 max-w-[48ch] text-th-muted text-base lg:text-lg"><?= esc_html($h_sub); ?></p>

				<div class="flex flex-wrap items-center gap-3 mt-8">
					<a class="cr-btn cr-btn-solid" href="<?= esc_url($cr_sealed_url); ?>"><?php esc_html_e('Sfoglia il Sealed', 'cardsrift'); ?></a>
					<a class="cr-btn cr-btn-ghost" href="<?= esc_url($cr_singole_url); ?>"><?php esc_html_e('Carte singole', 'cardsrift'); ?></a>
					<?php if ($h_cta_l && $h_cta_u) : ?>
						<a class="font-metropolis font-semibold text-sm text-th-acc no-underline hover:underline whitespace-nowrap" href="<?= esc_url($h_cta_u); ?>"><?= esc_html($h_cta_l); ?> →</a>
					<?php endif; ?>
				</div>

			</div>

			<a href="#gioco-contenuti" class="hidden lg:flex absolute left-1/2 -translate-x-1/2 bottom-6 flex-col items-center gap-1.5 text-th-soft hover:text-th-acc no-underline transition-colors" aria-label="<?php esc_attr_e('Scorri ai contenuti', 'cardsrift'); ?>">
				<span class="font-bylon uppercase text-[10px] tracking-[.24em]"><?php esc_html_e('Scorri', 'cardsrift'); ?></span>
				<svg class="w-4 h-4 stroke-current fill-transparent animate-bounce motion-reduce:animate-none" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
			</a>
		</div>
	</section>

	<!-- ============ LE DUE PORTE DEL GIOCO ============ -->
	<section id="gioco-contenuti" class="cr-sec" data-th="light">
		<div class="tw-container tw-section">
			<div class="py-12 lg:py-16 grid grid-cols-1 sm:grid-cols-2 gap-4 lg:gap-5">

				<a class="cr-card !rounded-2xl p-7 lg:p-9 flex flex-col gap-2 min-h-[168px] justify-between group" href="<?= esc_url($cr_sealed_url); ?>">
					<div class="cr-eyebrow"><?php esc_html_e('Sigillato', 'cardsrift'); ?></div>
					<div>
						<h2 class="font-metropolis font-bold !text-2xl lg:!text-3xl"><?php esc_html_e('Sealed', 'cardsrift'); ?></h2>
						<p class="text-th-muted text-sm mt-1 max-w-[42ch]"><?php esc_html_e('Box, buste e prodotti sigillati del gioco, imballati con cura.', 'cardsrift'); ?></p>
					</div>
					<span class="font-metropolis font-semibold text-sm text-th-acc mt-1"><?php esc_html_e('Sfoglia il sealed', 'cardsrift'); ?> <span class="inline-block transition-transform group-hover:translate-x-1">→</span></span>
				</a>

				<a class="cr-card !rounded-2xl p-7 lg:p-9 flex flex-col gap-2 min-h-[168px] justify-between group" href="<?= esc_url($cr_singole_url); ?>">
					<div class="cr-eyebrow"><?php esc_html_e('Raccoglitore', 'cardsrift'); ?></div>
					<div>
						<h2 class="font-metropolis font-bold !text-2xl lg:!text-3xl"><?php esc_html_e('Carte singole', 'cardsrift'); ?></h2>
						<p class="text-th-muted text-sm mt-1 max-w-[42ch]"><?php esc_html_e('Ogni singola controllata, con condizione dichiarata e foto reale.', 'cardsrift'); ?></p>
					</div>
					<span class="font-metropolis font-semibold text-sm text-th-acc mt-1"><?php esc_html_e('Apri il raccoglitore', 'cardsrift'); ?> <span class="inline-block transition-transform group-hover:translate-x-1">→</span></span>
				</a>

			</div>
		</div>
	</section>

	<!-- ============ IN EVIDENZA (query gioco-scoped) ============ -->
	<?php if (have_posts()) : ?>
		<section class="cr-sec" data-th="light">
			<div class="tw-container tw-section">
				<div class="pb-14 lg:pb-16">
					<div class="cr-eyebrow"><?= esc_html($cr_label); ?></div>
					<h2 class="font-metropolis font-bold !text-xl lg:!text-3xl mt-2 mb-7"><?php esc_html_e('In evidenza', 'cardsrift'); ?></h2>
					<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 lg:gap-4" data-fx-stagger>
						<?php while (have_posts()) : the_post(); ?>
							<?php cr_product_card(get_the_ID()); ?>
						<?php endwhile; ?>
					</div>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<!-- ============ IN ARRIVO (di questo gioco) ============ -->
	<?php if ($cr_upcoming) : ?>
		<section class="cr-sec" data-th="dark">
			<div class="tw-container tw-section">
				<div class="py-14 lg:py-16">
					<div class="cr-eyebrow"><?php esc_html_e('Prossime uscite', 'cardsrift'); ?></div>
					<h2 class="font-metropolis font-bold !text-xl lg:!text-3xl mt-2 mb-6"><?php printf(esc_html__('In arrivo su %s', 'cardsrift'), esc_html($cr_label)); ?></h2>
					<div class="w-fit max-w-full">
						<?php foreach ($cr_upcoming as $pid) :
							$data = cr_format_uscita(get_field('data_uscita', $pid));
						?>
							<a class="grid grid-cols-[64px_1fr] lg:grid-cols-[96px_1fr] gap-4 lg:gap-6 items-baseline py-4 px-2 w-full border-b border-th-lines last:border-b-0 no-underline transition-colors hover:bg-th-accsoft" href="<?= esc_url(get_permalink($pid)); ?>">
								<span class="font-bylon text-sm text-th-acc"><?= esc_html($data ?: '—'); ?></span>
								<h3 class="font-metropolis font-medium !text-base text-th-ink truncate"><?= esc_html(get_the_title($pid)); ?></h3>
							</a>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		</section>
	<?php endif; ?>

</main>
<?php get_footer(); ?>
