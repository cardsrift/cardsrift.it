<?php

/**
 * HERO DROP (HERO-3) — hero alternativo quando c'è un preordine in arrivo:
 * countdown discreto nel chip + ventaglio di carte (effetto holo in fase effetti).
 * Layout ACF: hero_drop — campi: tema, eyebrow, titolo_thin, titolo, data_drop (Y-m-d H:i:s),
 * chip_testo, cta_label/cta_url, link_label/link_url, ventaglio (repeater: prodotto)
 * Se la data del drop è passata, in homepage va rimesso hero_vetrina (fallback editoriale).
 */
$c = is_array($component_data ?? null) ? $component_data : [];
$tema      = cr_theme($c);
$titolo    = $c['titolo'] ?? '';
$data_drop = $c['data_drop'] ?? '';
$ventaglio = is_array($c['ventaglio'] ?? null) ? array_slice($c['ventaglio'], 0, 3) : [];
$pos = [
	'-rotate-[9deg] left-[calc(50%-272px)] max-sm:left-[calc(50%-182px)] top-[50px] z-[1]',
	'rotate-[0.5deg] left-[calc(50%-108px)] max-sm:left-[calc(50%-83px)] top-[6px] z-[2] scale-110',
	'rotate-[9deg] left-[calc(50%+78px)] max-sm:left-[calc(50%+36px)] top-[56px] z-[1]',
];
?>

<section class="cr-sec hero-drop overflow-hidden text-center" data-th="<?= esc_attr($tema); ?>">
	<div class="tw-container tw-section relative">
		<div class="pt-14 pb-8">

			<?php if (!empty($c['eyebrow'])) : ?>
				<div class="cr-eyebrow italic"><?= esc_html($c['eyebrow']); ?></div>
			<?php endif; ?>

			<h1 class="font-bylon uppercase !text-5xl lg:!text-9xl leading-none mt-3.5 mb-2">
				<?php if (!empty($c['titolo_thin'])) : ?>
					<span class="block font-metropolis font-light tracking-[0.32em] text-sm lg:text-xl text-th-muted mb-3"><?= esc_html($c['titolo_thin']); ?></span>
				<?php endif; ?>
				<?= esc_html($titolo); ?>
			</h1>

			<?php if ($data_drop) : ?>
				<div class="cr-dropchip my-3" data-cr-countdown data-target="<?= esc_attr($data_drop); ?>">
					<?= esc_html($c['chip_testo'] ?? __('Drop tra', 'cardsrift')); ?>
					<b class="text-th-acc tabular-nums"><span data-cd="d">–</span>g <span data-cd="h">–</span>h</b>
				</div>
			<?php endif; ?>

			<div class="flex gap-3.5 justify-center items-center flex-wrap mt-4">
				<?php if (!empty($c['cta_label']) && !empty($c['cta_url'])) : ?>
					<a class="cr-btn cr-btn-solid" href="<?= esc_url($c['cta_url']); ?>"><?= esc_html($c['cta_label']); ?></a>
				<?php endif; ?>
				<?php if (!empty($c['link_label']) && !empty($c['link_url'])) : ?>
					<a class="font-metropolis font-semibold text-xs uppercase tracking-wider text-th-muted hover:text-th-acc no-underline" href="<?= esc_url($c['link_url']); ?>"><?= esc_html($c['link_label']); ?> →</a>
				<?php endif; ?>
			</div>

			<?php if ($ventaglio) : ?>
				<!-- ventaglio carte: il tilt 3D + foil arriva nella fase effetti (js su [data-cr-fan]) -->
				<div class="relative h-[250px] sm:h-[320px] lg:h-[370px] mt-6 max-w-[780px] mx-auto" data-cr-fan aria-hidden="true">
					<?php foreach ($ventaglio as $i => $slot) :
						$pid = is_object($slot['prodotto'] ?? null) ? $slot['prodotto']->ID : (int) ($slot['prodotto'] ?? 0);
						if (!$pid) continue;
						// Stessa API delle card: le foto sono URL remoti del plugin di sync,
						// non allegati della libreria media (vedi cr_product_has_image).
						$cr_p = wc_get_product($pid);
						$img  = $cr_p ? $cr_p->get_image('woocommerce_single', ['class' => 'w-full h-full object-contain']) : '';
						$etichetta = cr_product_chip($pid);
					?>
						<div class="absolute w-[150px] sm:w-[215px] aspect-[4/5] <?= esc_attr($pos[$i] ?? ''); ?>">
							<div class="w-full h-full bg-white-pure rounded-2xl p-3.5 shadow-th overflow-hidden" data-cr-holo>
								<?= $img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</div>
							<?php if ($etichetta) : ?>
								<span class="absolute -bottom-3 left-1/2 -translate-x-1/2 <?= $tema === 'dark' ? 'bg-purple-deep' : 'bg-black'; ?> text-white font-metropolis font-bold text-xs rounded-full px-3.5 py-1 whitespace-nowrap"><?= esc_html($etichetta); ?></span>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

		</div>
	</div>
</section>
