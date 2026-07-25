<?php

/**
 * CANALI CONTATTO — griglia di card-canale linkate (Telegram, email, Instagram, gruppo, ...).
 * Ogni card È il link: icona + nome + a cosa serve + azione. Niente form (scelta del titolare).
 * Usa la primitiva .cr-card del design system. Campi: tema, eyebrow, titolo, intro (opz.),
 * voci[] (icona, titolo, testo, azione, url, esterno), nota (fine print, opz.), pattern.
 */
$c = is_array($component_data ?? null) ? $component_data : [];
$tema = cr_theme($c);
$voci = is_array($c['voci'] ?? null)
	? array_values(array_filter($c['voci'], fn($v) => !empty($v['url'])))
	: [];
if (!$voci) {
	return;
}

// Icone inline (stroke = currentColor). Chiave in 'icona'; fallback: mail.
$icons = [
	'telegram'  => '<path d="M22 2L11 13M22 2l-7 20-4-9-9-4z"/>',
	'mail'      => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
	'instagram' => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.2" cy="6.8" r="1" fill="currentColor" stroke="none"/>',
	'users'     => '<path d="M17 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9.5" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
	'star'      => '<path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>',
];
?>

<section class="cr-sec canali-contatto <?= !empty($c['pattern']) ? 'cr-patt' : ''; ?>" data-th="<?= esc_attr($tema); ?>">
	<div class="tw-container tw-section">
		<div class="py-14 lg:py-16" data-fx="rise">

			<?php if (!empty($c['eyebrow']) || !empty($c['titolo'])) : ?>
				<div class="max-w-[42ch] mb-10">
					<?php if (!empty($c['eyebrow'])) : ?>
						<div class="cr-eyebrow"><?= esc_html($c['eyebrow']); ?></div>
					<?php endif; ?>
					<?php if (!empty($c['titolo'])) : ?>
						<h2 class="font-metropolis font-bold !text-xl lg:!text-3xl mt-2.5"><?= esc_html($c['titolo']); ?></h2>
					<?php endif; ?>
					<?php if (!empty($c['intro'])) : ?>
						<p class="text-th-muted leading-relaxed mt-4"><?= esc_html($c['intro']); ?></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<div class="grid gap-5 sm:grid-cols-2 tb:grid-cols-3">
				<?php foreach ($voci as $v) :
					$ico = $icons[$v['icona'] ?? 'mail'] ?? $icons['mail'];
					$ext = !empty($v['esterno']);
					?>
					<a class="cr-card p-6 group" href="<?= esc_url($v['url']); ?>"<?= $ext ? ' target="_blank" rel="noopener"' : ''; ?>>
						<div class="flex items-center gap-3">
							<span class="w-11 h-11 grid place-items-center rounded-full border border-th-line text-th-acc shrink-0">
								<svg class="w-5 h-5 stroke-current fill-transparent" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?= $ico; ?></svg>
							</span>
							<span class="font-metropolis font-semibold !text-lg text-th-ink"><?= esc_html($v['titolo'] ?? ''); ?></span>
						</div>
						<?php if (!empty($v['testo'])) : ?>
							<p class="text-th-muted text-sm leading-relaxed mt-3"><?= esc_html($v['testo']); ?></p>
						<?php endif; ?>
						<?php if (!empty($v['azione'])) : ?>
							<span class="inline-flex items-center gap-1.5 mt-4 font-metropolis font-semibold text-sm text-th-acc">
								<?= esc_html($v['azione']); ?>
								<svg class="w-4 h-4 stroke-current fill-transparent transition-transform group-hover:translate-x-0.5" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
							</span>
						<?php endif; ?>
					</a>
				<?php endforeach; ?>
			</div>

			<?php if (!empty($c['nota'])) : ?>
				<p class="text-th-soft text-sm mt-10 [&_a]:underline [&_a]:text-th-muted hover:[&_a]:text-th-acc"><?= wp_kses_post($c['nota']); ?></p>
			<?php endif; ?>

		</div>
	</div>
</section>
