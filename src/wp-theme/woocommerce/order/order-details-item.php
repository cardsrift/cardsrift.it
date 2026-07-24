<?php

/**
 * Riga di un ordine — override CardsRift.
 * Stessa riga del carrello (miniatura + chip identificativi + importo), riusata
 * dalla pagina di conferma e dal dettaglio ordine nell'account.
 *
 * I chip CONDIZIONE · LINGUA · FOIL vengono dai meta salvati al momento
 * dell'acquisto: sono la verità storica di quale copia è stata venduta, e restano
 * corretti anche se il prodotto in catalogo cambia. Qui li mostriamo come chip e
 * li togliamo dall'elenco meta generico per non dire due volte la stessa cosa —
 * ma nelle email e in wp-admin l'elenco resta completo.
 *
 * @see woocommerce/templates/order/order-details-item.php
 * @version 9.8.0
 */

defined('ABSPATH') || exit;

if (!apply_filters('woocommerce_order_item_visible', true, $item)) {
	return;
}

$cr_visible   = $product && $product->is_visible();
$cr_permalink = apply_filters('woocommerce_order_item_permalink', $cr_visible ? $product->get_permalink($item) : '', $item, $order);
$cr_qty       = $item->get_quantity();
$cr_refunded  = $order->get_qty_refunded_for_item($item_id);
?>

<div class="cr-line <?= esc_attr(apply_filters('woocommerce_order_item_class', 'woocommerce-table__line-item order_item', $item, $order)); ?>">

	<span class="cr-thumb !w-12">
		<?= $product ? $product->get_image('woocommerce_thumbnail') : ''; // phpcs:ignore ?>
	</span>

	<div class="flex-1 min-w-0">
		<span class="block font-metropolis font-semibold text-sm leading-snug text-th-ink">
			<?= wp_kses_post(apply_filters('woocommerce_order_item_name', $cr_permalink ? sprintf('<a class="no-underline hover:text-th-acc transition-colors" href="%s">%s</a>', esc_url($cr_permalink), esc_html($item->get_name())) : esc_html($item->get_name()), $item, $cr_visible)); ?>
		</span>

		<?php cr_order_item_chips_html($item); ?>

		<?php
		do_action('woocommerce_order_item_meta_start', $item_id, $item, $order, false);
		add_filter('woocommerce_order_item_get_formatted_meta_data', 'cr_strip_card_meta', 20);
		wc_display_item_meta($item); // phpcs:ignore
		remove_filter('woocommerce_order_item_get_formatted_meta_data', 'cr_strip_card_meta', 20);
		do_action('woocommerce_order_item_meta_end', $item_id, $item, $order, false);
		?>

		<span class="block text-xs text-th-soft mt-1 tabular-nums">
			<?php
			$cr_qty_display = $cr_refunded
				? '<del>' . esc_html($cr_qty) . '</del> <ins class="no-underline">' . esc_html($cr_qty - ($cr_refunded * -1)) . '</ins>'
				: esc_html($cr_qty);
			echo apply_filters('woocommerce_order_item_quantity_html', '<span class="product-quantity">' . sprintf(esc_html__('Quantità: %s', 'cardsrift'), $cr_qty_display) . '</span>', $item); // phpcs:ignore
			?>
		</span>
	</div>

	<span class="cr-price !text-sm whitespace-nowrap self-center">
		<?= $order->get_formatted_line_subtotal($item); // phpcs:ignore ?>
	</span>
</div>

<?php if ($show_purchase_note && $purchase_note) : ?>
	<div class="woocommerce-table__product-purchase-note rounded-xl bg-th-accsoft p-4 my-3 text-sm leading-relaxed">
		<?= wpautop(do_shortcode(wp_kses_post($purchase_note))); // phpcs:ignore ?>
	</div>
<?php endif; ?>
