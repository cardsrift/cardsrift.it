<?php

/**
 * RACCOGLITORE SINGOLE (GRID-3) — foglio del raccoglitore con tasche a proporzione carta (63:88).
 * Le singole sono prodotti VARIABILI con attributi "condizione" (NM/LP/MP/HP) e "lingua":
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
				<div class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-6 gap-3">
					<?php if ($ph) : ?>
						<?php for ($k = 0; $k < 6; $k++) cr_ph_pocket(); ?>
					<?php else : ?>
						<?php foreach ($ids as $pid) :
							$product = wc_get_product($pid);
							if (!$product) continue;
							$cond   = $product->get_attribute('condizione');
							$lingua = $product->get_attribute('lingua');
							// per i variabili gli attributi possono elencare più valori: mostra il primo
							$cond   = $cond ? trim(explode(',', $cond)[0]) : '';
							$lingua = $lingua ? trim(explode(',', $lingua)[0]) : '';
						?>
							<a class="cr-pocket" href="<?= esc_url(get_permalink($pid)); ?>">
								<span class="cr-pocket__well">
									<?= $product->get_image('woocommerce_thumbnail'); ?>
								</span>
								<span class="flex flex-col gap-1 pt-2 px-0.5 pb-1">
									<span class="flex gap-1 flex-wrap">
										<?php if ($cond) : ?><span class="cr-cchip cr-cchip--cond"><?= esc_html($cond); ?></span><?php endif; ?>
										<?php if ($lingua) : ?><span class="cr-cchip"><?= esc_html($lingua); ?></span><?php endif; ?>
									</span>
									<span class="font-metropolis font-semibold text-xs leading-tight text-th-ink min-h-[2.6em]"><?= esc_html($product->get_name()); ?></span>
									<span class="cr-price !text-sm"><?= $product->get_price_html(); ?></span>
								</span>
								<span class="cr-pocket__pick"><?= $product->is_type('variable') ? __('Scegli condizione', 'cardsrift') : __('Vedi carta', 'cardsrift'); ?></span>
							</a>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
			</div>

		</div>
	</div>
</section>
