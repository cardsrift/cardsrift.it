<?php

/**
 * BULK BANNER (Bulk A) — "Compriamo le tue carte": teaser con la tariffa in evidenza.
 * È il canale di rifornimento delle singole: il banner porta alla pagina dedicata.
 * Campi: tema, percentuale, percentuale_label, eyebrow, titolo,
 * testo, cta_label/cta_url, microtrust (repeater: testo), pattern (true/false)
 */
$c = is_array($component_data ?? null) ? $component_data : [];
$tema  = cr_theme($c);
$micro = is_array($c['microtrust'] ?? null) ? $c['microtrust'] : [];
?>

<section class="cr-sec bulk-banner <?= !empty($c['pattern']) ? 'cr-patt' : ''; ?>" data-th="<?= esc_attr($tema); ?>">
	<div class="tw-container tw-section">
		<div class="py-14 flex items-center gap-12 flex-wrap">

			<?php /* ⚠️ NON rimettere flex-none: la colonna non si stringerebbe e l'etichetta
			         (28 caratteri con tracking .18em ≈ 367px) uscirebbe dai telefoni stretti,
			         dove il contenitore ne ha 327 (375 − 48 di gutter). Con flex-initial si
			         restringe e va a capo solo dove serve: fino a tb resta affiancata al testo. */ ?>
			<?php if (!empty($c['percentuale'])) : ?>
				<div class="text-center flex-initial max-lg:mx-auto">
					<span class="block font-metropolis font-bold !text-7xl lg:!text-9xl leading-[0.95] text-th-acc tabular-nums"><?= esc_html($c['percentuale']); ?></span>
					<?php if (!empty($c['percentuale_label'])) : ?>
						<span class="block font-bylon text-xs uppercase tracking-[0.18em] text-th-muted mt-2.5"><?= esc_html($c['percentuale_label']); ?></span>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<div class="flex-1 min-w-[280px]">
				<?php if (!empty($c['eyebrow'])) : ?>
					<div class="cr-eyebrow"><?= esc_html($c['eyebrow']); ?></div>
				<?php endif; ?>
				<?php if (!empty($c['titolo'])) : ?>
					<h2 class="font-metropolis font-bold !text-xl lg:!text-3xl mt-2.5 mb-2.5"><?= esc_html($c['titolo']); ?></h2>
				<?php endif; ?>
				<?php if (!empty($c['testo'])) : ?>
					<p class="text-th-muted max-w-[56ch] mb-5"><?= esc_html($c['testo']); ?></p>
				<?php endif; ?>

				<?php if (!empty($c['cta_label']) && !empty($c['cta_url'])) : ?>
					<a class="cr-btn cr-btn-solid" href="<?= esc_url($c['cta_url']); ?>"><?= esc_html($c['cta_label']); ?></a>
				<?php endif; ?>

				<?php if ($micro) : ?>
					<div class="flex gap-4 flex-wrap mt-4 font-metropolis font-medium text-xs text-th-muted">
						<?php foreach ($micro as $m) : ?>
							<span class="flex items-center gap-1.5">
								<svg class="w-3.5 h-3.5 stroke-th-ok fill-transparent" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round"><path d="M5 13l4 4 10-10"/></svg>
								<?= esc_html($m['testo'] ?? ''); ?>
							</span>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>

		</div>
	</div>
</section>
