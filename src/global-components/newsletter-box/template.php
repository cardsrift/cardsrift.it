<?php

/**
 * NEWSLETTER BOX (NL) — card glass centrata con incentivo primo ordine.
 * Campi: tema, eyebrow, titolo, testo, micro, pattern (true/false) + UNO tra:
 *   - cta_label/cta_url → modalità "invito" (bottone, es. registrazione account);
 *   - form_shortcode    → form del provider newsletter (es. Brevo);
 *   - (nessuno)         → form statico placeholder.
 */
$c = is_array($component_data ?? null) ? $component_data : [];
$tema = cr_theme($c);
?>

<section class="cr-sec newsletter-box <?= !empty($c['pattern']) ? 'cr-patt' : ''; ?>" data-th="<?= esc_attr($tema); ?>">
	<div class="tw-container tw-section">
		<div class="py-16">
			<div class="cr-glass rounded-[20px] shadow-th max-w-[640px] mx-auto text-center px-8 py-10 lg:px-10">

				<?php if (!empty($c['eyebrow'])) : ?>
					<div class="cr-eyebrow"><?= esc_html($c['eyebrow']); ?></div>
				<?php endif; ?>
				<?php if (!empty($c['titolo'])) : ?>
					<h2 class="font-metropolis font-bold !text-lg lg:!text-2xl mt-3 mb-2"><?= esc_html($c['titolo']); ?></h2>
				<?php endif; ?>
				<?php if (!empty($c['testo'])) : ?>
					<p class="text-th-muted text-sm mb-5"><?= esc_html($c['testo']); ?></p>
				<?php endif; ?>

				<?php if (!empty($c['cta_url'])) : ?>
					<a class="cr-btn cr-btn-solid" href="<?= esc_url($c['cta_url']); ?>"><?= esc_html($c['cta_label'] ?? __('Crea un account', 'cardsrift')); ?></a>
				<?php elseif (!empty($c['form_shortcode'])) : ?>
					<div class="newsletter-box__form"><?= do_shortcode($c['form_shortcode']); ?></div>
				<?php else : ?>
					<!-- placeholder statico finché non c'è il provider (Fase 3) -->
					<form class="flex gap-2.5 max-w-[430px] mx-auto max-sm:flex-col" onsubmit="return false">
						<input class="cr-input flex-1 min-w-0" type="email" placeholder="<?= esc_attr__('La tua email', 'cardsrift'); ?>" aria-label="Email">
						<button class="cr-btn cr-btn-solid justify-center" type="submit"><?= __('Iscriviti', 'cardsrift'); ?></button>
					</form>
				<?php endif; ?>

				<?php if (!empty($c['micro'])) : ?>
					<p class="text-xxs text-th-soft mt-3.5 mb-0"><?= esc_html($c['micro']); ?></p>
				<?php endif; ?>

			</div>
		</div>
	</div>
</section>
