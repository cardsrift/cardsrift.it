<?php

/**
 * BLOCCO TESTO — sezione editoriale riutilizzabile: titolo a sinistra, corpo a destra
 * (stack su mobile). Ammette <b>/<strong> (accent) e più paragrafi (wpautop).
 * Interamente da codice. Campi: tema, eyebrow, titolo, testo, pattern (true/false).
 */
$c = is_array($component_data ?? null) ? $component_data : [];
$tema = cr_theme($c);
?>

<section class="cr-sec blocco-testo <?= !empty($c['pattern']) ? 'cr-patt' : ''; ?>" data-th="<?= esc_attr($tema); ?>">
	<div class="tw-container tw-section">
		<div class="py-14 lg:py-16 grid gap-8 lg:gap-16 lg:grid-cols-[minmax(0,20rem)_1fr]" data-fx="rise">

			<div>
				<?php if (!empty($c['eyebrow'])) : ?>
					<div class="cr-eyebrow"><?= esc_html($c['eyebrow']); ?></div>
				<?php endif; ?>
				<?php if (!empty($c['titolo'])) : ?>
					<h2 class="font-metropolis font-bold !text-xl lg:!text-3xl mt-2.5"><?= esc_html($c['titolo']); ?></h2>
				<?php endif; ?>
			</div>

			<?php if (!empty($c['testo'])) : ?>
				<div class="max-w-[62ch] text-th-muted leading-relaxed [&_p]:mb-4 [&_p:last-child]:mb-0 [&_b]:font-semibold [&_b]:text-th-ink [&_strong]:font-semibold [&_strong]:text-th-ink">
					<?= wp_kses_post(wpautop($c['testo'])); ?>
				</div>
			<?php endif; ?>

		</div>
	</div>
</section>
