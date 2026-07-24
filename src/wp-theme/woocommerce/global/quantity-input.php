<?php

/**
 * Campo quantità — override CardsRift.
 * Solo l'<input>: il "vestito" (i tasti − / +) lo mette chi lo usa, avvolgendolo
 * in .cr-qty. Quando minimo e massimo coincidono (pezzo unico, articolo venduto
 * singolarmente) la quantità non è una scelta: campo nascosto e basta.
 *
 * @see woocommerce/templates/global/quantity-input.php
 * @version 9.8.0
 */

defined('ABSPATH') || exit;

$label = !empty($args['product_name'])
	? sprintf(esc_html__('Quantità di %s', 'cardsrift'), wp_strip_all_tags($args['product_name']))
	: esc_html__('Quantità', 'cardsrift');

do_action('woocommerce_before_quantity_input_field');

if ($max_value && $min_value === $max_value) : ?>
	<input type="hidden" id="<?= esc_attr($input_id); ?>" name="<?= esc_attr($input_name); ?>" value="<?= esc_attr($input_value); ?>" />
<?php else : ?>
	<label class="screen-reader-text" for="<?= esc_attr($input_id); ?>"><?= esc_attr($label); ?></label>
	<input
		type="<?= esc_attr($type); ?>"
		<?= $readonly ? 'readonly="readonly"' : ''; ?>
		id="<?= esc_attr($input_id); ?>"
		class="<?= esc_attr(join(' ', (array) $classes)); ?>"
		name="<?= esc_attr($input_name); ?>"
		value="<?= esc_attr($input_value); ?>"
		aria-label="<?php esc_attr_e('Quantità', 'cardsrift'); ?>"
		min="<?= esc_attr($min_value); ?>"
		max="<?= esc_attr(0 < $max_value ? $max_value : ''); ?>"
		<?php if (!$readonly) : ?>
			step="<?= esc_attr($step); ?>"
			inputmode="numeric"
			autocomplete="off"
		<?php endif; ?>
	/>
<?php endif;

do_action('woocommerce_after_quantity_input_field');
