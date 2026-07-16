<?php

/**
 * GRIGLIA PRODOTTI (GRID-1 / GRID-2) — griglia riutilizzabile con card unica del design system.
 * Layout ACF: griglia_prodotti — campi: tema, eyebrow, titolo, link_label/link_url,
 * sorgente (radio: manuale|recenti|offerte), prodotti (relationship, se manuale),
 * stile_card (radio: solido|glass), colonne (radio: 3|4), prodotto_top (post object, opzionale),
 * pattern (true/false: logo ripetuto sullo sfondo)
 */
$c = is_array($component_data ?? null) ? $component_data : [];
$tema     = cr_theme($c);
$sorgente = $c['sorgente'] ?? 'recenti';
$colonne  = (int) ($c['colonne'] ?? 4);
$glass    = ($c['stile_card'] ?? 'solido') === 'glass';
$top_id   = is_object($c['prodotto_top'] ?? null) ? $c['prodotto_top']->ID : (int) ($c['prodotto_top'] ?? 0);
$manual   = array_map(fn($p) => is_object($p) ? $p->ID : (int) $p, (array) ($c['prodotti'] ?? []));

$ids = cr_grid_products($sorgente, $manual, $colonne);
if (!$ids) {
	return;
}
$grid_class = $colonne === 3 ? 'lg:grid-cols-3' : 'lg:grid-cols-4';
?>

<section class="cr-sec griglia-prodotti <?= !empty($c['pattern']) ? 'cr-patt' : ''; ?>" data-th="<?= esc_attr($tema); ?>">
	<div class="tw-container tw-section">
		<div class="py-14 lg:py-16">

			<div class="flex items-end justify-between gap-4 mb-7">
				<div>
					<?php if (!empty($c['eyebrow'])) : ?>
						<div class="cr-eyebrow"><?= esc_html($c['eyebrow']); ?></div>
					<?php endif; ?>
					<?php if (!empty($c['titolo'])) : ?>
						<h2 class="font-metropolis font-bold !text-xl lg:!text-3xl mt-2"><?= esc_html($c['titolo']); ?></h2>
					<?php endif; ?>
				</div>
				<?php if (!empty($c['link_label']) && !empty($c['link_url'])) : ?>
					<a class="font-metropolis font-semibold text-xs uppercase tracking-wider text-th-acc no-underline hover:underline whitespace-nowrap pb-1" href="<?= esc_url($c['link_url']); ?>"><?= esc_html($c['link_label']); ?> →</a>
				<?php endif; ?>
			</div>

			<div class="grid grid-cols-2 <?= $grid_class; ?> gap-3 lg:gap-4">
				<?php foreach ($ids as $pid) : ?>
					<?php cr_product_card($pid, [
						'glass'    => $glass,
						'top_deal' => $top_id && (int) $pid === $top_id,
					]); ?>
				<?php endforeach; ?>
			</div>

		</div>
	</div>
</section>
