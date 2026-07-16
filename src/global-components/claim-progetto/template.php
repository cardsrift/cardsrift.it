<?php

/**
 * CLAIM PROGETTO (FID-1) — manifesto centrato con numeri e link alla pagina Chi siamo.
 * Layout ACF: claim_progetto — campi: tema, eyebrow, testo (wysiwyg basic, ammesso <b>),
 * stats (repeater: valore, etichetta), cta_label/cta_url, pattern (true/false)
 */
$c = is_array($component_data ?? null) ? $component_data : [];
$tema  = cr_theme($c);
$stats = is_array($c['stats'] ?? null) ? $c['stats'] : [];
?>

<section class="cr-sec claim-progetto <?= !empty($c['pattern']) ? 'cr-patt' : ''; ?>" data-th="<?= esc_attr($tema); ?>">
	<div class="tw-container tw-section">
		<div class="py-16 lg:py-20 flex flex-col items-center text-center">

			<?php if (!empty($c['eyebrow'])) : ?>
				<div class="cr-eyebrow"><?= esc_html($c['eyebrow']); ?></div>
			<?php endif; ?>

			<?php if (!empty($c['testo'])) : ?>
				<div class="claim-progetto__testo font-metropolis font-light !text-lg lg:!text-3xl leading-[1.42] max-w-[36ch] mt-4 mb-7 [&_b]:font-semibold [&_b]:text-th-acc [&_strong]:font-semibold [&_strong]:text-th-acc">
					<?= wp_kses_post($c['testo']); ?>
				</div>
			<?php endif; ?>

			<?php if ($stats) : ?>
				<div class="flex flex-wrap justify-center mb-8">
					<?php foreach ($stats as $s) : ?>
						<div class="px-8 text-center border-r border-th-lines last:border-r-0">
							<b class="block font-metropolis font-semibold text-xl tabular-nums"><?= esc_html($s['valore'] ?? ''); ?></b>
							<span class="font-metropolis text-xxs text-th-muted tracking-wide"><?= esc_html($s['etichetta'] ?? ''); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if (!empty($c['cta_label']) && !empty($c['cta_url'])) : ?>
				<a class="cr-btn cr-btn-ghost" href="<?= esc_url($c['cta_url']); ?>"><?= esc_html($c['cta_label']); ?></a>
			<?php endif; ?>

		</div>
	</div>
</section>
