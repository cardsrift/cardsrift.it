<?php

/**
 * Singolo metodo di pagamento — override CardsRift.
 * La struttura resta quella di WooCommerce (input radio + label fratelli dentro
 * un <li>): il "vestito" da card selezionabile lo dà .cr-optlist, che legge lo
 * stato scelto con :has(input:checked).
 *
 * ⚠️ Da non toccare: id `payment_method_{id}`, `name="payment_method"`,
 * `data-order_button_text` e la classe `payment_box payment_method_{id}` —
 * checkout.js usa esattamente questi selettori.
 *
 * @see woocommerce/templates/checkout/payment-method.php
 * @version 9.8.0
 */

defined('ABSPATH') || exit;

// PayPal crea i suoi bottoni su questa azione (vedi cr_ppcp_buttons_hook() in
// includes/shop.php): eseguendola qui nascono dentro il riquadro del metodo.
// Il box va aperto anche se il gateway non ha né campi né descrizione, o i
// bottoni non avrebbero dove comparire.
$cr_extra = 'ppcp-gateway' === $gateway->id ? 'cr_ppcp_checkout_buttons' : '';
?>
<li class="wc_payment_method payment_method_<?= esc_attr($gateway->id); ?>">
	<input id="payment_method_<?= esc_attr($gateway->id); ?>" type="radio" class="input-radio" name="payment_method" value="<?= esc_attr($gateway->id); ?>" <?php checked($gateway->chosen, true); ?> data-order_button_text="<?= esc_attr($gateway->order_button_text); ?>" />

	<label for="payment_method_<?= esc_attr($gateway->id); ?>">
		<?= $gateway->get_title(); // phpcs:ignore ?>
		<?= $gateway->get_icon(); // phpcs:ignore ?>
	</label>

	<?php if ($gateway->has_fields() || $gateway->get_description() || $cr_extra) : ?>
		<div class="payment_box payment_method_<?= esc_attr($gateway->id); ?>" <?php if (!$gateway->chosen) : ?>style="display:none;"<?php endif; ?>>
			<?php $gateway->payment_fields(); ?>
			<?php if ($cr_extra) {
				do_action($cr_extra);
			} ?>
		</div>
	<?php endif; ?>
</li>
