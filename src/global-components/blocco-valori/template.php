<?php

/**
 * BLOCCO VALORI — griglia riutilizzabile di punti: valori, garanzie, "come funziona" a step.
 * Ogni voce: titolo + testo, con numero d'ordine opzionale ('numerato' => true).
 * Interamente da codice. Campi: tema, eyebrow, titolo, valori[] (titolo, testo),
 * colonne (2|3|4), numerato (true/false), pattern (true/false).
 */
$c = is_array($component_data ?? null) ? $component_data : [];
$tema   = cr_theme($c);
$valori = is_array($c['valori'] ?? null) ? $c['valori'] : [];
if (!$valori) {
	return;
}
$cols = (int) ($c['colonne'] ?? count($valori));
$grid = [
	2 => 'sm:grid-cols-2',
	3 => 'sm:grid-cols-2 lg:grid-cols-3',
	4 => 'sm:grid-cols-2 lg:grid-cols-4',
][$cols] ?? 'sm:grid-cols-2 lg:grid-cols-3';
$numerato = !empty($c['numerato']);
?>

<section class="cr-sec blocco-valori <?= !empty($c['pattern']) ? 'cr-patt' : ''; ?>" data-th="<?= esc_attr($tema); ?>">
	<div class="tw-container tw-section">
		<div class="py-14 lg:py-16">

			<?php if (!empty($c['eyebrow']) || !empty($c['titolo'])) : ?>
				<div class="max-w-[42ch] mb-10 lg:mb-12">
					<?php if (!empty($c['eyebrow'])) : ?>
						<div class="cr-eyebrow"><?= esc_html($c['eyebrow']); ?></div>
					<?php endif; ?>
					<?php if (!empty($c['titolo'])) : ?>
						<h2 class="font-metropolis font-bold !text-xl lg:!text-3xl mt-2.5"><?= esc_html($c['titolo']); ?></h2>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<div class="grid grid-cols-1 <?= $grid; ?> gap-8 lg:gap-10">
				<?php foreach ($valori as $i => $v) : ?>
					<div class="border-t border-th-line pt-5">
						<?php if ($numerato) : ?>
							<span class="block font-bylon text-2xl text-th-acc tabular-nums mb-3"><?= esc_html(sprintf('%02d', $i + 1)); ?></span>
						<?php endif; ?>
						<h3 class="font-metropolis font-semibold !text-lg mb-2"><?= esc_html($v['titolo'] ?? ''); ?></h3>
						<p class="text-th-muted leading-relaxed max-w-[42ch]"><?= esc_html($v['testo'] ?? ''); ?></p>
					</div>
				<?php endforeach; ?>
			</div>

		</div>
	</div>
</section>
