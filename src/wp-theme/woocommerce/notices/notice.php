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
	<?php // role="status" (non "alert": non è un errore) — così anche gli avvisi iniettati
	// via AJAX dopo il caricamento vengono annunciati, come già fanno error e success. ?>
	<div class="woocommerce-info cr-notice cr-notice--info" role="status"<?= wc_get_notice_data_attr($notice); // phpcs:ignore ?>>
		<?= wc_kses_notice($notice['notice']); ?>
	</div>
<?php endforeach; ?>
