<?php

/**
 * HERO VETRINA (HERO-6) — hero di default: claim quieto + vetrina di 3 prodotti inclinati.
 * Layout ACF: hero_vetrina — campi: tema, eyebrow, titolo, sottotitolo,
 * cta_label/cta_url, cta2_label/cta2_url, vetrina (repeater: prodotto, etichetta),
 * trust (repeater: testo, evidenzia)
 */
$c = is_array($component_data ?? null) ? $component_data : [];
$tema     = cr_theme($c);
$eyebrow  = $c['eyebrow'] ?? __('Il tuo portale per il collezionismo', 'cardsrift');
$titolo   = $c['titolo'] ?? '';
$sotto    = $c['sottotitolo'] ?? '';
$vetrina  = is_array($c['vetrina'] ?? null) ? array_slice($c['vetrina'], 0, 3) : [];
$trust    = is_array($c['trust'] ?? null) ? $c['trust'] : [];
$rotazioni = ['-rotate-6 left-[5%] top-[13%] z-[2]', 'rotate-2 left-[35%] top-[2%] z-[3] scale-110', 'rotate-[8deg] left-[63%] top-[26%] z-[1]'];
?>

<section class="cr-sec cr-patt hero-vetrina" data-th="<?= esc_attr($tema); ?>">
	<div class="tw-container tw-section">
		<div class="grid lg:grid-cols-2 gap-12 items-center py-16 lg:py-20">

			<div>
				<?php if ($eyebrow) : ?>
					<div class="cr-eyebrow"><?= esc_html($eyebrow); ?></div>
				<?php endif; ?>

				<?php if ($titolo) : ?>
					<h1 class="font-metropolis font-bold !text-4xl lg:!text-7xl leading-[1.07] mt-4 mb-4"><?= nl2br(esc_html($titolo)); ?></h1>
				<?php endif; ?>

				<?php if ($sotto) : ?>
					<p class="text-md text-th-muted max-w-[46ch] mb-7"><?= esc_html($sotto); ?></p>
				<?php endif; ?>

				<div class="flex gap-3.5 flex-wrap">
					<?php if (!empty($c['cta_label']) && !empty($c['cta_url'])) : ?>
						<a class="cr-btn cr-btn-solid" href="<?= esc_url($c['cta_url']); ?>"><?= esc_html($c['cta_label']); ?></a>
					<?php endif; ?>
					<?php if (!empty($c['cta2_label']) && !empty($c['cta2_url'])) : ?>
						<a class="cr-btn cr-btn-glass" href="<?= esc_url($c['cta2_url']); ?>"><?= esc_html($c['cta2_label']); ?></a>
					<?php endif; ?>
				</div>

				<?php if ($trust) : ?>
					<div class="flex gap-5 flex-wrap mt-8 font-metropolis font-medium text-sm text-th-muted">
						<?php foreach ($trust as $t) : ?>
							<span><?php if (!empty($t['evidenzia'])) : ?><b class="text-th-ink"><?= esc_html($t['evidenzia']); ?></b> <?php endif; ?><?= esc_html($t['testo'] ?? ''); ?></span>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>

			<?php if ($vetrina) : ?>
				<div class="hero-vetrina__showcase relative h-[340px] lg:h-[410px] max-lg:max-w-[540px]" aria-hidden="true">
					<?php foreach ($vetrina as $i => $slot) :
						$pid = is_object($slot['prodotto'] ?? null) ? $slot['prodotto']->ID : (int) ($slot['prodotto'] ?? 0);
						if (!$pid) continue;
						$img = get_the_post_thumbnail_url($pid, 'woocommerce_thumbnail');
						$etichetta = $slot['etichetta'] ?? '';
					?>
						<div class="absolute w-[47%] max-w-[255px] aspect-[5/6] bg-white-pure rounded-2xl p-4 shadow-th border border-th-line <?= esc_attr($rotazioni[$i] ?? ''); ?>">
							<?php if ($img) : ?>
								<img class="w-full h-full object-contain" src="<?= esc_url($img); ?>" alt="">
							<?php endif; ?>
							<?php if ($etichetta) : ?>
								<span class="absolute -bottom-3 left-1/2 -translate-x-1/2 bg-black text-white font-metropolis font-bold text-xs rounded-full px-3.5 py-1 whitespace-nowrap"><?= esc_html($etichetta); ?></span>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

		</div>
	</div>
</section>
