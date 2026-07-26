<?php

/**
 * INDIRIZZI — override CardsRift.
 * Due schede: quella vuota lo dice e invita a compilarla, invece di lasciare un
 * riquadro muto. Il link di modifica è il pulsante della scheda, non un "Edit"
 * appeso al titolo.
 *
 * @see woocommerce/templates/myaccount/my-address.php
 * @version 9.8.0
 */

defined('ABSPATH') || exit;

$customer_id = get_current_user_id();

if (!wc_ship_to_billing_address_only() && wc_shipping_enabled()) {
	$get_addresses = apply_filters('woocommerce_my_account_get_addresses', [
		'billing'  => __('Indirizzo di fatturazione', 'cardsrift'),
		'shipping' => __('Indirizzo di spedizione', 'cardsrift'),
	], $customer_id);
} else {
	$get_addresses = apply_filters('woocommerce_my_account_get_addresses', [
		'billing' => __('Indirizzo di fatturazione', 'cardsrift'),
	], $customer_id);
}
?>

<p class="text-th-muted leading-relaxed mb-6">
	<?= wp_kses_post(apply_filters('woocommerce_my_account_my_address_description', esc_html__('Questi indirizzi vengono proposti già compilati al prossimo ordine: sistemali una volta e non ci pensi più.', 'cardsrift'))); ?>
</p>

<div class="woocommerce-Addresses grid grid-cols-1 tb:grid-cols-2 gap-4">
	<?php foreach ($get_addresses as $name => $address_title) :
		$address = wc_get_account_formatted_address($name);
	?>
		<div class="woocommerce-Address cr-panel p-5 flex flex-col">
			<header class="woocommerce-Address-title">
				<h2 class="cr-label"><?= esc_html($address_title); ?></h2>
			</header>

			<address class="cr-address flex-1 min-w-0">
				<?php if ($address) : ?>
					<?= wp_kses_post($address); ?>
				<?php else : ?>
					<span class="text-th-soft"><?php esc_html_e('Non ancora impostato.', 'cardsrift'); ?></span>
				<?php endif; ?>
				<?php do_action('woocommerce_my_account_after_my_address', $name); ?>
			</address>

			<a class="cr-btn cr-btn-ghost !py-2.5 !text-xs w-fit mt-4 edit" href="<?= esc_url(wc_get_endpoint_url('edit-address', $name)); ?>">
				<?= $address
					? esc_html__('Modifica', 'cardsrift')
					: esc_html__('Aggiungi', 'cardsrift'); ?>
			</a>
		</div>
	<?php endforeach; ?>
</div>
