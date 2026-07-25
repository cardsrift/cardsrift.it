<?php

/**
 * IN ARRIVO (PRE-3 + PRE-2) — vetrina "sola vista" dei prodotti in arrivo + calendario a righe.
 * NON preordinabili: le card non hanno acquisto. Sorgente AUTOMATICA (cr_preorder_products):
 * i prodotti col campo "data_uscita" valorizzato, ordinati per data crescente. Nessun campo backend.
 */
$c = is_array($component_data ?? null) ? $component_data : [];
$tema = cr_theme($c);
$ids  = cr_preorder_products(12);
$ph   = !$ids && CR_PLACEHOLDER; // sezione vuota + modalità sviluppo → card + calendario di esempio
if (!$ids && !$ph) {
	return;
}
$cards = array_slice($ids, 0, 3);
?>

<section class="cr-sec preordini-uscite" data-th="<?= esc_attr($tema); ?>">
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

			<div class="grid grid-cols-2 tb:grid-cols-3 gap-3 lg:gap-4" data-fx-stagger>
				<?php if ($ph) : ?>
					<?php for ($k = 0; $k < 3; $k++) cr_ph_card(['in_arrivo' => true]); ?>
				<?php else : ?>
					<?php foreach ($cards as $pid) : ?>
						<?php cr_product_card($pid, ['in_arrivo' => true]); ?>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>

			<?php if (!empty($c['mostra_calendario'])) : ?>
				<div class="mt-8 w-fit max-w-full">
					<?php if ($ph) : ?>
						<?php
						// Calendario placeholder (date fittizie in ordine, nomi dal dataset di esempio)
						$ph_dates = ['26/09', '11/10', '25/10', '08/11', '22/11', '06/12'];
						foreach (cr_placeholder_dataset() as $j => $d) :
							$gioco = explode(' · ', $d['chip'])[0];
						?>
							<span class="grid grid-cols-[54px_1fr] tb:grid-cols-[120px_88px_1fr] gap-x-3 gap-y-0.5 lg:gap-5 tb:items-baseline py-4 px-2 w-full border-b border-th-lines last:border-b-0">
								<span class="font-metropolis font-semibold text-xxs uppercase tracking-[0.14em] text-th-soft truncate max-tb:col-start-2 max-tb:row-start-2"><?= esc_html($gioco); ?></span>
								<span class="font-bylon text-sm text-th-acc max-tb:col-start-1 max-tb:row-start-1 max-tb:row-span-2 max-tb:self-center"><?= esc_html($ph_dates[$j % count($ph_dates)]); ?></span>
								<span class="font-metropolis font-medium !text-base text-th-ink line-clamp-2 tb:line-clamp-1 max-tb:col-start-2 max-tb:row-start-1"><?= esc_html($d['name']); ?></span>
							</span>
						<?php endforeach; ?>
					<?php else : ?>
						<?php foreach ($ids as $pid) :
							$product = wc_get_product($pid);
							if (!$product) continue;
							$data  = cr_format_uscita(get_field('data_uscita', $pid));
							$gioco = cr_product_game($pid);
						?>
							<?php // su telefono la riga diventa due piani: data a sinistra, titolo su due righe e gioco sotto.
							// A tre colonne fisse il titolo restava in ~190px e finiva quasi sempre troncato. ?>
							<a class="grid grid-cols-[54px_1fr] tb:grid-cols-[120px_88px_1fr] gap-x-3 gap-y-0.5 lg:gap-5 tb:items-baseline py-4 px-2 w-full border-b border-th-lines last:border-b-0 no-underline transition-all hover:bg-th-accsoft" href="<?= esc_url(get_permalink($pid)); ?>">
								<span class="font-metropolis font-semibold text-xxs uppercase tracking-[0.14em] text-th-soft truncate max-tb:col-start-2 max-tb:row-start-2"><?= esc_html($gioco); ?></span>
								<span class="font-bylon text-sm text-th-acc max-tb:col-start-1 max-tb:row-start-1 max-tb:row-span-2 max-tb:self-center"><?= esc_html($data ?: '—'); ?></span>
								<h3 class="font-metropolis font-medium !text-base text-th-ink line-clamp-2 tb:line-clamp-1 max-tb:col-start-2 max-tb:row-start-1"><?= esc_html($product->get_name()); ?></h3>
							</a>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
			<?php endif; ?>

		</div>
	</div>
</section>
