<?php

/**
 * DETTAGLIO ORDINE — override CardsRift.
 * Usato sia dalla pagina di conferma sia dal dettaglio ordine nell'account:
 * un pannello con le righe acquistate e, sotto, i totali. Niente tabella:
 * le stesse righe del carrello, così il cliente riconosce quello che ha visto.
 *
 * @see woocommerce/templates/order/order-details.php
 * @version 9.8.0
 */

defined('ABSPATH') || exit;

$order = wc_get_order($order_id); // phpcs:ignore
if (!$order) {
	return;
}

$order_items        = $order->get_items(apply_filters('woocommerce_purchase_order_item_types', 'line_item'));
$show_purchase_note = $order->has_status(apply_filters('woocommerce_purchase_note_order_statuses', ['completed', 'processing']));
$downloads          = $order->get_downloadable_items();
$actions            = array_filter(
	wc_get_account_orders_actions($order),
	function ($key) {
		return 'view' !== $key;
	},
	ARRAY_FILTER_USE_KEY
);
// l'ordine appartiene a chi lo sta guardando (vale anche per gli ospiti: user_id 0)
$show_customer_details = $order->get_user_id() === get_current_user_id();

if ($show_downloads) {
	wc_get_template('order/order-downloads.php', ['downloads' => $downloads, 'show_title' => true]);
}
?>

<section class="woocommerce-order-details">
	<?php do_action('woocommerce_order_details_before_order_table', $order); ?>

	<h2 class="woocommerce-order-details__title font-metropolis font-semibold !text-lg mb-4"><?php esc_html_e('Cosa hai ordinato', 'cardsrift'); ?></h2>

	<div class="cr-panel px-4 tb:px-6 pb-1">
		<?php
		do_action('woocommerce_order_details_before_order_table_items', $order);

		foreach ($order_items as $item_id => $item) {
			$product = $item->get_product();
			wc_get_template('order/order-details-item.php', [
				'order'              => $order,
				'item_id'            => $item_id,
				'item'               => $item,
				'show_purchase_note' => $show_purchase_note,
				'purchase_note'      => $product ? $product->get_purchase_note() : '',
				'product'            => $product,
			]);
		}

		do_action('woocommerce_order_details_after_order_table_items', $order);
		?>

		<div class="pt-3 pb-5 border-t border-th-line">
			<?php foreach ($order->get_order_item_totals() as $key => $total) : ?>
				<?php // WooCommerce mette i due punti nell'etichetta: qui l'allineamento a due
				// colonne li rende inutili, e stonerebbero col riepilogo del carrello ?>
				<div class="cr-sumrow<?= $key === 'order_total' ? ' cr-sumrow--total' : ''; ?>">
					<span><?= esc_html(rtrim($total['label'], ': ')); ?></span>
					<span><?= wp_kses_post($total['value']); ?></span>
				</div>
			<?php endforeach; ?>

			<?php if ($order->get_customer_note()) : ?>
				<div class="mt-4 pt-4 border-t border-th-line">
					<span class="cr-label"><?php esc_html_e('La tua nota', 'cardsrift'); ?></span>
					<p class="text-sm text-th-muted leading-relaxed m-0"><?= wp_kses(nl2br(wptexturize($order->get_customer_note())), ['br' => []]); ?></p>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<?php if (!empty($actions)) : ?>
		<div class="flex flex-wrap gap-2.5 mt-4">
			<?php foreach ($actions as $key => $action) : // phpcs:ignore
				$action_aria_label = empty($action['aria-label'])
					? sprintf(__('%1$s — ordine numero %2$s', 'cardsrift'), $action['name'], $order->get_order_number())
					: $action['aria-label'];
				printf(
					'<a href="%s" class="cr-btn cr-btn-ghost woocommerce-button button %s order-actions-button" aria-label="%s">%s</a>',
					esc_url($action['url']),
					esc_attr(sanitize_html_class($key)),
					esc_attr($action_aria_label),
					esc_html($action['name'])
				);
			endforeach; ?>
		</div>
	<?php endif; ?>

	<?php do_action('woocommerce_order_details_after_order_table', $order); ?>
</section>

<?php
do_action('woocommerce_after_order_details', $order);

if ($show_customer_details) {
	wc_get_template('order/order-details-customer.php', ['order' => $order]);
}
