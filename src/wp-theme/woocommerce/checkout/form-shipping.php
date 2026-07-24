<?php

/**
 * INDIRIZZO DI SPEDIZIONE ALTERNATIVO + NOTE — override CardsRift.
 *
 * Il caso normale è "spedisci dove fatturi": i campi di spedizione restano
 * chiusi dietro una spunta, e si aprono solo a chi ne ha davvero bisogno.
 * Le note sull'ordine sono un <details>: chi non ha niente da dire non le vede.
 *
 * ⚠️ `#ship-to-different-address-checkbox` e `.shipping_address` servono a
 * checkout.js per mostrare/nascondere il blocco e ricalcolare la spedizione.
 *
 * @see woocommerce/templates/checkout/form-shipping.php
 * @version 9.8.0
 */

defined('ABSPATH') || exit;
?>

<div class="woocommerce-shipping-fields">
	<?php if (true === WC()->cart->needs_shipping_address()) : ?>

		<div id="ship-to-different-address" class="cr-panel p-5">
			<label class="cr-check woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
				<input id="ship-to-different-address-checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" <?php checked(apply_filters('woocommerce_ship_to_different_address_checked', 'shipping' === get_option('woocommerce_ship_to_destination') ? 1 : 0), 1); ?> type="checkbox" name="ship_to_different_address" value="1" />
				<span class="font-metropolis font-semibold text-th-ink"><?php esc_html_e('Spedisci a un indirizzo diverso', 'cardsrift'); ?></span>
			</label>

			<div class="shipping_address mt-5">
				<?php do_action('woocommerce_before_checkout_shipping_form', $checkout); ?>

				<div class="woocommerce-shipping-fields__field-wrapper cr-form__grid">
					<?php foreach ($checkout->get_checkout_fields('shipping') as $key => $field) : ?>
						<?php woocommerce_form_field($key, $field, $checkout->get_value($key)); ?>
					<?php endforeach; ?>
				</div>

				<?php do_action('woocommerce_after_checkout_shipping_form', $checkout); ?>
			</div>
		</div>

	<?php endif; ?>
</div>

<div class="woocommerce-additional-fields">
	<?php do_action('woocommerce_before_order_notes', $checkout); ?>

	<?php if (apply_filters('woocommerce_enable_order_notes_field', 'yes' === get_option('woocommerce_enable_order_comments', 'yes'))) : ?>
		<details class="cr-panel overflow-hidden mt-5 group">
			<summary class="flex items-center gap-2.5 cursor-pointer list-none p-5 font-metropolis font-semibold text-sm text-th-ink">
				<svg class="w-[18px] h-[18px] stroke-th-acc fill-transparent shrink-0" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5h16M4 11h16M4 17h9" /></svg>
				<?php esc_html_e('Vuoi lasciarci una nota?', 'cardsrift'); ?>
				<svg class="w-4 h-4 ml-auto stroke-th-muted fill-transparent transition-transform group-open:rotate-180" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6" /></svg>
			</summary>
			<div class="woocommerce-additional-fields__field-wrapper px-5 pb-5">
				<?php foreach ($checkout->get_checkout_fields('order') as $key => $field) : ?>
					<?php woocommerce_form_field($key, $field, $checkout->get_value($key)); ?>
				<?php endforeach; ?>
			</div>
		</details>
	<?php endif; ?>

	<?php do_action('woocommerce_after_order_notes', $checkout); ?>
</div>
