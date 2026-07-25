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

/**
 * Vetrina: 3 prodotti scelti a mano (post_object da ACF).
 *
 * ⚠️ Il campo conserva gli ID anche quando i prodotti spariscono — ed è successo:
 * dopo una re-importazione del catalogo i tre ID scelti non esistevano più e l'hero
 * mostrava tre carte BIANCHE, perché il template si fidava del dato senza verificarlo.
 * Qui si tiene solo ciò che esiste davvero, è pubblicato e ha un'immagine; i posti
 * rimasti scoperti si riempiono da soli con i prodotti più recenti. L'hero è la prima
 * cosa che si vede: non può dipendere dal fatto che qualcuno ricordi di aggiornarlo.
 */
$showcase = [];
$used     = [];
$cr_img_class = ['class' => 'w-full h-full object-contain'];

/** Aggiunge un prodotto alla vetrina, se esiste ed ha una foto vera. */
$cr_add = function ($pid) use (&$showcase, &$used, $cr_img_class) {
	$product = $pid ? wc_get_product($pid) : null;
	if (!$product || $product->get_status() !== 'publish' || !cr_product_has_image($product)) {
		return;
	}
	$used[]     = (int) $pid;
	$showcase[] = ['img' => $product->get_image('woocommerce_thumbnail', $cr_img_class), 'label' => cr_product_chip($pid)];
};

foreach ($vetrina as $slot) {
	$cr_add(is_object($slot) ? $slot->ID : (int) $slot);
}

// Ripiego automatico: i posti scoperti li prendono i prodotti più recenti con foto.
if (count($showcase) < 3 && function_exists('wc_get_products')) {
	foreach (wc_get_products(['status' => 'publish', 'limit' => 20, 'exclude' => $used, 'orderby' => 'date', 'order' => 'DESC', 'return' => 'ids']) as $pid) {
		if (count($showcase) >= 3) {
			break;
		}
		$cr_add($pid);
	}
}

// Ultima spiaggia (negozio ancora vuoto, solo in sviluppo): i placeholder.
if (!$showcase && CR_PLACEHOLDER) {
	$ph = function_exists('wc_placeholder_img') ? wc_placeholder_img('woocommerce_thumbnail', $cr_img_class) : '';
	foreach (['151 · IT', 'MTG · EN', 'Pokémon · IT'] as $ph_label) {
		$showcase[] = ['img' => $ph, 'label' => $ph_label];
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
							<?php // markup dell'immagine già pronto: viene da $product->get_image(),
							// la stessa API delle card (le foto sono URL remoti del plugin di sync) ?>
							<?php // Sono le tre foto in cima alla home: differirle peggiora l'LCP, e il
						// plugin di sync scrive loading="lazy" comunque. La priorità alta va solo
						// alla prima, altrimenti si annullano a vicenda. ?>
						<?= cr_eager_image($s['img'], $i === 0); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
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
