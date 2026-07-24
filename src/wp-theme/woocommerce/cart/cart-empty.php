<?php

/**
 * CARRELLO VUOTO — override CardsRift.
 * Un carrello vuoto non è un errore: è qualcuno che sta ancora sfogliando. Niente
 * messaggio di sistema, ma le tre porte del negozio (i giochi) per riprendere da lì.
 *
 * ⚠️ La classe `wc-empty-cart-message` serve a cart.js, che sostituisce proprio
 * questo blocco quando l'ultimo articolo viene tolto in AJAX.
 * Il messaggio predefinito è disattivato in includes/shop.php.
 *
 * @see woocommerce/templates/cart/cart-empty.php
 * @version 9.8.0
 */

defined('ABSPATH') || exit;

$cr_games = defined('CR_GAMES') ? CR_GAMES : ['magic', 'pokemon'];

cr_shop_open_section(['step' => 'cart', 'width' => 'tw-container-md']);

do_action('woocommerce_cart_is_empty');
?>

<div class="wc-empty-cart-message text-center py-6 lg:py-12">

	<span class="grid place-items-center w-16 h-16 mx-auto rounded-full bg-th-accsoft">
		<svg class="w-7 h-7 stroke-th-acc fill-transparent" viewBox="0 0 24 24" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
			<path d="M3 4h2l2.4 12h11.2l2.4-9H7" />
			<circle cx="9" cy="20" r="1.4" />
			<circle cx="17" cy="20" r="1.4" />
		</svg>
	</span>

	<h1 class="font-metropolis font-bold !text-2xl lg:!text-3xl !leading-tight mt-6"><?php esc_html_e('Il carrello è ancora vuoto', 'cardsrift'); ?></h1>
	<p class="text-th-muted leading-relaxed mt-3 max-w-[46ch] mx-auto"><?php esc_html_e('Il catalogo è piccolo e scelto a mano: si sfoglia meglio di quanto si cerchi. Da dove vuoi cominciare?', 'cardsrift'); ?></p>

	<div class="flex flex-col tb:flex-row flex-wrap justify-center gap-2.5 mt-8">
		<?php foreach ($cr_games as $cr_g) : ?>
			<a class="cr-btn cr-btn-ghost justify-center" href="<?= esc_url(home_url('/' . $cr_g . '/')); ?>">
				<?= esc_html(function_exists('cr_game_label') ? cr_game_label($cr_g) : $cr_g); ?>
			</a>
		<?php endforeach; ?>
	</div>

	<a class="inline-block mt-7 font-metropolis font-semibold text-sm text-th-acc no-underline hover:underline" href="<?= esc_url(home_url('/')); ?>"><?php esc_html_e('oppure torna alla home', 'cardsrift'); ?></a>
</div>

<?php
cr_shop_close_section();
cr_shop_trust_band();
