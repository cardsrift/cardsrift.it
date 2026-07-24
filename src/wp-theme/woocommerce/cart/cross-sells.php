<?php

/**
 * CROSS-SELL nel carrello — override CardsRift.
 * Stessa griglia e stessa card del resto del sito (cr_product_card): il carrello
 * non è un posto diverso dal negozio. Titolo sobrio: un suggerimento, non una spinta.
 *
 * @see woocommerce/templates/cart/cross-sells.php
 * @version 9.8.0
 */

defined('ABSPATH') || exit;

if (!$cross_sells) {
	return;
}
?>

<div class="cross-sells">
	<div class="cr-eyebrow mb-2.5"><?php esc_html_e('Già che sei qui', 'cardsrift'); ?></div>
	<h2 class="font-metropolis font-bold !text-xl lg:!text-2xl mb-6"><?php esc_html_e('Potrebbero interessarti', 'cardsrift'); ?></h2>

	<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 lg:gap-4">
		<?php foreach ($cross_sells as $cross_sell) : ?>
			<?php cr_product_card($cross_sell->get_id()); ?>
		<?php endforeach; ?>
	</div>
</div>
