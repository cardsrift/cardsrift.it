<?php

/**
 * RIEPILOGO CARRELLO — override CardsRift.
 *
 * Il pannello sticky della colonna destra. Mostra i costi per intero PRIMA del
 * checkout (spedizione compresa): i costi che compaiono a sorpresa sono la prima
 * causa di abbandono, quindi qui non si nasconde niente.
 * In fondo, su mobile, la barra fissa con totale e CTA.
 *
 * ⚠️ La classe `cart_totals` sul contenitore è obbligatoria: cart.js sostituisce
 * proprio quell'elemento a ogni aggiornamento AJAX.
 *
 * @see woocommerce/templates/cart/cart-totals.php
 * @version 9.8.0
 */

defined('ABSPATH') || exit;
?>

<div class="cart_totals <?= WC()->customer->has_calculated_shipping() ? 'calculated_shipping' : ''; ?>">
	<?php do_action('woocommerce_before_cart_totals'); ?>

	<div class="cr-panel p-5 lg:p-6">

		<h2 class="font-metropolis font-semibold !text-lg mb-1"><?php esc_html_e('Riepilogo', 'cardsrift'); ?></h2>

		<?php cr_free_shipping_bar(); ?>

		<div class="mt-3">
			<div class="cr-sumrow">
				<span><?php esc_html_e('Subtotale', 'cardsrift'); ?></span>
				<span><?php wc_cart_totals_subtotal_html(); ?></span>
			</div>

			<?php foreach (WC()->cart->get_coupons() as $code => $coupon) : ?>
				<div class="cr-sumrow coupon-<?= esc_attr(sanitize_title($code)); ?>">
					<span class="text-th-acc"><?php wc_cart_totals_coupon_label($coupon); ?></span>
					<span class="!text-th-acc"><?php wc_cart_totals_coupon_html($coupon); ?></span>
				</div>
			<?php endforeach; ?>

			<?php if (WC()->cart->needs_shipping() && WC()->cart->show_shipping()) : ?>
				<?php do_action('woocommerce_cart_totals_before_shipping'); ?>
				<?php wc_cart_totals_shipping_html(); ?>
				<?php do_action('woocommerce_cart_totals_after_shipping'); ?>

			<?php elseif (WC()->cart->needs_shipping() && 'yes' === get_option('woocommerce_enable_shipping_calc')) : ?>
				<div class="cr-sumrow items-start flex-col !gap-2">
					<span><?php esc_html_e('Spedizione', 'cardsrift'); ?></span>
					<span class="w-full"><?php woocommerce_shipping_calculator(); ?></span>
				</div>
			<?php endif; ?>

			<?php foreach (WC()->cart->get_fees() as $fee) : ?>
				<div class="cr-sumrow">
					<span><?= esc_html($fee->name); ?></span>
					<span><?php wc_cart_totals_fee_html($fee); ?></span>
				</div>
			<?php endforeach; ?>

			<?php
			if (wc_tax_enabled() && !WC()->cart->display_prices_including_tax()) {
				$taxable_address = WC()->customer->get_taxable_address();
				$estimated_text  = '';
				if (WC()->customer->is_customer_outside_base() && !WC()->customer->has_calculated_shipping()) {
					$estimated_text = sprintf(' <small>' . esc_html__('(stimata per %s)', 'cardsrift') . '</small>', WC()->countries->estimated_for_prefix($taxable_address[0]) . WC()->countries->countries[$taxable_address[0]]);
				}
				if ('itemized' === get_option('woocommerce_tax_total_display')) {
					foreach (WC()->cart->get_tax_totals() as $code => $tax) { // phpcs:ignore
						?>
						<div class="cr-sumrow tax-rate-<?= esc_attr(sanitize_title($code)); ?>">
							<span><?= esc_html($tax->label) . $estimated_text; // phpcs:ignore ?></span>
							<span><?= wp_kses_post($tax->formatted_amount); ?></span>
						</div>
						<?php
					}
				} else {
					?>
					<div class="cr-sumrow">
						<span><?= esc_html(WC()->countries->tax_or_vat()) . $estimated_text; // phpcs:ignore ?></span>
						<span><?php wc_cart_totals_taxes_total_html(); ?></span>
					</div>
					<?php
				}
			}
			?>

			<?php do_action('woocommerce_cart_totals_before_order_total'); ?>
			<div class="cr-sumrow cr-sumrow--total order-total">
				<span><?php esc_html_e('Totale', 'cardsrift'); ?></span>
				<span><?php wc_cart_totals_order_total_html(); ?></span>
			</div>
			<?php do_action('woocommerce_cart_totals_after_order_total'); ?>
		</div>

		<div class="wc-proceed-to-checkout mt-5">
			<?php do_action('woocommerce_proceed_to_checkout'); ?>
		</div>

		<ul class="flex flex-col gap-2 mt-5 pt-5 border-t border-th-line text-xs text-th-muted">
			<li class="flex items-center gap-2.5">
				<svg class="w-4 h-4 stroke-th-acc fill-transparent shrink-0" viewBox="0 0 24 24" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7 3v6c0 4.5-3 7.7-7 9-4-1.3-7-4.5-7-9V6l7-3z" /></svg>
				<?php esc_html_e('Pagamento sicuro, dati cifrati', 'cardsrift'); ?>
			</li>
			<li class="flex items-center gap-2.5">
				<svg class="w-4 h-4 stroke-th-acc fill-transparent shrink-0" viewBox="0 0 24 24" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5" /></svg>
				<?php esc_html_e('Spedizione tracciata in 24/48h', 'cardsrift'); ?>
			</li>
		</ul>
	</div>

	<?php // mobile: totale e CTA sempre raggiungibili, senza risalire la pagina ?>
	<div class="cr-stickybar">
		<div class="min-w-0">
			<span class="block text-xs text-th-muted leading-none mb-1"><?php esc_html_e('Totale', 'cardsrift'); ?></span>
			<span class="cr-price block leading-none"><?php wc_cart_totals_order_total_html(); ?></span>
		</div>
		<a class="cr-btn cr-btn-solid flex-1 justify-center" href="<?= esc_url(wc_get_checkout_url()); ?>"><?php esc_html_e('Vai al checkout', 'cardsrift'); ?></a>
	</div>

	<?php do_action('woocommerce_after_cart_totals'); ?>
</div>
