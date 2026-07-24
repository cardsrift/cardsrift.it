<?php

/**
 * PAGAMENTO + CONFERMA — override CardsRift.
 *
 * Ultimo passo del percorso, nella colonna di sinistra: metodi di pagamento come
 * card selezionabili, condizioni di vendita, e il pulsante che chiude l'ordine.
 * Accanto al pulsante le tre rassicurazioni che contano proprio lì (pagamento
 * sicuro, cosa succede dopo, come imballiamo): l'ansia si scioglie sul posto,
 * non in una pagina di FAQ.
 *
 * ⚠️ Vanno mantenuti: `.woocommerce-checkout-payment` (frammento AJAX),
 * `#place_order`, `name="payment_method"` e il nonce di processo checkout.
 *
 * @see woocommerce/templates/checkout/payment.php
 * @version 9.8.0
 */

defined('ABSPATH') || exit;

if (!wp_doing_ajax()) {
	do_action('woocommerce_review_order_before_payment');
}
?>

<div id="payment" class="woocommerce-checkout-payment">

	<?php if (WC()->cart && WC()->cart->needs_payment()) : ?>
		<h2 class="font-metropolis font-semibold !text-lg mb-4"><?php esc_html_e('Come vuoi pagare', 'cardsrift'); ?></h2>

		<ul class="wc_payment_methods payment_methods methods cr-optlist">
			<?php
			if (!empty($available_gateways)) {
				foreach ($available_gateways as $gateway) {
					wc_get_template('checkout/payment-method.php', ['gateway' => $gateway]);
				}
			} else {
				echo '<li class="!border-0 !bg-transparent">';
				wc_print_notice(apply_filters('woocommerce_no_available_payment_methods_message', WC()->customer->get_billing_country() ? esc_html__('Non risultano metodi di pagamento disponibili. Scrivici: troviamo il modo.', 'cardsrift') : esc_html__('Compila i tuoi dati qui sopra per vedere i metodi di pagamento.', 'cardsrift')), 'notice'); // phpcs:ignore
				echo '</li>';
			}
			?>
		</ul>
	<?php endif; ?>

	<div class="form-row place-order mt-7">
		<noscript>
			<p class="cr-notice cr-notice--info mb-4">
				<?php printf(esc_html__('Il tuo browser non esegue JavaScript: premi %1$sAggiorna totali%2$s prima di concludere, altrimenti gli importi potrebbero non essere aggiornati.', 'cardsrift'), '<em>', '</em>'); ?>
			</p>
			<button type="submit" class="cr-btn cr-btn-ghost mb-4" name="woocommerce_checkout_update_totals" value="<?php esc_attr_e('Aggiorna totali', 'cardsrift'); ?>"><?php esc_html_e('Aggiorna totali', 'cardsrift'); ?></button>
		</noscript>

		<?php wc_get_template('checkout/terms.php'); ?>

		<?php do_action('woocommerce_review_order_before_submit'); ?>

		<?php
		echo apply_filters( // phpcs:ignore
			'woocommerce_order_button_html',
			'<button type="submit" class="cr-btn cr-btn-solid w-full justify-center !py-4 !text-base mt-5" name="woocommerce_checkout_place_order" id="place_order" value="' . esc_attr($order_button_text) . '" data-value="' . esc_attr($order_button_text) . '">' . esc_html($order_button_text) . '</button>'
		);
		?>

		<?php do_action('woocommerce_review_order_after_submit'); ?>

		<ul class="flex flex-col tb:flex-row tb:flex-wrap gap-y-2 gap-x-6 mt-5 text-xs text-th-muted">
			<li class="flex items-center gap-2">
				<svg class="w-4 h-4 stroke-th-acc fill-transparent shrink-0" viewBox="0 0 24 24" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7 3v6c0 4.5-3 7.7-7 9-4-1.3-7-4.5-7-9V6l7-3z" /></svg>
				<?php esc_html_e('Connessione cifrata', 'cardsrift'); ?>
			</li>
			<li class="flex items-center gap-2">
				<svg class="w-4 h-4 stroke-th-acc fill-transparent shrink-0" viewBox="0 0 24 24" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h11v8H3zM14 10h4l3 3v2h-7z" /><circle cx="7.5" cy="17.5" r="1.5" /><circle cx="17.5" cy="17.5" r="1.5" /></svg>
				<?php esc_html_e('Parte in 24/48h, tracciato', 'cardsrift'); ?>
			</li>
			<li class="flex items-center gap-2">
				<svg class="w-4 h-4 stroke-th-acc fill-transparent shrink-0" viewBox="0 0 24 24" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8l-9-5-9 5v8l9 5 9-5V8zM3 8l9 5 9-5M12 13v8" /></svg>
				<?php esc_html_e('Bustina, toploader, cartoncino', 'cardsrift'); ?>
			</li>
		</ul>

		<?php wp_nonce_field('woocommerce-process_checkout', 'woocommerce-process-checkout-nonce'); ?>
	</div>
</div>

<?php
if (!wp_doing_ajax()) {
	do_action('woocommerce_review_order_after_payment');
}
