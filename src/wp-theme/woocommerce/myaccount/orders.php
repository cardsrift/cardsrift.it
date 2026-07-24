<?php

/**
 * ELENCO ORDINI — override CardsRift.
 * Da tabella a righe-card: su mobile una tabella a 5 colonne diventa illeggibile,
 * e le colonne di WooCommerce ("Totale: 51,19 € per 4 articoli") sono scritte per
 * stare in una cella, non per essere lette. Qui ogni ordine è un blocco con stato,
 * numero, data, miniature di cosa c'era dentro e totale.
 *
 * ⚠️ `woocommerce_my_account_my_orders_column_{id}` resta supportato: se
 * un'estensione aggiunge una colonna, la stampiamo in coda alla riga.
 *
 * @see woocommerce/templates/myaccount/orders.php
 * @version 9.8.0
 */

defined('ABSPATH') || exit;

do_action('woocommerce_before_account_orders', $has_orders);

$cr_extra_columns = array_diff_key(
	wc_get_account_orders_columns(),
	array_flip(['order-number', 'order-date', 'order-status', 'order-total', 'order-actions'])
);
?>

<?php if ($has_orders) : ?>

	<div class="woocommerce-orders-table woocommerce-MyAccount-orders account-orders-table flex flex-col gap-4">
		<?php foreach ($customer_orders->orders as $customer_order) :
			$order      = wc_get_order($customer_order); // phpcs:ignore
			$item_count = $order->get_item_count() - $order->get_item_count_refunded();
			$actions    = wc_get_account_orders_actions($order);
		?>
			<div class="cr-panel p-5 woocommerce-orders-table__row order woocommerce-orders-table__row--status-<?= esc_attr($order->get_status()); ?>">

				<div class="flex flex-wrap items-center gap-3">
					<?php cr_order_status_chip($order); ?>
					<a class="font-metropolis font-semibold text-sm text-th-ink no-underline hover:text-th-acc transition-colors tabular-nums" href="<?= esc_url($order->get_view_order_url()); ?>" aria-label="<?= esc_attr(sprintf(__('Vedi l’ordine numero %s', 'cardsrift'), $order->get_order_number())); ?>">
						#<?= esc_html($order->get_order_number()); ?>
					</a>
					<time class="text-xs text-th-soft" datetime="<?= esc_attr($order->get_date_created()->date('c')); ?>"><?= esc_html(wc_format_datetime($order->get_date_created())); ?></time>
					<span class="cr-price ml-auto"><?= wp_kses_post($order->get_formatted_order_total()); ?></span>
				</div>

				<div class="flex flex-wrap items-center gap-2 mt-4">
					<?php
					$cr_items = $order->get_items();
					foreach (array_slice($cr_items, 0, 4, true) as $cr_item) :
						$cr_product = $cr_item->get_product();
						if (!$cr_product) continue;
					?>
						<span class="cr-thumb !w-10" title="<?= esc_attr($cr_item->get_name()); ?>">
							<?= $cr_product->get_image('woocommerce_thumbnail'); // phpcs:ignore ?>
						</span>
					<?php endforeach; ?>

					<span class="text-xs text-th-muted ml-1">
						<?= esc_html(sprintf(_n('%d articolo', '%d articoli', $item_count, 'cardsrift'), $item_count)); ?>
					</span>

					<span class="flex flex-wrap gap-2 ml-auto">
						<?php
						foreach ($actions as $key => $action) { // phpcs:ignore
							$aria = empty($action['aria-label'])
								? sprintf(__('%1$s — ordine numero %2$s', 'cardsrift'), $action['name'], $order->get_order_number())
								: $action['aria-label'];
							printf(
								'<a href="%s" class="cr-btn %s !py-2.5 !text-xs woocommerce-button button %s" aria-label="%s">%s</a>',
								esc_url($action['url']),
								'view' === $key ? 'cr-btn-ghost' : 'cr-btn-solid',
								esc_attr(sanitize_html_class($key)),
								esc_attr($aria),
								esc_html($action['name'])
							);
						}
						?>
					</span>
				</div>

				<?php // colonne aggiunte da estensioni: nessuna nel tema, ma il contratto resta ?>
				<?php foreach ($cr_extra_columns as $column_id => $column_name) : ?>
					<?php if (has_action('woocommerce_my_account_my_orders_column_' . $column_id)) : ?>
						<div class="flex items-baseline gap-2 mt-3 text-sm text-th-muted">
							<span class="cr-label !mb-0"><?= esc_html($column_name); ?></span>
							<?php do_action('woocommerce_my_account_my_orders_column_' . $column_id, $order); ?>
						</div>
					<?php endif; ?>
				<?php endforeach; ?>

			</div>
		<?php endforeach; ?>
	</div>

	<?php do_action('woocommerce_before_account_orders_pagination'); ?>

	<?php if (1 < $customer_orders->max_num_pages) : ?>
		<div class="woocommerce-pagination flex items-center justify-between gap-3 mt-6">
			<?php if (1 !== $current_page) : ?>
				<a class="cr-btn cr-btn-ghost woocommerce-button" href="<?= esc_url(wc_get_endpoint_url('orders', $current_page - 1)); ?>"><?php esc_html_e('Più recenti', 'cardsrift'); ?></a>
			<?php else : ?>
				<span></span>
			<?php endif; ?>
			<?php if (intval($customer_orders->max_num_pages) !== $current_page) : ?>
				<a class="cr-btn cr-btn-ghost woocommerce-button" href="<?= esc_url(wc_get_endpoint_url('orders', $current_page + 1)); ?>"><?php esc_html_e('Più vecchi', 'cardsrift'); ?></a>
			<?php endif; ?>
		</div>
	<?php endif; ?>

<?php else : ?>

	<div class="cr-panel p-6 lg:p-8 text-center">
		<h2 class="font-metropolis font-semibold !text-lg"><?php esc_html_e('Nessun ordine, per ora', 'cardsrift'); ?></h2>
		<p class="text-sm text-th-muted leading-relaxed mt-2 max-w-[46ch] mx-auto"><?php esc_html_e('Qui troverai tutti i tuoi ordini con lo stato aggiornato: preparazione, spedizione, consegna.', 'cardsrift'); ?></p>
		<a class="cr-btn cr-btn-solid mt-5" href="<?= esc_url(home_url('/')); ?>"><?php esc_html_e('Sfoglia il catalogo', 'cardsrift'); ?></a>
	</div>

<?php endif; ?>

<?php do_action('woocommerce_after_account_orders', $has_orders); ?>
