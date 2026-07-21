<?php

/**
 * BLOCCO METODO — "come lavoro/funziona" raccontato in concreto (niente numeri, niente vanto):
 * titolo a sinistra, a destra una lista di passaggi (verbo/etichetta + descrizione) con filetti.
 * Riutilizzabile per qualsiasi "come funziona" pacato. Campi: tema, eyebrow, titolo,
 * intro (opz.), voci[] (label, testo), nota (fine print sotto la lista, ammette <a>), pattern.
 */
$c = is_array($component_data ?? null) ? $component_data : [];
$tema = cr_theme($c);
$voci = is_array($c['voci'] ?? null)
	? array_values(array_filter($c['voci'], fn($v) => !empty($v['label']) || !empty($v['testo'])))
	: [];
if (!$voci) {
	return;
}
?>

<section class="cr-sec blocco-metodo <?= !empty($c['pattern']) ? 'cr-patt' : ''; ?>" data-th="<?= esc_attr($tema); ?>">
	<div class="tw-container tw-section">
		<div class="py-14 lg:py-16 grid gap-8 lg:gap-16 lg:grid-cols-[minmax(0,20rem)_1fr]" data-fx="rise">

			<div>
				<?php if (!empty($c['eyebrow'])) : ?>
					<div class="cr-eyebrow"><?= esc_html($c['eyebrow']); ?></div>
				<?php endif; ?>
				<?php if (!empty($c['titolo'])) : ?>
					<h2 class="font-metropolis font-bold !text-xl lg:!text-3xl mt-2.5"><?= esc_html($c['titolo']); ?></h2>
				<?php endif; ?>
				<?php if (!empty($c['intro'])) : ?>
					<p class="text-th-muted leading-relaxed max-w-[38ch] mt-4"><?= esc_html($c['intro']); ?></p>
				<?php endif; ?>
			</div>

			<div>
				<dl class="max-w-[62ch]">
					<?php foreach ($voci as $v) : ?>
						<div class="py-5 border-t border-th-line first:border-t-0 first:pt-0 last:pb-0">
							<dt class="font-metropolis font-semibold !text-base text-th-ink"><?= esc_html($v['label'] ?? ''); ?></dt>
							<dd class="text-th-muted leading-relaxed mt-1.5"><?= esc_html($v['testo'] ?? ''); ?></dd>
						</div>
					<?php endforeach; ?>
				</dl>

				<?php if (!empty($c['nota'])) : ?>
					<p class="text-th-muted text-sm leading-relaxed max-w-[60ch] mt-6 pt-5 border-t border-th-line [&_a]:underline [&_a]:text-th-ink hover:[&_a]:text-th-acc"><?= wp_kses_post($c['nota']); ?></p>
				<?php endif; ?>
			</div>

		</div>
	</div>
</section>
