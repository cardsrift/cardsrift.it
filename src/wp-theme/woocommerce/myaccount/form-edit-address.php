<?php

/**
 * MODIFICA INDIRIZZO — override CardsRift.
 * Stessa griglia a due colonne del checkout: chi ha già compilato l'indirizzo lì
 * ritrova la stessa forma, e non deve reimparare niente.
 *
 * @see woocommerce/templates/myaccount/form-edit-address.php
 * @version 9.8.0
 */

defined('ABSPATH') || exit;

$page_title = ('billing' === $load_address)
	? __('Indirizzo di fatturazione', 'cardsrift')
	: __('Indirizzo di spedizione', 'cardsrift');

do_action('woocommerce_before_edit_account_address_form');

if (!$load_address) {
	wc_get_template('myaccount/my-address.php');
} else { ?>

	<form method="post" novalidate class="cr-form">
		<div class="cr-panel p-5 lg:p-6">
			<h2 class="font-metropolis font-semibold !text-lg mb-5"><?= esc_html(apply_filters('woocommerce_my_account_edit_address_title', $page_title, $load_address)); ?></h2>

			<div class="woocommerce-address-fields">
				<?php do_action("woocommerce_before_edit_address_form_{$load_address}"); ?>

				<div class="woocommerce-address-fields__field-wrapper cr-form__grid">
					<?php foreach ($address as $key => $field) : ?>
						<?php woocommerce_form_field($key, $field, wc_get_post_data_by_key($key, $field['value'])); ?>
					<?php endforeach; ?>
				</div>

				<?php do_action("woocommerce_after_edit_address_form_{$load_address}"); ?>

				<div class="flex flex-wrap items-center gap-4 mt-6">
					<button type="submit" class="cr-btn cr-btn-solid" name="save_address" value="<?php esc_attr_e('Salva indirizzo', 'cardsrift'); ?>"><?php esc_html_e('Salva indirizzo', 'cardsrift'); ?></button>
					<a class="font-metropolis font-semibold text-sm text-th-muted no-underline hover:text-th-acc transition-colors" href="<?= esc_url(wc_get_endpoint_url('edit-address')); ?>"><?php esc_html_e('Annulla', 'cardsrift'); ?></a>
					<?php wp_nonce_field('woocommerce-edit_address', 'woocommerce-edit-address-nonce'); ?>
					<input type="hidden" name="action" value="edit_address" />
				</div>
			</div>
		</div>
	</form>

<?php }

do_action('woocommerce_after_edit_account_address_form');
