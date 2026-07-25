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

$ids = cr_grid_products($sorgente, $manual, $colonne, $c['gioco'] ?? '');
$ph  = !$ids && CR_PLACEHOLDER; // sezione vuota + modalità sviluppo → card di esempio
if (!$ids && !$ph) {
	return;
}
$grid_class = $colonne === 3 ? 'lg:grid-cols-3' : 'lg:grid-cols-4';
?>

<section <?= !empty($c['anchor']) ? 'id="' . esc_attr($c['anchor']) . '"' : ''; ?> class="cr-sec griglia-prodotti <?= !empty($c['pattern']) ? 'cr-patt' : ''; ?>" data-th="<?= esc_attr($tema); ?>">
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

			<?php // 2 colonne già sul telefono (design-system §4): a 1 colonna si vedeva un prodotto per schermata ?>
			<div class="grid grid-cols-2 tb:grid-cols-3 <?= $grid_class; ?> gap-3 lg:gap-4" data-fx-stagger>
				<?php if ($ph) : ?>
					<?php for ($k = 0; $k < $colonne; $k++) cr_ph_card(['glass' => $glass]); ?>
				<?php else : ?>
					<?php foreach ($ids as $pid) : ?>
						<?php cr_product_card($pid, [
							'glass'    => $glass,
							'top_deal' => $top_id && (int) $pid === $top_id,
						]); ?>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>

		</div>
	</div>
</section>
