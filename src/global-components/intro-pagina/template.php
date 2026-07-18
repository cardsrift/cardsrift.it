<?php

/**
 * INTRO PAGINA — intestazione editoriale riutilizzabile per le pagine secondarie
 * (chi-siamo, compriamo-le-tue-carte, ...): eyebrow → titolo → sottotitolo, centrato.
 * Interamente da codice (nessun campo DB). Copy nel manifest content/{slug}.php.
 * Campi: tema, eyebrow, titolo (\n = a capo), sottotitolo, cta_label/cta_url (opz.),
 * pattern (true/false).
 */
$c = is_array($component_data ?? null) ? $component_data : [];
$tema = cr_theme($c);
?>

<section class="cr-sec intro-pagina <?= !empty($c['pattern']) ? 'cr-patt' : ''; ?>" data-th="<?= esc_attr($tema); ?>">
	<div class="tw-container tw-section">
		<div class="py-16 lg:py-24 flex flex-col items-center text-center max-w-[62ch] mx-auto">

			<?php if (!empty($c['eyebrow'])) : ?>
				<div class="cr-eyebrow"><?= esc_html($c['eyebrow']); ?></div>
			<?php endif; ?>

			<?php if (!empty($c['titolo'])) : ?>
				<h1 class="font-metropolis font-bold !text-3xl lg:!text-6xl leading-[1.06] mt-4"><?= nl2br(esc_html($c['titolo'])); ?></h1>
			<?php endif; ?>

			<?php if (!empty($c['sottotitolo'])) : ?>
				<p class="text-th-muted !text-base lg:!text-lg leading-relaxed max-w-[54ch] mt-5"><?= esc_html($c['sottotitolo']); ?></p>
			<?php endif; ?>

			<?php if (!empty($c['cta_label']) && !empty($c['cta_url'])) : ?>
				<div class="mt-8">
					<a class="cr-btn cr-btn-solid" href="<?= esc_url($c['cta_url']); ?>"><?= esc_html($c['cta_label']); ?></a>
				</div>
			<?php endif; ?>

		</div>
	</div>
</section>
