<?php

/**
 * CHECKOUT — override CardsRift.
 *
 * Una sola pagina, due colonne: a sinistra il percorso da compilare (contatti →
 * indirizzo → note → pagamento → conferma), a destra il riepilogo sticky di ciò
 * che si sta comprando. Su mobile il riepilogo sale in cima, ripiegato: si apre
 * se serve, ma non fa scorrere 400px prima del primo campo.
 *
 * Il blocco pagamento è spostato nella colonna sinistra (in coda al percorso):
 * scegliere come pagare è un'AZIONE, e le azioni stanno dove si sta lavorando —
 * non spiaccicate in una barra laterale da 400px. Lo sgancio dell'hook è in
 * includes/shop.php; l'aggiornamento AJAX continua a funzionare perché WooCommerce
 * rigenera i frammenti per classe (`.woocommerce-checkout-payment`), non per posizione.
 *
 * ⚠️ Da non toccare: `form.checkout`, `#order_review`,
 * `.woocommerce-checkout-review-order` e il nonce — ci lavora checkout.js.
 *
 * @see woocommerce/templates/checkout/form-checkout.php
 * @version 9.8.0
 */

defined('ABSPATH') || exit;

cr_shop_open_section([
	'step'    => 'checkout',
	'eyebrow' => __('Ci siamo quasi', 'cardsrift'),
	'title'   => __('Completa l’ordine', 'cardsrift'),
]);

do_action('woocommerce_before_checkout_form', $checkout);

// Registrazione obbligatoria e utente non loggato: non si può proseguire.
if (!$checkout->is_registration_enabled() && $checkout->is_registration_required() && !is_user_logged_in()) {
	echo '<p class="cr-notice cr-notice--info">' . esc_html(apply_filters('woocommerce_checkout_must_be_logged_in_message', __('Per concludere l’ordine devi accedere al tuo account.', 'cardsrift'))) . '</p>';
	cr_shop_close_section();
	return;
}
?>

<form name="checkout" method="post" class="checkout woocommerce-checkout cr-form" action="<?= esc_url(wc_get_checkout_url()); ?>" enctype="multipart/form-data" aria-label="<?php esc_attr_e('Checkout', 'cardsrift'); ?>">

	<div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_400px] gap-8 lg:gap-12 items-start">

		<!-- ============ COLONNA RIEPILOGO (in cima su mobile) ============ -->
		<aside class="order-first lg:order-last lg:sticky lg:top-[calc(var(--header-h-desktop)+1.5rem)]">
			<?php do_action('woocommerce_checkout_before_order_review_heading'); ?>

			<details class="cr-panel overflow-hidden" data-cr-summary open>
				<summary class="flex items-center gap-3 cursor-pointer list-none p-5 lg:hidden">
					<svg class="w-5 h-5 stroke-th-acc fill-transparent shrink-0" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 4h2l2.4 12h11.2l2.4-9H7" /><circle cx="9" cy="20" r="1.4" /><circle cx="17" cy="20" r="1.4" /></svg>
					<span class="font-metropolis font-semibold text-sm"><?php esc_html_e('Riepilogo ordine', 'cardsrift'); ?></span>
					<span class="cr-price ml-auto"><?= wp_kses_post(WC()->cart->get_total()); ?></span>
					<svg class="w-4 h-4 stroke-th-muted fill-transparent shrink-0 transition-transform" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6" /></svg>
				</summary>

				<div class="px-5 pb-5 lg:pt-5">
					<h2 id="order_review_heading" class="hidden lg:block font-metropolis font-semibold !text-lg mb-1"><?php esc_html_e('Il tuo ordine', 'cardsrift'); ?></h2>

					<?php do_action('woocommerce_checkout_before_order_review'); ?>
					<div id="order_review" class="woocommerce-checkout-review-order">
						<?php do_action('woocommerce_checkout_order_review'); ?>
					</div>
					<?php do_action('woocommerce_checkout_after_order_review'); ?>
				</div>
			</details>
		</aside>

		<!-- ============ COLONNA DATI ============ -->
		<div class="min-w-0 flex flex-col gap-8 lg:gap-10">

			<?php if ($checkout->get_checkout_fields()) : ?>
				<?php do_action('woocommerce_checkout_before_customer_details'); ?>
				<div id="customer_details" class="flex flex-col gap-8 lg:gap-10">
					<?php do_action('woocommerce_checkout_billing'); ?>
					<?php do_action('woocommerce_checkout_shipping'); ?>
				</div>
				<?php do_action('woocommerce_checkout_after_customer_details'); ?>
			<?php endif; ?>

			<?php // pagamento + termini + "Concludi l'ordine": la fine del percorso, non un accessorio ?>
			<?php woocommerce_checkout_payment(); ?>
		</div>

	</div>
</form>

<?php
do_action('woocommerce_after_checkout_form', $checkout);

cr_shop_close_section();
cr_shop_trust_band();
