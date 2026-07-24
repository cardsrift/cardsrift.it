<?php

/**
 * NUOVA PASSWORD — override CardsRift.
 * Ci si arriva dal link ricevuto per email.
 *
 * @see woocommerce/templates/myaccount/form-reset-password.php
 * @version 9.8.0
 */

defined('ABSPATH') || exit;

do_action('woocommerce_before_reset_password_form');
?>

<form method="post" class="woocommerce-ResetPassword lost_reset_password cr-form cr-panel p-5 lg:p-7 max-w-[520px]">
	<p class="text-sm text-th-muted leading-relaxed mb-5">
		<?= wp_kses_post(apply_filters('woocommerce_reset_password_message', esc_html__('Scegli la nuova password: da adesso userai questa per accedere.', 'cardsrift'))); ?>
	</p>

	<p class="woocommerce-form-row form-row">
		<label for="password_1"><?php esc_html_e('Nuova password', 'cardsrift'); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e('obbligatorio', 'cardsrift'); ?></span></label>
		<input type="password" class="woocommerce-Input input-text" name="password_1" id="password_1" autocomplete="new-password" required aria-required="true" />
	</p>

	<p class="woocommerce-form-row form-row">
		<label for="password_2"><?php esc_html_e('Ripeti la nuova password', 'cardsrift'); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e('obbligatorio', 'cardsrift'); ?></span></label>
		<input type="password" class="woocommerce-Input input-text" name="password_2" id="password_2" autocomplete="new-password" required aria-required="true" />
	</p>

	<input type="hidden" name="reset_key" value="<?= esc_attr($args['key']); ?>" />
	<input type="hidden" name="reset_login" value="<?= esc_attr($args['login']); ?>" />

	<?php do_action('woocommerce_resetpassword_form'); ?>

	<p class="woocommerce-form-row form-row !mt-5">
		<input type="hidden" name="wc_reset_password" value="true" />
		<button type="submit" class="cr-btn cr-btn-solid woocommerce-Button" value="<?php esc_attr_e('Salva la password', 'cardsrift'); ?>"><?php esc_html_e('Salva la password', 'cardsrift'); ?></button>
	</p>

	<?php wp_nonce_field('reset_password', 'woocommerce-reset-password-nonce'); ?>
</form>

<?php do_action('woocommerce_after_reset_password_form'); ?>
