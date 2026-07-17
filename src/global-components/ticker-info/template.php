<?php

/**
 * TICKER INFO (BAR-3) — striscia glass a scorrimento con i movimenti del negozio.
 * Voci AUTOMATICHE (cr_ticker_voci): 2 messaggi brand fissi + fino a 2 dal negozio
 * (ultimo arrivo, un prodotto in offerta). Nessun inserimento a mano.
 *
 * Loop infinito senza vuoti: il track è composto da 2 METÀ identiche e l'animazione
 * trasla del 50% (= una metà). Perché non compaiano buchi, ogni metà deve superare la
 * larghezza dello schermo: ripetiamo le voci quanto serve (target ~16 elementi per metà).
 * La durata scala col contenuto → velocità di scorrimento costante.
 * Si ferma su hover e con prefers-reduced-motion (gestito in design-system.css).
 */
$c = is_array($component_data ?? null) ? $component_data : [];
$tema = cr_theme($c);
$voci = cr_ticker_voci();
if (!$voci) {
	return;
}
$per_half   = max(2, (int) ceil(16 / count($voci))); // ripetizioni per metà (riempie oltre il viewport)
$items_half = count($voci) * $per_half;
$dur        = max(30, (int) round($items_half * 6));  // ~6s per voce → velocità costante
?>

<div class="cr-sec ticker-info" data-th="<?= esc_attr($tema); ?>">
	<div class="cr-ticker" aria-hidden="true">
		<div class="cr-ticker__track" style="animation-duration: <?= (int) $dur; ?>s">
			<?php for ($half = 0; $half < 2; $half++) : ?>
				<?php for ($r = 0; $r < $per_half; $r++) : ?>
					<?php foreach ($voci as $v) : ?>
						<span class="font-metropolis font-semibold text-xs uppercase tracking-[0.12em] text-th-muted px-6 whitespace-nowrap">
							<?= esc_html($v['testo'] ?? ''); ?>
							<?php if (!empty($v['evidenzia'])) : ?>
								— <b class="text-th-acc"><?= esc_html($v['evidenzia']); ?></b>
							<?php endif; ?>
						</span>
					<?php endforeach; ?>
				<?php endfor; ?>
			<?php endfor; ?>
		</div>
	</div>
</div>
