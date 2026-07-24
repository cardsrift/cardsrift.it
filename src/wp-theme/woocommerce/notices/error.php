<?php

/**
 * Notice di errore — override CardsRift.
 * ⚠️ La classe `woocommerce-error` va mantenuta: cart.js e checkout.js rimuovono
 * proprio questi elementi quando ricaricano il carrello/il checkout in AJAX.
 *
 * @see woocommerce/templates/notices/error.php
 * @version 9.8.0
 */

defined('ABSPATH') || exit;

if (!$notices) {
	return;
}
?>
<ul class="woocommerce-error cr-notice cr-notice--error" role="alert">
	<?php foreach ($notices as $notice) : ?>
		<li<?= wc_get_notice_data_attr($notice); // phpcs:ignore ?>>
			<?= wc_kses_notice($notice['notice']); ?>
		</li>
	<?php endforeach; ?>
</ul>
