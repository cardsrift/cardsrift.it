<?php

/**
 * DETTAGLIO ORDINE — override CardsRift.
 * In cima lo stato (chip + frase in italiano, non il nome tecnico dello stato di
 * WooCommerce), poi gli aggiornamenti se ce ne sono, poi le righe e gli indirizzi
 * (order/order-details.php, agganciato a woocommerce_view_order).
 *
 * @see woocommerce/templates/myaccount/view-order.php
 * @version 9.8.0
 */

defined('ABSPATH') || exit;

$notes = $order->get_customer_order_notes();
?>

<div class="cr-panel p-5 lg:p-6 mb-6">
	<div class="flex flex-wrap items-center gap-3">
		<?php cr_order_status_chip($order); ?>
		<span class="font-metropolis font-semibold text-sm text-th-ink tabular-nums">#<?= esc_html($order->get_order_number()); ?></span>
		<time class="text-xs text-th-soft" datetime="<?= esc_attr($order->get_date_created()->date('c')); ?>">
			<?= esc_html(sprintf(__('Ordinato il %s', 'cardsrift'), wc_format_datetime($order->get_date_created()))); ?>
		</time>
		<span class="cr-price ml-auto"><?= wp_kses_post($order->get_formatted_order_total()); ?></span>
	</div>

	<?php if ($line = cr_order_status_line($order)) : ?>
		<p class="text-sm text-th-muted leading-relaxed mt-3 mb-0"><?= esc_html($line); ?></p>
	<?php endif; ?>
</div>

<?php if ($notes) : ?>
	<div class="mb-6">
		<h2 class="font-metropolis font-semibold !text-lg mb-3"><?php esc_html_e('Aggiornamenti', 'cardsrift'); ?></h2>
		<ol class="woocommerce-OrderUpdates cr-notes">
			<?php foreach ($notes as $note) : ?>
				<li class="woocommerce-OrderUpdate note">
					<span class="meta"><?= esc_html(date_i18n(__('j F Y, H:i', 'cardsrift'), strtotime($note->comment_date))); ?></span>
					<div class="description"><?= wpautop(wptexturize($note->comment_content)); // phpcs:ignore ?></div>
				</li>
			<?php endforeach; ?>
		</ol>
	</div>
<?php endif; ?>

<?php do_action('woocommerce_view_order', $order_id); ?>
