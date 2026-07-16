<?php

/**
 * PREORDINI E USCITE (PRE-3 + PRE-2) — card preordine + calendario a righe.
 * La data viene dal campo ACF "data_uscita" sul prodotto (testo, es. "26/09").
 * Layout ACF: preordini_uscite — campi: tema, eyebrow, titolo, link_label/link_url,
 * prodotti (relationship, ordinati a mano dall'editor), mostra_calendario (true/false)
 */
$c = is_array($component_data ?? null) ? $component_data : [];
$tema = cr_theme($c);
$ids  = array_map(fn($p) => is_object($p) ? $p->ID : (int) $p, (array) ($c['prodotti'] ?? []));
$ids  = array_filter($ids);
if (!$ids) {
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

			<div class="grid grid-cols-2 lg:grid-cols-3 gap-3 lg:gap-4">
				<?php foreach ($cards as $pid) : ?>
					<?php cr_product_card($pid, ['preordine' => true]); ?>
				<?php endforeach; ?>
			</div>

			<?php if (!empty($c['mostra_calendario'])) : ?>
				<div class="mt-8 border-t border-th-lines">
					<?php foreach ($ids as $pid) :
						$product = wc_get_product($pid);
						if (!$product) continue;
						$data  = get_field('data_uscita', $pid);
						$terms = get_the_terms($pid, 'product_cat');
						$gioco = $terms && !is_wp_error($terms) ? $terms[0]->name : '';
					?>
						<a class="grid grid-cols-[80px_1fr] lg:grid-cols-[96px_1fr_auto_auto] gap-3 lg:gap-6 items-baseline py-4 px-2 border-b border-th-line no-underline transition-all hover:bg-th-accsoft" href="<?= esc_url(get_permalink($pid)); ?>">
							<span class="font-bylon text-sm text-th-acc"><?= esc_html($data ?: '—'); ?></span>
							<h3 class="font-metropolis font-medium !text-base text-th-ink"><?= esc_html($product->get_name()); ?></h3>
							<span class="max-lg:hidden font-metropolis text-xxs uppercase tracking-[0.16em] text-th-soft"><?= esc_html($gioco); ?></span>
							<span class="max-lg:hidden font-metropolis font-semibold text-sm text-th-acc tabular-nums"><?= __('Preordina', 'cardsrift'); ?> — <?= wp_strip_all_tags($product->get_price_html()); ?></span>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

		</div>
	</div>
</section>
