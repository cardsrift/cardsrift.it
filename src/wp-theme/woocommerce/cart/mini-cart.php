<?php

/**
 * MINI-CARRELLO (contenuto del drawer) — override CardsRift.
 *
 * È il recap che si apre da destra dopo l'aggiunta: cosa hai appena messo dentro,
 * cosa c'era già, quanto fa, e due strade avanti. Stesse righe del carrello vero
 * (miniatura + chip condizione/lingua/foil): chi compra una singola deve
 * riconoscere la copia esatta anche qui.
 *
 * ⚠️ Questo template è anche un FRAMMENTO AJAX (vedi cr_minicart_fragment in
 * includes/shop.php): viene rigenerato a ogni aggiunta/rimozione e sostituito
 * dentro `div.cr-drawer__content`. I link di rimozione devono mantenere la classe
 * `remove_from_cart_button`, che è ciò che WooCommerce intercetta per rimuovere
 * in AJAX senza ricaricare.
 *
 * @see woocommerce/templates/cart/mini-cart.php
 * @version 9.8.0
 */

defined('ABSPATH') || exit;

do_action('woocommerce_before_mini_cart');

if (WC()->cart->is_empty()) : ?>

	<div class="flex flex-col items-center justify-center text-center flex-1 px-6 py-12">
		<span class="grid place-items-center w-14 h-14 rounded-full bg-th-accsoft">
			<svg class="w-6 h-6 stroke-th-acc fill-transparent" viewBox="0 0 24 24" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 4h2l2.4 12h11.2l2.4-9H7" /><circle cx="9" cy="20" r="1.4" /><circle cx="17" cy="20" r="1.4" /></svg>
		</span>
		<p class="font-metropolis font-semibold text-base text-th-ink mt-5"><?php esc_html_e('Il carrello è vuoto', 'cardsrift'); ?></p>
		<p class="text-sm text-th-muted leading-relaxed mt-2 max-w-[32ch]"><?php esc_html_e('Il catalogo è piccolo e scelto a mano: si sfoglia meglio di quanto si cerchi.', 'cardsrift'); ?></p>
		<a class="cr-btn cr-btn-ghost mt-6" href="<?= esc_url(home_url('/')); ?>" data-cr-close><?php esc_html_e('Continua a sfogliare', 'cardsrift'); ?></a>
	</div>

<?php else : ?>

	<div class="flex-1 overflow-y-auto px-5 woocommerce-mini-cart cart_list <?= esc_attr($list_class); ?>" role="list">
		<?php
		foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
			$_product   = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
			$product_id = apply_filters('woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key);

			if (!$_product || !$_product->exists() || $cart_item['quantity'] <= 0 || !apply_filters('woocommerce_widget_cart_item_visible', true, $cart_item, $cart_item_key)) {
				continue;
			}

			$product_name = apply_filters('woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key);
			$permalink    = apply_filters('woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink($cart_item) : '', $cart_item, $cart_item_key);
			$qty          = (int) $cart_item['quantity'];
		?>
			<div class="cr-line woocommerce-mini-cart-item <?= esc_attr(apply_filters('woocommerce_mini_cart_item_class', 'mini_cart_item', $cart_item, $cart_item_key)); ?>" data-cr-item="<?= esc_attr($product_id); ?>" role="listitem">

				<span class="cr-thumb !w-12"><?= $_product->get_image('woocommerce_thumbnail'); // phpcs:ignore ?></span>

				<div class="flex-1 min-w-0">
					<div class="flex items-start justify-between gap-2">
						<div class="min-w-0">
							<?php if ($permalink) : ?>
								<a class="font-metropolis font-semibold text-xs leading-snug text-th-ink no-underline hover:text-th-acc transition-colors" href="<?= esc_url($permalink); ?>"><?= wp_kses_post($product_name); ?></a>
							<?php else : ?>
								<span class="font-metropolis font-semibold text-xs leading-snug text-th-ink"><?= wp_kses_post($product_name); ?></span>
							<?php endif; ?>
							<?php cr_item_chips_html($_product); ?>
							<span class="cr-line__new"><?php esc_html_e('Appena aggiunto', 'cardsrift'); ?></span>
						</div>

						<?php
						echo apply_filters( // phpcs:ignore
							'woocommerce_cart_item_remove_link',
							sprintf(
								'<a href="%s" class="remove remove_from_cart_button cr-remove" aria-label="%s" data-product_id="%s" data-cart_item_key="%s" data-product_sku="%s"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></a>',
								esc_url(wc_get_cart_remove_url($cart_item_key)),
								esc_attr(sprintf(__('Togli %s dal carrello', 'cardsrift'), wp_strip_all_tags($product_name))),
								esc_attr($product_id),
								esc_attr($cart_item_key),
								esc_attr($_product->get_sku())
							),
							$cart_item_key
						);
						?>
					</div>

					<?php
					// Massimo acquistabile: pezzo unico, scorte reali, oppure nessun limite.
					$cr_max = $_product->is_sold_individually() ? 1 : null;
					if ($cr_max === null && $_product->managing_stock() && !$_product->backorders_allowed()) {
						$cr_max = (int) $_product->get_stock_quantity();
					}
					?>
					<div class="flex items-center justify-between gap-2 mt-2">
						<?php if ($cr_max === 1) : ?>
							<?php // un pezzo solo: la quantità è un fatto, non una scelta ?>
							<span class="cr-qty cr-qty--fixed !h-8"><?= esc_html__('1 pz', 'cardsrift'); ?></span>
						<?php else : ?>
							<div class="cr-qty cr-qty--mini">
								<button type="button" class="cr-qty__btn" data-cr-qty="-" aria-label="<?= esc_attr(sprintf(__('Diminuisci quantità di %s', 'cardsrift'), wp_strip_all_tags($product_name))); ?>">−</button>
								<label class="screen-reader-text" for="cr-qty-<?= esc_attr($cart_item_key); ?>"><?= esc_attr(sprintf(__('Quantità di %s', 'cardsrift'), wp_strip_all_tags($product_name))); ?></label>
								<input type="number" class="qty" id="cr-qty-<?= esc_attr($cart_item_key); ?>" value="<?= esc_attr($qty); ?>" min="1" <?= $cr_max !== null ? 'max="' . esc_attr($cr_max) . '"' : ''; ?> step="1" inputmode="numeric" autocomplete="off" data-cart_item_key="<?= esc_attr($cart_item_key); ?>" />
								<button type="button" class="cr-qty__btn" data-cr-qty="+" aria-label="<?= esc_attr(sprintf(__('Aumenta quantità di %s', 'cardsrift'), wp_strip_all_tags($product_name))); ?>">+</button>
							</div>
						<?php endif; ?>
						<span class="cr-price !text-sm"><?= apply_filters('woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal($_product, $qty), $cart_item, $cart_item_key); // phpcs:ignore ?></span>
					</div>
				</div>
			</div>
		<?php } ?>
	</div>

	<?php do_action('woocommerce_mini_cart_contents'); ?>

	<?php // mt-auto: totali e CTA restano incollati in fondo al pannello anche con un solo articolo ?>
	<div class="shrink-0 mt-auto border-t border-th-line px-5 pt-4 pb-5">
		<div class="cr-sumrow cr-sumrow--total !border-0 !pt-0 !mt-0 woocommerce-mini-cart__total total">
			<span><?php esc_html_e('Subtotale', 'cardsrift'); ?></span>
			<span><?= wp_kses_post(WC()->cart->get_cart_subtotal()); ?></span>
		</div>
		<p class="text-xs text-th-soft mt-1 mb-0"><?php esc_html_e('Spedizione e sconti si calcolano al passo successivo.', 'cardsrift'); ?></p>

		<div class="flex flex-col gap-2.5 mt-4 woocommerce-mini-cart__buttons">
			<a class="cr-btn cr-btn-solid w-full justify-center" href="<?= esc_url(wc_get_checkout_url()); ?>"><?php esc_html_e('Vai al checkout', 'cardsrift'); ?></a>
			<a class="cr-btn cr-btn-ghost w-full justify-center" href="<?= esc_url(wc_get_cart_url()); ?>"><?php esc_html_e('Vedi il carrello', 'cardsrift'); ?></a>
			<button type="button" class="font-metropolis font-semibold text-sm text-th-muted hover:text-th-acc transition-colors py-1 bg-transparent border-0 cursor-pointer" data-cr-close><?php esc_html_e('Continua a sfogliare', 'cardsrift'); ?></button>
		</div>
	</div>

<?php endif;

do_action('woocommerce_after_mini_cart');
