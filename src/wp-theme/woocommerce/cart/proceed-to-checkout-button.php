<?php

/**
 * CTA "vai al checkout" — override CardsRift.
 * La classe `checkout-button` resta: alcune estensioni ci si agganciano.
 *
 * @see woocommerce/templates/cart/proceed-to-checkout-button.php
 * @version 9.8.0
 */

defined('ABSPATH') || exit;
?>
<a href="<?= esc_url(wc_get_checkout_url()); ?>" class="cr-btn cr-btn-solid w-full justify-center checkout-button wc-forward">
	<?php esc_html_e('Vai al checkout', 'cardsrift'); ?>
	<svg class="w-4 h-4 stroke-current fill-transparent" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
</a>
