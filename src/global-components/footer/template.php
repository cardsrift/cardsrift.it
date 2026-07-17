<?php

/**
 * FOOTER (FOOT-2) — dark, design system. Struttura fedele all'artifact di riferimento:
 * brand + about + social · Il negozio · Assistenza · Contatti · barra copyright + pagamenti.
 * Copy in codice (i18n). Unico dato da backend: il logo (immagine, opzioni tema).
 */
$logo = get_field('logo', 'option');
$logo_url = is_array($logo) ? ($logo['url'] ?? '') : (string) $logo;
$tpl = get_template_directory_uri();
?>

<footer class="cr-sec footer-rework" data-th="dark">
	<div class="tw-container tw-section">
		<div class="py-14 lg:py-16">

			<div class="grid gap-10 lg:gap-8 lg:grid-cols-[1.5fr_1fr_1fr_1fr]">

				<!-- Brand -->
				<div>
					<?php if ($logo_url) : ?>
						<a href="<?= esc_url(home_url('/')); ?>" aria-label="CardsRift" class="inline-block">
							<?php // footer sempre dark (data-th="dark"): il logo va forzato bianco (design-system §6 "bianco su dark").
							// brightness-0 + invert rende bianco QUALSIASI logo caricato in ACF (colored/black), a prova di rebrand. ?>
							<img class="h-9 w-auto brightness-0 invert" src="<?= esc_url($logo_url); ?>" alt="CardsRift">
						</a>
					<?php endif; ?>
					<p class="text-th-muted text-sm leading-relaxed max-w-[34ch] mt-5">
						<?= esc_html__('Il tuo portale per il collezionismo: carte controllate una a una, protette e spedite in 24/48h.', 'cardsrift'); ?>
					</p>
					<div class="flex gap-3 mt-6">
						<a href="https://www.instagram.com/cardsrift/" target="_blank" rel="noopener" aria-label="Instagram" class="w-9 h-9 grid place-items-center rounded-full border border-th-line text-th-ink hover:text-th-acc hover:border-th-acc transition-colors">
							<svg class="w-4 h-4 stroke-current fill-transparent" viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.2" cy="6.8" r="1" fill="currentColor" stroke="none"/></svg>
						</a>
						<a href="<?= esc_url(CR_TELEGRAM_URL); ?>" target="_blank" rel="noopener" aria-label="Telegram" class="w-9 h-9 grid place-items-center rounded-full border border-th-line text-th-ink hover:text-th-acc hover:border-th-acc transition-colors">
							<svg class="w-4 h-4 stroke-current fill-transparent" viewBox="0 0 24 24" stroke-width="2" stroke-linejoin="round"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4z"/></svg>
						</a>
					</div>
				</div>

				<!-- Il negozio -->
				<div>
					<h4 class="font-metropolis font-semibold text-xs uppercase tracking-[0.16em] text-th-soft mb-4"><?= esc_html__('Il negozio', 'cardsrift'); ?></h4>
					<ul class="flex flex-col gap-2.5 text-sm text-th-muted">
						<li><a class="hover:text-th-acc transition-colors" href="/chi-siamo"><?= esc_html__('Il progetto', 'cardsrift'); ?></a></li>
						<li><a class="hover:text-th-acc transition-colors" href="/come-imballiamo"><?= esc_html__('Come imballiamo', 'cardsrift'); ?></a></li>
						<li><a class="hover:text-th-acc transition-colors" href="/recensioni"><?= esc_html__('Recensioni', 'cardsrift'); ?></a></li>
						<li><a class="hover:text-th-acc transition-colors" href="<?= esc_url(CR_TELEGRAM_URL); ?>" target="_blank" rel="noopener"><?= esc_html__('Community Telegram', 'cardsrift'); ?></a></li>
					</ul>
				</div>

				<!-- Assistenza -->
				<div>
					<h4 class="font-metropolis font-semibold text-xs uppercase tracking-[0.16em] text-th-soft mb-4"><?= esc_html__('Assistenza', 'cardsrift'); ?></h4>
					<ul class="flex flex-col gap-2.5 text-sm text-th-muted">
						<li><a class="hover:text-th-acc transition-colors" href="/spedizioni-e-resi"><?= esc_html__('Spedizioni e resi', 'cardsrift'); ?></a></li>
						<li><a class="hover:text-th-acc transition-colors" href="/guida-alle-condizioni"><?= esc_html__('Guida alle condizioni', 'cardsrift'); ?></a></li>
						<li><a class="hover:text-th-acc transition-colors" href="/faq"><?= esc_html__('Domande frequenti', 'cardsrift'); ?></a></li>
						<li><a class="hover:text-th-acc transition-colors" href="/contatti"><?= esc_html__('Contatti', 'cardsrift'); ?></a></li>
					</ul>
				</div>

				<!-- Contatti -->
				<div>
					<h4 class="font-metropolis font-semibold text-xs uppercase tracking-[0.16em] text-th-soft mb-4"><?= esc_html__('Contatti', 'cardsrift'); ?></h4>
					<ul class="flex flex-col gap-2.5 text-sm text-th-muted">
						<li><a class="hover:text-th-acc transition-colors" href="mailto:cardsrift@gmail.com">cardsrift@gmail.com</a></li>
						<li>P.IVA 13934020960</li>
					</ul>
				</div>

			</div>

			<!-- Barra inferiore -->
			<div class="mt-12 pt-6 border-t border-th-line flex flex-col sm:flex-row items-center justify-between gap-4">
				<div class="flex items-center gap-3 text-xs text-th-soft">
					<span>&copy; <?= esc_html(gmdate('Y')); ?> CardsRift</span>
					<span aria-hidden="true">·</span>
					<a class="iubenda-nostyle iubenda-noiframe iubenda-embed iubenda-noiframe hover:text-th-acc transition-colors" href="https://www.iubenda.com/privacy-policy/20607045" title="Privacy Policy"><?= esc_html__('Privacy', 'cardsrift'); ?></a>
					<span aria-hidden="true">·</span>
					<a class="iubenda-nostyle iubenda-noiframe iubenda-embed iubenda-noiframe hover:text-th-acc transition-colors" href="https://www.iubenda.com/privacy-policy/20607045/cookie-policy" title="Cookie Policy"><?= esc_html__('Cookie', 'cardsrift'); ?></a>
				</div>
				<ul class="flex items-center gap-3">
					<li class="w-9 h-6"><img class="w-full h-full object-contain" src="<?= esc_url($tpl . '/assets/images/visa.svg'); ?>" alt="Visa"></li>
					<li class="w-9 h-6"><img class="w-full h-full object-contain" src="<?= esc_url($tpl . '/assets/images/mastercard.svg'); ?>" alt="Mastercard"></li>
					<li class="w-9 h-6"><img class="w-full h-full object-contain" src="<?= esc_url($tpl . '/assets/images/paypal.svg'); ?>" alt="PayPal"></li>
				</ul>
			</div>

		</div>
	</div>
	<script type="text/javascript">(function (w,d) {var loader = function () {var s = d.createElement("script"), tag = d.getElementsByTagName("script")[0]; s.src="https://cdn.iubenda.com/iubenda.js"; tag.parentNode.insertBefore(s,tag);}; if(w.addEventListener){w.addEventListener("load", loader, false);}else if(w.attachEvent){w.attachEvent("onload", loader);}else{w.onload = loader;}})(window, document);</script>
</footer>
