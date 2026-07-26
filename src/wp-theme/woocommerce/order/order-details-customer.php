<?php

/**
 * INDIRIZZI DELL'ORDINE — override CardsRift.
 * Due schede affiancate (fatturazione e spedizione) con la stessa forma delle
 * schede indirizzo dell'account: dove arriva il pacco è un'informazione da
 * ricontrollare a colpo d'occhio, non da leggere in un paragrafo.
 *
 * @see woocommerce/templates/order/order-details-customer.php
 * @version 9.8.0
 */

defined('ABSPATH') || exit;

$show_shipping = !wc_ship_to_billing_address_only() && $order->needs_shipping_address();
?>

<section class="woocommerce-customer-details mt-8 lg:mt-10">
	<h2 class="font-metropolis font-semibold !text-lg mb-4"><?php esc_html_e('Dove lo mandiamo', 'cardsrift'); ?></h2>

	<div class="grid grid-cols-1 tb:grid-cols-2 gap-4">

		<div class="cr-panel p-5 min-w-0 woocommerce-column woocommerce-column--billing-address">
			<h3 class="cr-label"><?php esc_html_e('Indirizzo di fatturazione', 'cardsrift'); ?></h3>
			<address class="cr-address">
				<?= wp_kses_post($order->get_formatted_billing_address(esc_html__('Non indicato', 'cardsrift'))); ?>
				<?php if ($order->get_billing_phone()) : ?>
					<span class="block mt-2 woocommerce-customer-details--phone"><?= esc_html($order->get_billing_phone()); ?></span>
				<?php endif; ?>
				<?php if ($order->get_billing_email()) : ?>
					<span class="block woocommerce-customer-details--email"><?= esc_html($order->get_billing_email()); ?></span>
				<?php endif; ?>
				<?php do_action('woocommerce_order_details_after_customer_address', 'billing', $order); ?>
			</address>
		</div>

		<?php if ($show_shipping) : ?>
			<div class="cr-panel p-5 min-w-0 woocommerce-column woocommerce-column--shipping-address">
				<h3 class="cr-label"><?php esc_html_e('Indirizzo di spedizione', 'cardsrift'); ?></h3>
				<address class="cr-address">
					<?= wp_kses_post($order->get_formatted_shipping_address(esc_html__('Non indicato', 'cardsrift'))); ?>
					<?php if ($order->get_shipping_phone()) : ?>
						<span class="block mt-2 woocommerce-customer-details--phone"><?= esc_html($order->get_shipping_phone()); ?></span>
					<?php endif; ?>
					<?php do_action('woocommerce_order_details_after_customer_address', 'shipping', $order); ?>
				</address>
			</div>
		<?php endif; ?>
	</div>

	<?php do_action('woocommerce_order_details_after_customer_details', $order); ?>
</section>
