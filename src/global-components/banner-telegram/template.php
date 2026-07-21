<?php

/**
 * BANNER TELEGRAM (COMM-3) — community: restock alert, preordini in anteprima, giveaway.
 * Layout centrato e compatto (fa coppia con la newsletter): eyebrow → titolo → testo → CTA,
 * con l'aeroplanino come watermark centrato dietro.
 * Layout ACF: banner_telegram — campi: tema, eyebrow, titolo, testo, cta_label/cta_url
 */
$c = is_array($component_data ?? null) ? $component_data : [];
$tema = cr_theme($c);
?>

<section class="cr-sec banner-telegram overflow-hidden" data-th="<?= esc_attr($tema); ?>">
	<div class="tw-container tw-section relative">

		<!-- aeroplanino: watermark centrato dietro il contenuto -->
		<svg class="absolute left-[calc(50%+34px)] top-1/2 -translate-x-1/2 -translate-y-1/2 rotate-[-12deg] w-[220px] h-[220px] lg:w-[280px] lg:h-[280px] opacity-[0.08] stroke-current fill-transparent pointer-events-none z-0" viewBox="0 0 24 24" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4z"/></svg>

		<div class="relative z-10 py-14 lg:py-16 max-w-[560px] mx-auto flex flex-col items-center text-center" data-fx="rise">

			<?php if (!empty($c['eyebrow'])) : ?>
				<div class="cr-eyebrow"><?= esc_html($c['eyebrow']); ?></div>
			<?php endif; ?>
			<?php if (!empty($c['titolo'])) : ?>
				<h2 class="font-metropolis font-bold !text-lg lg:!text-2xl mt-2"><?= esc_html($c['titolo']); ?></h2>
			<?php endif; ?>
			<?php if (!empty($c['testo'])) : ?>
				<p class="text-th-muted max-w-[48ch] mt-3 mb-6"><?= esc_html($c['testo']); ?></p>
			<?php endif; ?>

			<?php if (!empty($c['cta_label']) && !empty($c['cta_url'])) : ?>
				<a class="cr-btn cr-btn-solid" href="<?= esc_url($c['cta_url']); ?>" target="_blank" rel="noopener"><?= esc_html($c['cta_label']); ?></a>
			<?php endif; ?>

		</div>
	</div>
</section>
