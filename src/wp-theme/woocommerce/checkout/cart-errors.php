<?php

/**
 * CHECKOUT BLOCCATO da problemi nel carrello — override CardsRift.
 * Succede tipicamente quando una singola è stata venduta mentre era nel carrello:
 * capita, il catalogo è fatto di pezzi unici. Si dice cosa fare, non si accusa.
 *
 * @see woocommerce/templates/checkout/cart-errors.php
 * @version 9.8.0
 */

defined('ABSPATH') || exit;

cr_shop_open_section([
	'step'    => 'checkout',
	'eyebrow' => __('Un attimo', 'cardsrift'),
	'title'   => __('Qualcosa nel carrello va sistemato', 'cardsrift'),
	'lead'    => __('Il nostro catalogo è fatto in buona parte di pezzi unici: può capitare che una carta venga venduta mentre è ancora nel tuo carrello. Torna indietro e correggi, ci vuole un attimo.', 'cardsrift'),
	'width'   => 'tw-container-md',
]);

// Gli avvisi arrivano qui perché li abbiamo tolti dall'hook che li stampava fuori
// dal layout (vedi cr_shop_rehook in includes/shop.php).
woocommerce_output_all_notices();

do_action('woocommerce_cart_has_errors');
?>

<a class="cr-btn cr-btn-solid mt-6" href="<?= esc_url(wc_get_cart_url()); ?>">
	<svg class="w-4 h-4 stroke-current fill-transparent" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M11 18l-6-6 6-6" /></svg>
	<?php esc_html_e('Torna al carrello', 'cardsrift'); ?>
</a>

<?php
cr_shop_close_section();
