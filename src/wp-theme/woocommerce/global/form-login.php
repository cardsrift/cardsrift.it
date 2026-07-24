<?php

/**
 * MODULO DI ACCESSO generico — override CardsRift.
 * Usato da woocommerce_login_form(): al checkout compare ripiegato sotto
 * "Hai già un account? Accedi".
 *
 * ⚠️ `form.login`, i nomi dei campi e il nonce `woocommerce-login` sono richiesti
 * dal gestore di WooCommerce e da checkout.js (che apre/chiude `form.login`).
 *
 * @see woocommerce/templates/global/form-login.php
 * @version 9.8.0
 */

defined('ABSPATH') || exit;

if (is_user_logged_in()) {
	return;
}
?>

<form class="woocommerce-form woocommerce-form-login login cr-form cr-panel p-5 lg:p-6 mb-6 max-w-[520px]" method="post" <?= $hidden ? 'style="display:none;"' : ''; ?>>
	<?php do_action('woocommerce_login_form_start'); ?>

	<?php if ($message) : ?>
		<p class="text-sm text-th-muted leading-relaxed mb-5"><?= wp_kses_post(wptexturize($message)); ?></p>
	<?php endif; ?>

	<p class="form-row">
		<label for="username"><?php esc_html_e('Email o nome utente', 'cardsrift'); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e('obbligatorio', 'cardsrift'); ?></span></label>
		<input type="text" class="input-text" name="username" id="username" autocomplete="username" required aria-required="true" />
	</p>

	<p class="form-row">
		<label for="password"><?php esc_html_e('Password', 'cardsrift'); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e('obbligatorio', 'cardsrift'); ?></span></label>
		<input class="input-text woocommerce-Input" type="password" name="password" id="password" autocomplete="current-password" required aria-required="true" />
	</p>

	<?php do_action('woocommerce_login_form'); ?>

	<p class="form-row">
		<label class="cr-check woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme">
			<input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox" id="rememberme" value="forever" />
			<span><?php esc_html_e('Resta collegato', 'cardsrift'); ?></span>
		</label>
		<?php wp_nonce_field('woocommerce-login', 'woocommerce-login-nonce'); ?>
		<input type="hidden" name="redirect" value="<?= esc_url($redirect); ?>" />
	</p>

	<div class="flex flex-wrap items-center gap-4 mt-5">
		<button type="submit" class="cr-btn cr-btn-solid woocommerce-button woocommerce-form-login__submit" name="login" value="<?php esc_attr_e('Accedi', 'cardsrift'); ?>"><?php esc_html_e('Accedi', 'cardsrift'); ?></button>
		<a class="lost_password font-metropolis font-semibold text-sm text-th-muted no-underline hover:text-th-acc transition-colors" href="<?= esc_url(wp_lostpassword_url()); ?>"><?php esc_html_e('Password dimenticata?', 'cardsrift'); ?></a>
	</div>

	<?php do_action('woocommerce_login_form_end'); ?>
</form>
