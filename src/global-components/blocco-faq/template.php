<?php

/**
 * BLOCCO FAQ — accordion di domande frequenti riutilizzabile (native <details>, niente JS).
 * Smonta le obiezioni + cattura ricerche a coda lunga; emette anche i dati strutturati FAQPage.
 * Campi: tema, eyebrow, titolo, faq[] (domanda, risposta), pattern (true/false).
 */
$c = is_array($component_data ?? null) ? $component_data : [];
$tema = cr_theme($c);
$faq  = is_array($c['faq'] ?? null)
	? array_values(array_filter($c['faq'], fn($f) => !empty($f['domanda']) && !empty($f['risposta'])))
	: [];
if (!$faq) {
	return;
}
?>

<section class="cr-sec blocco-faq <?= !empty($c['pattern']) ? 'cr-patt' : ''; ?>" data-th="<?= esc_attr($tema); ?>">
	<div class="tw-container tw-section">
		<div class="py-14 lg:py-16 max-w-[760px] mx-auto">

			<?php if (!empty($c['eyebrow']) || !empty($c['titolo'])) : ?>
				<div class="mb-8">
					<?php if (!empty($c['eyebrow'])) : ?>
						<div class="cr-eyebrow"><?= esc_html($c['eyebrow']); ?></div>
					<?php endif; ?>
					<?php if (!empty($c['titolo'])) : ?>
						<h2 class="font-metropolis font-bold !text-xl lg:!text-3xl mt-2.5"><?= esc_html($c['titolo']); ?></h2>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<div class="border-t border-th-line">
				<?php foreach ($faq as $item) : ?>
					<details class="group border-b border-th-line">
						<summary class="flex items-center justify-between gap-4 cursor-pointer list-none py-5 font-metropolis font-semibold !text-base lg:!text-lg [&::-webkit-details-marker]:hidden">
							<span><?= esc_html($item['domanda']); ?></span>
							<svg class="w-5 h-5 shrink-0 stroke-th-acc fill-transparent transition-transform duration-200 group-[[open]]:rotate-45" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg>
						</summary>
						<div class="cr-faq-body overflow-hidden transition-[height] duration-300 ease-out">
							<div class="pb-5 text-th-muted leading-relaxed max-w-[64ch]"><?= esc_html($item['risposta']); ?></div>
						</div>
					</details>
				<?php endforeach; ?>
			</div>

		</div>
	</div>
	<?php
	// Dati strutturati FAQPage (dallo stesso array): utile a ricerca e motori di risposta.
	$ld = [
		'@context'   => 'https://schema.org',
		'@type'      => 'FAQPage',
		'mainEntity' => array_map(fn($f) => [
			'@type'          => 'Question',
			'name'           => wp_strip_all_tags($f['domanda']),
			'acceptedAnswer' => ['@type' => 'Answer', 'text' => wp_strip_all_tags($f['risposta'])],
		], $faq),
	];
	?>
	<script type="application/ld+json"><?= wp_json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
</section>
