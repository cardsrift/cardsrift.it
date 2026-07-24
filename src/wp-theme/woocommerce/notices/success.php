<?php

/**
 * Notice di conferma — override CardsRift.
 * ⚠️ La classe `woocommerce-message` va mantenuta (vedi notices/error.php).
 *
 * @see woocommerce/templates/notices/success.php
 * @version 9.8.0
 */

defined('ABSPATH') || exit;

if (!$notices) {
	return;
}
?>
<?php foreach ($notices as $notice) : ?>
	<div class="woocommerce-message cr-notice cr-notice--success"<?= wc_get_notice_data_attr($notice); // phpcs:ignore ?> role="alert">
		<?= wc_kses_notice($notice['notice']); ?>
	</div>
<?php endforeach; ?>
