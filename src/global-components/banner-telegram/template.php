<?php

/**
 * BANNER TELEGRAM (COMM-3) — community: restock alert, preordini in anteprima, giveaway.
 * Layout ACF: banner_telegram — campi: tema, eyebrow, titolo, testo, cta_label/cta_url
 */
$c = is_array($component_data ?? null) ? $component_data : [];
$tema = cr_theme($c);
?>

<section class="cr-sec banner-telegram overflow-hidden" data-th="<?= esc_attr($tema); ?>">
	<div class="tw-container tw-section relative">
		<!-- aeroplanino watermark -->
		<svg class="absolute -right-6 top-1/2 -translate-y-1/2 rotate-[-12deg] w-[210px] h-[210px] opacity-[0.13] stroke-current fill-transparent pointer-events-none" viewBox="0 0 24 24" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4z"/></svg>

		<div class="py-12 flex items-center gap-8 max-lg:flex-col max-lg:items-start relative">
			<div class="flex-1">
				<?php if (!empty($c['eyebrow'])) : ?>
					<div class="cr-eyebrow"><?= esc_html($c['eyebrow']); ?></div>
				<?php endif; ?>
				<?php if (!empty($c['titolo'])) : ?>
					<h2 class="font-metropolis font-bold !text-lg lg:!text-2xl mt-2"><?= esc_html($c['titolo']); ?></h2>
				<?php endif; ?>
				<?php if (!empty($c['testo'])) : ?>
					<p class="text-th-muted max-w-[56ch] mt-2 mb-0"><?= esc_html($c['testo']); ?></p>
				<?php endif; ?>
			</div>

			<?php if (!empty($c['cta_label']) && !empty($c['cta_url'])) : ?>
				<a class="cr-btn cr-btn-solid whitespace-nowrap" href="<?= esc_url($c['cta_url']); ?>" target="_blank" rel="noopener"><?= esc_html($c['cta_label']); ?></a>
			<?php endif; ?>
		</div>
	</div>
</section>
