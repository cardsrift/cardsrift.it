<?php

/**
 * TICKER INFO (BAR-3) — striscia glass a scorrimento con i movimenti del negozio.
 * Layout ACF: ticker_info — campi: tema, voci (repeater: testo, evidenzia)
 * Il contenuto è duplicato due volte per il loop seamless; si ferma su hover
 * e con prefers-reduced-motion (gestito in design-system.css).
 */
$c = is_array($component_data ?? null) ? $component_data : [];
$tema = cr_theme($c);
$voci = is_array($c['voci'] ?? null) ? $c['voci'] : [];
if (!$voci) {
	return;
}
?>

<div class="cr-sec ticker-info" data-th="<?= esc_attr($tema); ?>">
	<div class="cr-ticker" aria-hidden="true">
		<div class="cr-ticker__track">
			<?php for ($loop = 0; $loop < 2; $loop++) : ?>
				<?php foreach ($voci as $v) : ?>
					<span class="font-metropolis font-semibold text-xs uppercase tracking-[0.12em] text-th-muted px-6 whitespace-nowrap">
						<?= esc_html($v['testo'] ?? ''); ?>
						<?php if (!empty($v['evidenzia'])) : ?>
							— <b class="text-th-acc"><?= esc_html($v['evidenzia']); ?></b>
						<?php endif; ?>
					</span>
				<?php endforeach; ?>
			<?php endfor; ?>
		</div>
	</div>
</div>
