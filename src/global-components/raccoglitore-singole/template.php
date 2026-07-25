<?php

/**
 * RACCOGLITORE SINGOLE (GRID-3) — foglio del raccoglitore con tasche a proporzione carta (63:88).
 * Le singole sono prodotti VARIABILI con attributi "condizione" (scala Cardmarket: MT/NM/EX/GD/LP/PL/PO) e "lingua":
 * la tasca mostra i chip e porta alla PDP per la scelta della variazione.
 * Sorgente AUTOMATICA (cr_singole_products): le variabili più recenti. Nessun campo backend.
 */
$c = is_array($component_data ?? null) ? $component_data : [];
$tema = cr_theme($c);

$ids = cr_singole_products(6);
$ph  = !$ids && CR_PLACEHOLDER; // sezione vuota + modalità sviluppo → tasche di esempio
if (!$ids && !$ph) {
	return;
}
?>

<section class="cr-sec raccoglitore-singole" data-th="<?= esc_attr($tema); ?>">
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

			<!-- il foglio del raccoglitore: glass sopra il fondo di sezione -->
			<div class="cr-glass rounded-[20px] p-4 lg:p-5 shadow-th">
				<div class="grid grid-cols-2 sm:grid-cols-3 tb:grid-cols-4 lg:grid-cols-6 gap-3" data-fx-stagger>
					<?php if ($ph) : ?>
						<?php for ($k = 0; $k < 6; $k++) cr_ph_pocket(); ?>
					<?php else : ?>
						<?php foreach ($ids as $pid) cr_pocket_card($pid); ?>
					<?php endif; ?>
				</div>
			</div>

		</div>
	</div>
</section>
