<?php

/**
 * HERO VETRINA (HERO-6) — hero di default: claim quieto + vetrina di 3 prodotti inclinati.
 * Copy dal manifest (content/home.php). Unico dato da backend: "vetrina" = 3 prodotti
 * (campo ACF generato dall'engine); l'etichetta pill è dedotta in automatico dal prodotto.
 */
$c = is_array($component_data ?? null) ? $component_data : [];
$tema     = cr_theme($c);
$eyebrow  = $c['eyebrow'] ?? __('Il tuo portale per il collezionismo', 'cardsrift');
$titolo   = $c['titolo'] ?? '';
$sotto    = $c['sottotitolo'] ?? '';
$vetrina  = array_slice(array_filter((array) ($c['vetrina'] ?? [])), 0, 3);
$trust    = is_array($c['trust'] ?? null) ? $c['trust'] : [];
$rotazioni = ['-rotate-6 left-[5%] top-[13%] z-[2]', 'rotate-2 left-[35%] top-[2%] z-[3] scale-110', 'rotate-[8deg] left-[63%] top-[26%] z-[1]'];

// Vetrina: prodotti reali (3 post_object da ACF) o, se vuota, placeholder in sviluppo.
$showcase = [];
if ($vetrina) {
	foreach ($vetrina as $slot) {
		$pid = is_object($slot) ? $slot->ID : (int) $slot;
		if ($pid) {
			$showcase[] = ['img' => get_the_post_thumbnail_url($pid, 'woocommerce_thumbnail'), 'label' => cr_product_chip($pid)];
		}
	}
} elseif (CR_PLACEHOLDER) {
	$ph_img = function_exists('wc_placeholder_img_src') ? wc_placeholder_img_src('woocommerce_thumbnail') : '';
	foreach (['151 · IT', 'OP-10 · JP', 'MTG · EN'] as $ph_label) {
		$showcase[] = ['img' => $ph_img, 'label' => $ph_label];
	}
}
?>

<section class="cr-sec cr-patt hero-vetrina relative overflow-hidden" data-th="<?= esc_attr($tema); ?>">
	<div class="cr-orbs" data-cr-orbs aria-hidden="true"><div class="cr-orbs__layer"><span class="cr-orb cr-orb--1"></span><span class="cr-orb cr-orb--2"></span><span class="cr-orb cr-orb--3"></span></div></div>
	<div class="tw-container tw-section relative z-[1]">
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

			<?php if ($showcase) : ?>
				<div class="hero-vetrina__showcase relative h-[340px] lg:h-[410px] max-lg:max-w-[540px]" aria-hidden="true">
					<?php foreach ($showcase as $i => $s) : ?>
						<div class="hero-vetrina__card absolute w-[47%] max-w-[255px] aspect-[5/6] <?= esc_attr($rotazioni[$i] ?? ''); ?>" data-cr-tilt>
								<div class="relative w-full h-full bg-white-pure rounded-2xl p-4 shadow-th border border-th-line overflow-hidden">
							<?php if ($s['img']) : ?>
								<img class="w-full h-full object-contain" src="<?= esc_url($s['img']); ?>" alt="">
							<?php endif; ?>
								<span class="cr-glare"></span>
							</div>
							<?php if ($s['label']) : ?>
								<span class="absolute -bottom-3 left-1/2 -translate-x-1/2 <?= $tema === 'dark' ? 'bg-purple-deep' : 'bg-black'; ?> text-white font-metropolis font-bold text-xs rounded-full px-3.5 py-1 whitespace-nowrap"><?= esc_html($s['label']); ?></span>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

		</div>
	</div>
</section>
