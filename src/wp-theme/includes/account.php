<?php

/**
 * ACCOUNT — sconto di benvenuto alla registrazione (senza provider newsletter) + consenso marketing.
 *  1) Il codice CR_WELCOME_COUPON compare nell'email WooCommerce "Nuovo account" (solo quella).
 *  2) Checkbox facoltativa «Voglio ricevere novità» sia nel form di registrazione My Account
 *     sia nel blocco "crea un account" del checkout → user meta `cr_marketing_consent` (yes/no):
 *     lista marketing consensata, esportabile in futuro.
 * ⚠️ Il coupon va CREATO in WooCommerce (vedi docs/go-live.md): qui il tema lo mostra soltanto.
 */

/* 1 · Blocco "sconto di benvenuto" in coda all'email del nuovo account (priorità < 10 del footer). */
add_action('woocommerce_email_footer', 'cr_welcome_coupon_email', 5);
function cr_welcome_coupon_email($email)
{
	if (!$email || $email->id !== 'customer_new_account' || !defined('CR_WELCOME_COUPON') || !CR_WELCOME_COUPON) {
		return;
	}
	$code = CR_WELCOME_COUPON;
	$pct  = defined('CR_WELCOME_PCT') ? CR_WELCOME_PCT : '5%';
	?>
	<table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin:24px 0;">
		<tr><td style="background:#f3f0fa;border:1px solid #b6a9d9;border-radius:12px;padding:24px;text-align:center;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;">
			<p style="margin:0 0 6px;font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:#8877b2;"><?php esc_html_e('Il tuo benvenuto', 'cardsrift'); ?></p>
			<p style="margin:0 0 12px;font-size:16px;color:#1d2125;"><?php printf(esc_html__('Il primo ordine è %s. Usa il codice:', 'cardsrift'), '−' . esc_html($pct)); ?></p>
			<p style="margin:0;font-size:26px;font-weight:bold;letter-spacing:.06em;color:#1d2125;"><?= esc_html($code); ?></p>
			<p style="margin:10px 0 0;font-size:13px;color:#6b6f76;"><?php esc_html_e('Inseriscilo al checkout. Valido una volta.', 'cardsrift'); ?></p>
		</td></tr>
	</table>
	<?php
}

/* 2 · Consenso marketing facoltativo: form di registrazione My Account + "crea un account" al checkout. */
add_action('woocommerce_register_form', 'cr_marketing_consent_field');
add_action('woocommerce_after_checkout_registration_form', 'cr_marketing_consent_field');
function cr_marketing_consent_field()
{
	?>
	<p class="form-row form-row-wide">
		<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
			<input class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" type="checkbox" name="cr_marketing_consent" id="cr_marketing_consent" value="1" <?php checked(isset($_POST['cr_marketing_consent'])); // phpcs:ignore ?> />
			<span><?php esc_html_e('Voglio ricevere novità e offerte da CardsRift (facoltativo).', 'cardsrift'); ?></span>
		</label>
	</p>
	<?php
}

/* Salva il consenso alla creazione dell'account (fires anche dal checkout: senza checkbox = no). */
add_action('woocommerce_created_customer', 'cr_save_marketing_consent');
function cr_save_marketing_consent($customer_id)
{
	update_user_meta($customer_id, 'cr_marketing_consent', isset($_POST['cr_marketing_consent']) ? 'yes' : 'no'); // phpcs:ignore
}
