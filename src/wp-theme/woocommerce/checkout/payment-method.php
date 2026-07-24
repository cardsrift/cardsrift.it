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
?>
<li class="wc_payment_method payment_method_<?= esc_attr($gateway->id); ?>">
	<input id="payment_method_<?= esc_attr($gateway->id); ?>" type="radio" class="input-radio" name="payment_method" value="<?= esc_attr($gateway->id); ?>" <?php checked($gateway->chosen, true); ?> data-order_button_text="<?= esc_attr($gateway->order_button_text); ?>" />

	<label for="payment_method_<?= esc_attr($gateway->id); ?>">
		<?= $gateway->get_title(); // phpcs:ignore ?>
		<?= $gateway->get_icon(); // phpcs:ignore ?>
	</label>

	<?php if ($gateway->has_fields() || $gateway->get_description()) : ?>
		<div class="payment_box payment_method_<?= esc_attr($gateway->id); ?>" <?php if (!$gateway->chosen) : ?>style="display:none;"<?php endif; ?>>
			<?php $gateway->payment_fields(); ?>
		</div>
	<?php endif; ?>
</li>
