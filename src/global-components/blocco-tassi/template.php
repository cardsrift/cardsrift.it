<?php

/**
 * BLOCCO TASSI — listino trasparente "quanto pago / quanto costa": righe tipo → valore,
 * con nota per riga e una fine-print finale. Il valore è in accent, allineato a destra.
 * Stesso impianto editoriale di blocco-metodo (titolo a sinistra, righe a destra).
 * Riutilizzabile per qualsiasi tariffario. Campi: tema, eyebrow, titolo, intro (opz.),
 * voci[] (tipo, nota, valore), nota (fine print, opz.), pattern (true/false).
 */
$c = is_array($component_data ?? null) ? $component_data : [];
$tema = cr_theme($c);
$voci = is_array($c['voci'] ?? null)
	? array_values(array_filter($c['voci'], fn($v) => !empty($v['tipo']) || !empty($v['valore'])))
	: [];
if (!$voci) {
	return;
}
?>

<section class="cr-sec blocco-tassi <?= !empty($c['pattern']) ? 'cr-patt' : ''; ?>" data-th="<?= esc_attr($tema); ?>">
	<div class="tw-container tw-section">
		<div class="py-14 lg:py-16 grid gap-8 tb:gap-10 lg:gap-16 tb:grid-cols-[minmax(0,17rem)_1fr] lg:grid-cols-[minmax(0,20rem)_1fr]" data-fx="rise">

			<div>
				<?php if (!empty($c['eyebrow'])) : ?>
					<div class="cr-eyebrow"><?= esc_html($c['eyebrow']); ?></div>
				<?php endif; ?>
				<?php if (!empty($c['titolo'])) : ?>
					<h2 class="font-metropolis font-bold !text-xl lg:!text-3xl mt-2.5"><?= esc_html($c['titolo']); ?></h2>
				<?php endif; ?>
				<?php if (!empty($c['intro'])) : ?>
					<p class="text-th-muted leading-relaxed max-w-[38ch] mt-4"><?= esc_html($c['intro']); ?></p>
				<?php endif; ?>
			</div>

			<div class="max-w-[62ch]">
				<dl>
					<?php foreach ($voci as $v) : ?>
						<div class="flex items-baseline justify-between gap-6 py-5 border-t border-th-line first:border-t-0 first:pt-0">
							<dt>
								<span class="block font-metropolis font-semibold !text-base text-th-ink"><?= esc_html($v['tipo'] ?? ''); ?></span>
								<?php if (!empty($v['nota'])) : ?>
									<span class="block text-th-muted text-sm mt-1"><?= esc_html($v['nota']); ?></span>
								<?php endif; ?>
							</dt>
							<dd class="font-metropolis font-bold !text-xl lg:!text-2xl text-th-acc tabular-nums whitespace-nowrap shrink-0"><?= esc_html($v['valore'] ?? ''); ?></dd>
						</div>
					<?php endforeach; ?>
				</dl>

				<?php if (!empty($c['nota'])) : ?>
					<p class="text-th-muted text-sm leading-relaxed max-w-[60ch] mt-6 pt-5 border-t border-th-line"><?= esc_html($c['nota']); ?></p>
				<?php endif; ?>
			</div>

		</div>
	</div>
</section>
