<?php

/**
 * CALCOLATORE SPEDIZIONE — override CardsRift.
 * Sta dentro il riepilogo: serve solo a rispondere a "quanto mi costa arrivare a
 * casa mia", quindi resta chiuso finché non lo si chiede. Se il negozio spedisce
 * in un solo Paese la tendina Nazione sparisce (è una domanda senza alternative).
 *
 * ⚠️ `shipping-calculator-button` e `shipping-calculator-form` servono a cart.js
 * per aprire e chiudere il blocco.
 *
 * @see woocommerce/templates/cart/shipping-calculator.php
 * @version 9.8.0
 */

defined('ABSPATH') || exit;

do_action('woocommerce_before_shipping_calculator');

$cr_countries  = WC()->countries->get_shipping_countries();
$cr_one_market = is_array($cr_countries) && count($cr_countries) === 1;
?>

<form class="woocommerce-shipping-calculator cr-form" action="<?= esc_url(wc_get_cart_url()); ?>" method="post">

	<?php printf(
		'<a href="#" class="shipping-calculator-button inline-flex items-center gap-1.5 font-metropolis font-semibold text-xs text-th-acc no-underline hover:underline" aria-expanded="false" aria-controls="shipping-calculator-form" role="button">%s</a>',
		esc_html(!empty($button_text) ? $button_text : __('Calcola la spedizione', 'cardsrift'))
	); ?>

	<section class="shipping-calculator-form mt-3" id="shipping-calculator-form" style="display:none;">
		<div class="cr-form__grid !gap-3">

			<?php if (apply_filters('woocommerce_shipping_calculator_enable_country', true)) : ?>
				<p class="form-row form-row-wide<?= $cr_one_market ? ' hidden' : ''; ?>" id="calc_shipping_country_field">
					<label for="calc_shipping_country"><?php esc_html_e('Nazione', 'cardsrift'); ?></label>
					<select name="calc_shipping_country" id="calc_shipping_country" class="country_to_state country_select" rel="calc_shipping_state">
						<?php if (!$cr_one_market) : ?>
							<option value="default"><?php esc_html_e('Scegli la nazione…', 'cardsrift'); ?></option>
						<?php endif; ?>
						<?php foreach ($cr_countries as $key => $value) : ?>
							<option value="<?= esc_attr($key); ?>" <?= selected(WC()->customer->get_shipping_country(), esc_attr($key), false); ?>><?= esc_html($value); ?></option>
						<?php endforeach; ?>
					</select>
				</p>
			<?php endif; ?>

			<?php if (apply_filters('woocommerce_shipping_calculator_enable_state', true)) : ?>
				<p class="form-row" id="calc_shipping_state_field">
					<?php
					$current_cc = WC()->customer->get_shipping_country();
					$current_r  = WC()->customer->get_shipping_state();
					$states     = WC()->countries->get_states($current_cc);

					if (is_array($states) && empty($states)) : ?>
						<input type="hidden" name="calc_shipping_state" id="calc_shipping_state" />
					<?php elseif (is_array($states)) : ?>
						<label for="calc_shipping_state"><?php esc_html_e('Provincia', 'cardsrift'); ?></label>
						<select name="calc_shipping_state" class="state_select" id="calc_shipping_state">
							<option value=""><?php esc_html_e('Scegli…', 'cardsrift'); ?></option>
							<?php foreach ($states as $ckey => $cvalue) : ?>
								<option value="<?= esc_attr($ckey); ?>" <?= selected($current_r, $ckey, false); ?>><?= esc_html($cvalue); ?></option>
							<?php endforeach; ?>
						</select>
					<?php else : ?>
						<label for="calc_shipping_state"><?php esc_html_e('Provincia', 'cardsrift'); ?></label>
						<input type="text" class="input-text" value="<?= esc_attr($current_r); ?>" name="calc_shipping_state" id="calc_shipping_state" />
					<?php endif; ?>
				</p>
			<?php endif; ?>

			<?php if (apply_filters('woocommerce_shipping_calculator_enable_postcode', true)) : ?>
				<p class="form-row" id="calc_shipping_postcode_field">
					<label for="calc_shipping_postcode"><?php esc_html_e('CAP', 'cardsrift'); ?></label>
					<input type="text" class="input-text" value="<?= esc_attr(WC()->customer->get_shipping_postcode()); ?>" name="calc_shipping_postcode" id="calc_shipping_postcode" inputmode="numeric" autocomplete="postal-code" />
				</p>
			<?php endif; ?>

			<?php if (apply_filters('woocommerce_shipping_calculator_enable_city', true)) : ?>
				<p class="form-row form-row-wide" id="calc_shipping_city_field">
					<label for="calc_shipping_city"><?php esc_html_e('Città', 'cardsrift'); ?></label>
					<input type="text" class="input-text" value="<?= esc_attr(WC()->customer->get_shipping_city()); ?>" name="calc_shipping_city" id="calc_shipping_city" autocomplete="address-level2" />
				</p>
			<?php endif; ?>
		</div>

		<button type="submit" name="calc_shipping" value="1" class="cr-btn cr-btn-ghost w-full justify-center !py-2.5 !text-xs mt-3"><?php esc_html_e('Aggiorna la spedizione', 'cardsrift'); ?></button>
		<?php wp_nonce_field('woocommerce-shipping-calculator', 'woocommerce-shipping-calculator-nonce'); ?>
	</section>
</form>

<?php do_action('woocommerce_after_shipping_calculator'); ?>
