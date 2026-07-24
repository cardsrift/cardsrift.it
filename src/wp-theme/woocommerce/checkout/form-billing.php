<?php

/**
 * DATI CLIENTE (fatturazione) — override CardsRift.
 *
 * I campi di WooCommerce sono divisi in due sezioni con senso diverso:
 *  1. CONTATTI (email, telefono) — a cosa servono: conferma, tracking, corriere.
 *  2. INDIRIZZO — dove arriva il pacco quando non se ne indica un altro.
 * È lo stesso gruppo `billing` di sempre: nessun campo inventato, nessuna
 * validazione aggirata, solo un ordine di lettura umano.
 *
 * @see woocommerce/templates/checkout/form-billing.php
 * @version 9.8.0
 */

defined('ABSPATH') || exit;

$cr_fields  = $checkout->get_checkout_fields('billing');
$cr_contact = ['billing_email', 'billing_phone'];
?>

<div class="woocommerce-billing-fields">

	<!-- ---------- 1 · CONTATTI ---------- -->
	<section>
		<div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 mb-4">
			<h2 class="font-metropolis font-semibold !text-lg"><?php esc_html_e('I tuoi contatti', 'cardsrift'); ?></h2>
			<?php if (!is_user_logged_in() && 'no' !== get_option('woocommerce_enable_checkout_login_reminder')) : ?>
				<span class="text-sm text-th-muted">
					<?php esc_html_e('Hai già un account?', 'cardsrift'); ?>
					<a href="#" class="showlogin font-metropolis font-semibold text-th-acc no-underline hover:underline"><?php esc_html_e('Accedi', 'cardsrift'); ?></a>
				</span>
			<?php endif; ?>
		</div>

		<?php do_action('woocommerce_before_checkout_billing_form', $checkout); ?>

		<div class="cr-form__grid">
			<?php foreach ($cr_fields as $key => $field) : ?>
				<?php if (in_array($key, $cr_contact, true)) : ?>
					<?php woocommerce_form_field($key, $field, $checkout->get_value($key)); ?>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>
	</section>

	<!-- ---------- 2 · INDIRIZZO ---------- -->
	<section class="mt-8 lg:mt-10">
		<h2 class="font-metropolis font-semibold !text-lg"><?php esc_html_e('Indirizzo di fatturazione', 'cardsrift'); ?></h2>
		<p class="text-sm text-th-muted mt-1.5 mb-4"><?php esc_html_e('Se non ne indichi un altro qui sotto, spediamo a questo indirizzo.', 'cardsrift'); ?></p>

		<div class="woocommerce-billing-fields__field-wrapper cr-form__grid">
			<?php foreach ($cr_fields as $key => $field) : ?>
				<?php if (!in_array($key, $cr_contact, true)) : ?>
					<?php woocommerce_form_field($key, $field, $checkout->get_value($key)); ?>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>

		<?php do_action('woocommerce_after_checkout_billing_form', $checkout); ?>
	</section>
</div>

<?php if (!is_user_logged_in() && $checkout->is_registration_enabled()) : ?>
	<div class="woocommerce-account-fields cr-panel p-5 mt-8 lg:mt-10">

		<?php if (!$checkout->is_registration_required()) : ?>
			<p class="form-row create-account !mt-0">
				<label class="cr-check woocommerce-form__label woocommerce-form__label-for-checkbox checkbox !normal-case !tracking-normal !text-sm !mb-0">
					<input class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" id="createaccount" <?php checked((true === $checkout->get_value('createaccount') || (true === apply_filters('woocommerce_create_account_default_checked', false))), true); ?> type="checkbox" name="createaccount" value="1" />
					<span class="font-metropolis font-semibold text-th-ink"><?php esc_html_e('Crea un account CardsRift', 'cardsrift'); ?></span>
				</label>
			</p>
		<?php endif; ?>

		<?php do_action('woocommerce_before_checkout_registration_form', $checkout); ?>

		<?php if ($checkout->get_checkout_fields('account')) : ?>
			<div class="create-account cr-form__grid mt-4">
				<?php foreach ($checkout->get_checkout_fields('account') as $key => $field) : ?>
					<?php woocommerce_form_field($key, $field, $checkout->get_value($key)); ?>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php do_action('woocommerce_after_checkout_registration_form', $checkout); ?>
	</div>
<?php endif; ?>
