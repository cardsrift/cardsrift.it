<?php

/**
 * RIEPILOGO ORDINE (checkout) — override CardsRift.
 *
 * Righe compatte con la miniatura e i chip CONDIZIONE · LINGUA · FOIL: chi compra
 * una singola deve poter ricontrollare la copia esatta anche all'ultimo passo,
 * quando ormai sta per pagare.
 *
 * ⚠️ La classe `woocommerce-checkout-review-order-table` sul contenitore è
 * obbligatoria: è il selettore che WooCommerce sostituisce a ogni ricalcolo AJAX
 * (cambio indirizzo, spedizione, coupon). Senza, i totali restano fermi.
 *
 * @see woocommerce/templates/checkout/review-order.php
 * @version 9.8.0
 */

defined('ABSPATH') || exit;
?>

<div class="woocommerce-checkout-review-order-table">

	<div class="flex flex-col">
		<?php
		do_action('woocommerce_review_order_before_cart_contents');

		foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
			$_product = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);

			if (!$_product || !$_product->exists() || $cart_item['quantity'] <= 0 || !apply_filters('woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key)) {
				continue;
			}
			$qty = (int) $cart_item['quantity'];
		?>
			<div class="cr-line !py-3 <?= esc_attr(apply_filters('woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key)); ?>">
				<?php // ⚠️ niente bollino sulla miniatura: .cr-thumb ha overflow:hidden e lo taglierebbe.
				// La quantità sta nella riga, come nella conferma d'ordine e nello storico. ?>
				<span class="cr-thumb !w-11">
					<?= $_product->get_image('woocommerce_thumbnail'); // phpcs:ignore ?>
				</span>

				<div class="flex-1 min-w-0">
					<span class="block font-metropolis font-semibold text-xs leading-snug text-th-ink">
						<?= wp_kses_post(apply_filters('woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key)); ?>
					</span>
					<?php cr_item_chips_html($_product); ?>
					<?= wc_get_formatted_cart_item_data($cart_item); // phpcs:ignore ?>

					<span class="block text-xs mt-1 tabular-nums <?= $qty > 1 ? 'text-th-acc font-metropolis font-semibold' : 'text-th-soft'; ?>">
						<?= apply_filters('woocommerce_checkout_cart_item_quantity', '<span class="product-quantity">' . esc_html(sprintf(__('Quantità: %d', 'cardsrift'), $qty)) . '</span>', $cart_item, $cart_item_key); // phpcs:ignore ?>
					</span>
				</div>

				<span class="cr-price !text-sm whitespace-nowrap self-center">
					<?= apply_filters('woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal($_product, $qty), $cart_item, $cart_item_key); // phpcs:ignore ?>
				</span>
			</div>
		<?php
		}

		do_action('woocommerce_review_order_after_cart_contents');
		?>
	</div>

	<div class="mt-3 pt-1 border-t border-th-line">
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
			<?php do_action('woocommerce_review_order_before_shipping'); ?>
			<?php wc_cart_totals_shipping_html(); ?>
			<?php do_action('woocommerce_review_order_after_shipping'); ?>
		<?php endif; ?>

		<?php foreach (WC()->cart->get_fees() as $fee) : ?>
			<div class="cr-sumrow">
				<span><?= esc_html($fee->name); ?></span>
				<span><?php wc_cart_totals_fee_html($fee); ?></span>
			</div>
		<?php endforeach; ?>

		<?php if (wc_tax_enabled() && !WC()->cart->display_prices_including_tax()) : ?>
			<?php if ('itemized' === get_option('woocommerce_tax_total_display')) : ?>
				<?php foreach (WC()->cart->get_tax_totals() as $code => $tax) : // phpcs:ignore ?>
					<div class="cr-sumrow tax-rate-<?= esc_attr(sanitize_title($code)); ?>">
						<span><?= esc_html($tax->label); ?></span>
						<span><?= wp_kses_post($tax->formatted_amount); ?></span>
					</div>
				<?php endforeach; ?>
			<?php else : ?>
				<div class="cr-sumrow">
					<span><?= esc_html(WC()->countries->tax_or_vat()); ?></span>
					<span><?php wc_cart_totals_taxes_total_html(); ?></span>
				</div>
			<?php endif; ?>
		<?php endif; ?>

		<?php do_action('woocommerce_review_order_before_order_total'); ?>
		<div class="cr-sumrow cr-sumrow--total order-total">
			<span><?php esc_html_e('Totale', 'cardsrift'); ?></span>
			<span><?php wc_cart_totals_order_total_html(); ?></span>
		</div>
		<?php do_action('woocommerce_review_order_after_order_total'); ?>
	</div>

	<a class="inline-flex items-center gap-1.5 mt-4 font-metropolis font-semibold text-xs text-th-muted no-underline hover:text-th-acc transition-colors" href="<?= esc_url(wc_get_cart_url()); ?>">
		<svg class="w-3.5 h-3.5 stroke-current fill-transparent" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M11 18l-6-6 6-6" /></svg>
		<?php esc_html_e('Modifica il carrello', 'cardsrift'); ?>
	</a>
</div>
