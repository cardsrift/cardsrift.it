<?php

/**
 * CARRELLO — override CardsRift.
 *
 * Due colonne: gli articoli a sinistra, il riepilogo (sticky) a destra; su mobile
 * gli articoli, poi il riepilogo, con una barra fissa in basso che tiene totale e
 * CTA sempre a portata di pollice.
 *
 * Scelte specifiche per un negozio di carte:
 *  - ogni riga mostra i chip CONDIZIONE · LINGUA · FOIL: per una singola quegli
 *    attributi SONO il prodotto, e il carrello è l'ultimo posto in cui il cliente
 *    può verificare di aver preso la copia giusta;
 *  - il prezzo unitario compare accanto al subtotale solo quando la quantità è > 1;
 *  - le singole sono spesso pezzi unici: quando resti all'ultimo pezzo lo diciamo,
 *    e lo stepper non lascia superare la disponibilità reale.
 *
 * ⚠️ Classi obbligatorie per il JS di WooCommerce (cart.js): `woocommerce-cart-form`,
 * `woocommerce-cart-form__contents`, `cart_item`, `product-remove` e `.cart_totals`
 * (in cart-totals.php). Toglierle spegne gli aggiornamenti AJAX.
 *
 * @see woocommerce/templates/cart/cart.php
 * @version 9.8.0
 */

defined('ABSPATH') || exit;

cr_shop_open_section([
	'step'    => 'cart',
	'eyebrow' => __('Il tuo carrello', 'cardsrift'),
	'title'   => sprintf(
		_n('%d articolo nel carrello', '%d articoli nel carrello', WC()->cart->get_cart_contents_count(), 'cardsrift'),
		WC()->cart->get_cart_contents_count()
	),
]);

do_action('woocommerce_before_cart'); ?>

<div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_380px] gap-6 lg:gap-10 items-start">

	<!-- ============ COLONNA ARTICOLI ============ -->
	<div class="min-w-0">
		<form class="woocommerce-cart-form" action="<?= esc_url(wc_get_cart_url()); ?>" method="post">
			<?php do_action('woocommerce_before_cart_table'); ?>

			<div class="woocommerce-cart-form__contents cr-panel px-4 tb:px-6">
				<?php do_action('woocommerce_before_cart_contents'); ?>

				<?php
				foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
					$_product     = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
					$product_id   = apply_filters('woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key);
					$product_name = apply_filters('woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key);

					if (!$_product || !$_product->exists() || $cart_item['quantity'] <= 0 || !apply_filters('woocommerce_cart_item_visible', true, $cart_item, $cart_item_key)) {
						continue;
					}

					$permalink = apply_filters('woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink($cart_item) : '', $cart_item, $cart_item_key);
					$qty       = (int) $cart_item['quantity'];
					$stock     = $_product->get_stock_quantity(); // null = scorte non gestite
					$max       = $_product->is_sold_individually() ? 1 : $_product->get_max_purchase_quantity();
					$min       = $_product->is_sold_individually() ? 1 : 0;
					$thumb     = apply_filters('woocommerce_cart_item_thumbnail', $_product->get_image('woocommerce_thumbnail'), $cart_item, $cart_item_key);
				?>
					<div class="cr-line woocommerce-cart-form__cart-item <?= esc_attr(apply_filters('woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key)); ?>">

						<!-- miniatura -->
						<?php if ($permalink) : ?>
							<a class="cr-thumb" href="<?= esc_url($permalink); ?>" tabindex="-1" aria-hidden="true"><?= $thumb; // phpcs:ignore ?></a>
						<?php else : ?>
							<span class="cr-thumb"><?= $thumb; // phpcs:ignore ?></span>
						<?php endif; ?>

						<div class="flex-1 min-w-0 flex flex-col">

							<!-- nome + identità della carta + rimuovi -->
							<div class="flex items-start justify-between gap-2">
								<div class="min-w-0">
									<?php if ($permalink) : ?>
										<a class="font-metropolis font-semibold text-sm leading-snug text-th-ink no-underline hover:text-th-acc transition-colors" href="<?= esc_url($permalink); ?>"><?= wp_kses_post($product_name); ?></a>
									<?php else : ?>
										<span class="font-metropolis font-semibold text-sm leading-snug text-th-ink"><?= wp_kses_post($product_name); ?></span>
									<?php endif; ?>

									<?php cr_item_chips_html($_product); ?>

									<?php
									do_action('woocommerce_after_cart_item_name', $cart_item, $cart_item_key);
									// eventuali dati aggiunti da terze parti (i nostri attributi sono già chip)
									echo wc_get_formatted_cart_item_data($cart_item); // phpcs:ignore
									if ($_product->backorders_require_notification() && $_product->is_on_backorder($qty)) {
										echo wp_kses_post(apply_filters('woocommerce_cart_item_backorder_notification', '<p class="text-xs text-th-warn mt-1">' . esc_html__('Disponibile su ordinazione', 'cardsrift') . '</p>', $product_id));
									}
									?>
								</div>

								<span class="product-remove shrink-0">
									<?php
									echo apply_filters( // phpcs:ignore
										'woocommerce_cart_item_remove_link',
										sprintf(
											'<a href="%s" class="remove cr-remove" aria-label="%s" data-product_id="%s" data-product_sku="%s"><svg viewBox="0 0 24 24" stroke-linecap="round" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></a>',
											esc_url(wc_get_cart_remove_url($cart_item_key)),
											esc_attr(sprintf(__('Togli %s dal carrello', 'cardsrift'), wp_strip_all_tags($product_name))),
											esc_attr($product_id),
											esc_attr($_product->get_sku())
										),
										$cart_item_key
									);
									?>
								</span>
							</div>

							<!-- quantità + importo -->
							<div class="flex items-end justify-between gap-3 mt-3">
								<?php
								$qty_input = woocommerce_quantity_input([
									'input_name'   => "cart[{$cart_item_key}][qty]",
									'input_value'  => $qty,
									'max_value'    => $max,
									'min_value'    => $min,
									'product_name' => $product_name,
								], $_product, false);
								?>
								<?php if ($max && $min === $max) : ?>
									<span class="cr-qty cr-qty--fixed"><?= esc_html(sprintf(__('%d pz', 'cardsrift'), $qty)); ?></span>
									<?= apply_filters('woocommerce_cart_item_quantity', $qty_input, $cart_item_key, $cart_item); // phpcs:ignore ?>
								<?php else : ?>
									<div class="cr-qty">
										<button type="button" class="cr-qty__btn" data-cr-qty="-" aria-label="<?php esc_attr_e('Diminuisci', 'cardsrift'); ?>">−</button>
										<?= apply_filters('woocommerce_cart_item_quantity', $qty_input, $cart_item_key, $cart_item); // phpcs:ignore ?>
										<button type="button" class="cr-qty__btn" data-cr-qty="+" aria-label="<?php esc_attr_e('Aumenta', 'cardsrift'); ?>">+</button>
									</div>
								<?php endif; ?>

								<div class="text-right">
									<span class="cr-price"><?= apply_filters('woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal($_product, $qty), $cart_item, $cart_item_key); // phpcs:ignore ?></span>
									<?php if ($qty > 1) : ?>
										<span class="block text-xs text-th-soft mt-0.5 tabular-nums">
											<?php printf(esc_html__('%s cad.', 'cardsrift'), wp_kses_post(apply_filters('woocommerce_cart_item_price', WC()->cart->get_product_price($_product), $cart_item, $cart_item_key))); ?>
										</span>
									<?php endif; ?>
								</div>
							</div>

							<?php // pezzo unico o scorta agli sgoccioli: dirlo qui evita la delusione al checkout ?>
							<?php if ($stock !== null && $stock <= 3) : ?>
								<span class="cr-stock cr-stock--low mt-2.5">
									<?= $stock === 1
										? esc_html__('Ultimo pezzo disponibile', 'cardsrift')
										: esc_html(sprintf(__('Restano %d pezzi', 'cardsrift'), $stock)); ?>
								</span>
							<?php endif; ?>

						</div>
					</div>
				<?php } ?>

				<?php do_action('woocommerce_cart_contents'); ?>
				<?php do_action('woocommerce_after_cart_contents'); ?>
			</div>

			<?php // il pulsante resta per chi non ha JavaScript: con JS il carrello si aggiorna da solo
			// (e la riga sparisce del tutto, a meno che un'estensione non ci metta altro) ?>
			<div class="cr-cartactions flex flex-wrap items-center gap-3 mt-4">
				<button type="submit" class="cr-btn cr-btn-ghost cr-updatecart" name="update_cart" value="<?php esc_attr_e('Aggiorna carrello', 'cardsrift'); ?>"><?php esc_html_e('Aggiorna carrello', 'cardsrift'); ?></button>
				<?php do_action('woocommerce_cart_actions'); ?>
			</div>

			<?php if (wc_coupons_enabled()) : ?>
				<details class="cr-panel mt-4 group">
					<summary class="flex items-center gap-2.5 cursor-pointer list-none px-4 tb:px-6 py-4 font-metropolis font-semibold text-sm text-th-ink">
						<svg class="w-[18px] h-[18px] stroke-th-acc fill-transparent shrink-0" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 12a2 2 0 012-2V7H2v3a2 2 0 010 4v3h20v-3a2 2 0 01-2-2zM9 8v8"/></svg>
						<?php esc_html_e('Hai un codice sconto?', 'cardsrift'); ?>
						<svg class="w-4 h-4 ml-auto stroke-th-muted fill-transparent transition-transform group-open:rotate-180" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
					</summary>
					<div class="coupon flex flex-col tb:flex-row gap-2.5 px-4 tb:px-6 pb-5">
						<label for="coupon_code" class="screen-reader-text"><?php esc_html_e('Codice sconto', 'cardsrift'); ?></label>
						<input type="text" name="coupon_code" class="cr-input input-text flex-1" id="coupon_code" value="" placeholder="<?php esc_attr_e('Es. BENVENUTO5', 'cardsrift'); ?>" autocapitalize="characters" />
						<button type="submit" class="cr-btn cr-btn-ghost justify-center shrink-0" name="apply_coupon" value="<?php esc_attr_e('Applica', 'cardsrift'); ?>"><?php esc_html_e('Applica', 'cardsrift'); ?></button>
						<?php do_action('woocommerce_cart_coupon'); ?>
					</div>
				</details>
			<?php endif; ?>

			<?php wp_nonce_field('woocommerce-cart', 'woocommerce-cart-nonce'); ?>
			<?php do_action('woocommerce_after_cart_table'); ?>
		</form>
	</div>

	<!-- ============ COLONNA RIEPILOGO ============ -->
	<aside class="lg:sticky lg:top-[calc(var(--header-h-desktop)+1.5rem)]">
		<?php
		do_action('woocommerce_before_cart_collaterals');
		woocommerce_cart_totals();
		?>
	</aside>

</div>

<a class="inline-flex items-center gap-2 mt-7 font-metropolis font-semibold text-sm text-th-acc no-underline hover:underline" href="<?= esc_url(home_url('/')); ?>">
	<svg class="w-4 h-4 stroke-current fill-transparent" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M11 18l-6-6 6-6" /></svg>
	<?php esc_html_e('Continua a sfogliare', 'cardsrift'); ?>
</a>

<?php // cross-sell e altre estensioni: a tutta larghezza, sotto le due colonne ?>
<div class="cart-collaterals mt-12 lg:mt-16">
	<?php do_action('woocommerce_cart_collaterals'); ?>
</div>

<?php // spazio per la barra fissa mobile: nessun contenuto deve finirci sotto ?>
<div class="h-20 lg:hidden" aria-hidden="true"></div>

<?php
do_action('woocommerce_after_cart');

cr_shop_close_section();
cr_shop_trust_band();
