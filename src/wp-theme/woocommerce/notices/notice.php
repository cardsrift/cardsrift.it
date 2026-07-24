<?php

/**
 * Notice informativo — override CardsRift.
 * ⚠️ La classe `woocommerce-info` va mantenuta (vedi notices/error.php).
 *
 * @see woocommerce/templates/notices/notice.php
 * @version 9.8.0
 */

defined('ABSPATH') || exit;

if (!$notices) {
	return;
}
?>
<?php foreach ($notices as $notice) : ?>
	<div class="woocommerce-info cr-notice cr-notice--info"<?= wc_get_notice_data_attr($notice); // phpcs:ignore ?>>
		<?= wc_kses_notice($notice['notice']); ?>
	</div>
<?php endforeach; ?>
