<?php

/**
 * CONDIZIONI DI VENDITA — override CardsRift.
 * ⚠️ `input#terms` e `input[name="terms-field"]` sono richiesti dalla validazione
 * di WooCommerce: la spunta è obbligatoria solo se la pagina Termini è impostata
 * in WooCommerce → Avanzate.
 *
 * @see woocommerce/templates/checkout/terms.php
 * @version 9.8.0
 */

defined('ABSPATH') || exit;

if (!apply_filters('woocommerce_checkout_show_terms', true) || !function_exists('wc_terms_and_conditions_checkbox_enabled')) {
	return;
}

do_action('woocommerce_checkout_before_terms_and_conditions');
?>

<div class="woocommerce-terms-and-conditions-wrapper">
	<?php do_action('woocommerce_checkout_terms_and_conditions'); ?>

	<?php if (wc_terms_and_conditions_checkbox_enabled()) : ?>
		<p class="form-row validate-required !mt-5">
			<label class="cr-check woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
				<input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" name="terms" <?php checked(apply_filters('woocommerce_terms_is_checked_default', isset($_POST['terms'])), true); // phpcs:ignore ?> id="terms" />
				<span class="woocommerce-terms-and-conditions-checkbox-text"><?php wc_terms_and_conditions_checkbox_text(); ?><abbr class="required" title="<?php esc_attr_e('obbligatorio', 'cardsrift'); ?>">&nbsp;*</abbr></span>
			</label>
			<input type="hidden" name="terms-field" value="1" />
		</p>
	<?php endif; ?>
</div>

<?php do_action('woocommerce_checkout_after_terms_and_conditions'); ?>
