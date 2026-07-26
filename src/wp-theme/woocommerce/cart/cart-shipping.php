<?php

/**
 * SPEDIZIONE nel riepilogo — override CardsRift.
 * Da riga di tabella a blocco: una sola tariffa diventa un valore secco, più
 * tariffe diventano una lista di scelte. Sotto, la destinazione e il link per
 * cambiarla (calcolatore) — così il costo è noto già nel carrello.
 *
 * ⚠️ `id="shipping_method"` e la classe `shipping_method` sui radio servono al JS
 * di WooCommerce per ricalcolare i totali al cambio di tariffa.
 *
 * @see woocommerce/templates/cart/cart-shipping.php
 * @version 9.8.0
 */

defined('ABSPATH') || exit;

$formatted_destination    = isset($formatted_destination) ? $formatted_destination : WC()->countries->get_formatted_address($package['destination'], ', ');
$has_calculated_shipping  = !empty($has_calculated_shipping);
$show_shipping_calculator = !empty($show_shipping_calculator);
$calculator_text          = '';
$has_methods              = !empty($available_methods) && is_array($available_methods);
$single                   = $has_methods && 1 === count($available_methods);
?>

<div class="woocommerce-shipping-totals shipping">

	<?php if ($single) : ?>
		<?php // una sola tariffa: è un dato, non una scelta ?>
		<div class="cr-sumrow">
			<span><?= wp_kses_post($package_name); ?></span>
			<span>
				<ul id="shipping_method" class="woocommerce-shipping-methods list-none m-0 p-0">
					<?php foreach ($available_methods as $method) : ?>
						<li>
							<?php
							printf('<input type="hidden" name="shipping_method[%1$d]" data-index="%1$d" id="shipping_method_%1$d_%2$s" value="%3$s" class="shipping_method" />', $index, esc_attr(sanitize_title($method->id)), esc_attr($method->id)); // phpcs:ignore
							echo wp_kses_post(wc_cart_totals_shipping_method_label($method));
							do_action('woocommerce_after_shipping_rate', $method, $index);
							?>
						</li>
					<?php endforeach; ?>
				</ul>
			</span>
		</div>

	<?php elseif ($has_methods) : ?>
		<div class="py-3">
			<?php // come nel pagamento: il nome del gruppo va legato ai radio, o chi usa uno
			// screen reader sceglie la spedizione senza sapere a quale pacco si riferisce. ?>
			<?php // La legend è sr-only e lo <span> visibile resta com'era: un <legend> vero
			// al suo posto porterebbe con sé lo stile di default del browser. ?>
			<fieldset class="cr-fieldset-bare">
			<legend class="sr-only"><?= wp_kses_post($package_name); ?></legend>
			<span class="cr-label"><?= wp_kses_post($package_name); ?></span>
			<ul id="shipping_method" class="woocommerce-shipping-methods cr-optlist">
				<?php foreach ($available_methods as $method) : ?>
					<li>
						<?php
						printf('<input type="radio" name="shipping_method[%1$d]" data-index="%1$d" id="shipping_method_%1$d_%2$s" value="%3$s" class="shipping_method" %4$s />', $index, esc_attr(sanitize_title($method->id)), esc_attr($method->id), checked($method->id, $chosen_method, false)); // phpcs:ignore
						printf('<label for="shipping_method_%1$s_%2$s">%3$s</label>', $index, esc_attr(sanitize_title($method->id)), wc_cart_totals_shipping_method_label($method)); // phpcs:ignore
						do_action('woocommerce_after_shipping_rate', $method, $index);
						?>
					</li>
				<?php endforeach; ?>
			</ul>
			</fieldset>
		</div>

	<?php else : ?>
		<div class="py-3">
			<span class="cr-label"><?= wp_kses_post($package_name); ?></span>
			<p class="text-sm text-th-muted leading-relaxed m-0">
				<?php
				if (!$has_calculated_shipping || !$formatted_destination) {
					if (is_cart() && 'no' === get_option('woocommerce_enable_shipping_calc')) {
						echo wp_kses_post(apply_filters('woocommerce_shipping_not_enabled_on_cart_html', __('Il costo di spedizione si calcola al checkout.', 'cardsrift')));
					} else {
						echo wp_kses_post(apply_filters('woocommerce_shipping_may_be_available_html', __('Indica la destinazione per vedere le spedizioni disponibili.', 'cardsrift')));
					}
				} elseif (!is_cart()) {
					echo wp_kses_post(apply_filters('woocommerce_no_shipping_available_html', __('Non ci sono spedizioni disponibili per questo indirizzo. Controlla i dati oppure scrivici: troviamo una soluzione.', 'cardsrift')));
				} else {
					echo wp_kses_post(apply_filters(
						'woocommerce_cart_no_shipping_available_html',
						sprintf(esc_html__('Nessuna spedizione disponibile per %s.', 'cardsrift') . ' ', '<strong>' . esc_html($formatted_destination) . '</strong>'),
						$formatted_destination
					));
					$calculator_text = esc_html__('Prova un altro indirizzo', 'cardsrift');
				}
				?>
			</p>
		</div>
	<?php endif; ?>

	<?php if ($has_methods && is_cart()) : ?>
		<p class="woocommerce-shipping-destination text-xs text-th-soft leading-relaxed mt-0.5 mb-0">
			<?php
			if ($formatted_destination) {
				printf(esc_html__('Spedizione a %s.', 'cardsrift') . ' ', '<span class="text-th-muted">' . esc_html($formatted_destination) . '</span>'); // phpcs:ignore
				$calculator_text = esc_html__('Cambia indirizzo', 'cardsrift');
			} else {
				echo wp_kses_post(apply_filters('woocommerce_shipping_estimate_html', __('Le opzioni di spedizione si aggiornano al checkout.', 'cardsrift')));
			}
			?>
		</p>
	<?php endif; ?>

	<?php if ($show_package_details) : ?>
		<p class="woocommerce-shipping-contents text-xs text-th-soft mt-1 mb-0"><?= esc_html($package_details); ?></p>
	<?php endif; ?>

	<?php if ($show_shipping_calculator) : ?>
		<div class="mt-2"><?php woocommerce_shipping_calculator($calculator_text); ?></div>
	<?php endif; ?>
</div>
