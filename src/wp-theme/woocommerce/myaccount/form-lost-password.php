<?php

/**
 * PASSWORD DIMENTICATA — override CardsRift.
 *
 * @see woocommerce/templates/myaccount/form-lost-password.php
 * @version 9.8.0
 */

defined('ABSPATH') || exit;

do_action('woocommerce_before_lost_password_form');
?>

<form method="post" class="woocommerce-ResetPassword lost_reset_password cr-form cr-panel p-5 lg:p-7 max-w-[520px]">
	<p class="text-sm text-th-muted leading-relaxed mb-5">
		<?= wp_kses_post(apply_filters('woocommerce_lost_password_message', esc_html__('Scrivi l’email con cui ti sei registrato: ti mandiamo un link per impostare una nuova password.', 'cardsrift'))); ?>
	</p>

	<p class="woocommerce-form-row form-row">
		<label for="user_login"><?php esc_html_e('Email o nome utente', 'cardsrift'); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e('obbligatorio', 'cardsrift'); ?></span></label>
		<input class="woocommerce-Input input-text" type="text" name="user_login" id="user_login" autocomplete="username" required aria-required="true" />
	</p>

	<?php do_action('woocommerce_lostpassword_form'); ?>

	<div class="flex flex-wrap items-center gap-4 mt-5">
		<input type="hidden" name="wc_reset_password" value="true" />
		<button type="submit" class="cr-btn cr-btn-solid woocommerce-Button" value="<?php esc_attr_e('Mandami il link', 'cardsrift'); ?>"><?php esc_html_e('Mandami il link', 'cardsrift'); ?></button>
		<a class="font-metropolis font-semibold text-sm text-th-muted no-underline hover:text-th-acc transition-colors" href="<?= esc_url(wc_get_page_permalink('myaccount')); ?>"><?php esc_html_e('Torna all’accesso', 'cardsrift'); ?></a>
	</div>

	<?php wp_nonce_field('lost_password', 'woocommerce-lost-password-nonce'); ?>
</form>

<?php do_action('woocommerce_after_lost_password_form'); ?>
