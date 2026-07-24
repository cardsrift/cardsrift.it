<?php

/**
 * DATI DELL'ACCOUNT — override CardsRift.
 * Due blocchi separati: chi sei (nome, email) e la password. Tenerli distinti
 * evita l'errore classico di riempire i campi password senza volerli cambiare.
 *
 * ⚠️ Nomi dei campi, classe `woocommerce-EditAccountForm` e nonce sono richiesti
 * dal gestore di WooCommerce: cambiano il salvataggio, non l'aspetto.
 *
 * @see woocommerce/templates/myaccount/form-edit-account.php
 * @version 9.8.0
 */

defined('ABSPATH') || exit;

do_action('woocommerce_before_edit_account_form');
?>

<form class="woocommerce-EditAccountForm edit-account cr-form" action="" method="post" <?php do_action('woocommerce_edit_account_form_tag'); ?>>
	<?php do_action('woocommerce_edit_account_form_start'); ?>

	<div class="cr-panel p-5 lg:p-6">
		<h2 class="font-metropolis font-semibold !text-lg mb-5"><?php esc_html_e('Chi sei', 'cardsrift'); ?></h2>

		<div class="cr-form__grid">
			<p class="woocommerce-form-row form-row">
				<label for="account_first_name"><?php esc_html_e('Nome', 'cardsrift'); ?>&nbsp;<span class="required" aria-hidden="true">*</span></label>
				<input type="text" class="woocommerce-Input input-text" name="account_first_name" id="account_first_name" autocomplete="given-name" value="<?= esc_attr($user->first_name); ?>" aria-required="true" />
			</p>
			<p class="woocommerce-form-row form-row">
				<label for="account_last_name"><?php esc_html_e('Cognome', 'cardsrift'); ?>&nbsp;<span class="required" aria-hidden="true">*</span></label>
				<input type="text" class="woocommerce-Input input-text" name="account_last_name" id="account_last_name" autocomplete="family-name" value="<?= esc_attr($user->last_name); ?>" aria-required="true" />
			</p>

			<p class="woocommerce-form-row form-row form-row-wide">
				<label for="account_display_name"><?php esc_html_e('Come vuoi essere chiamato', 'cardsrift'); ?>&nbsp;<span class="required" aria-hidden="true">*</span></label>
				<input type="text" class="woocommerce-Input input-text" name="account_display_name" id="account_display_name" aria-describedby="account_display_name_description" value="<?= esc_attr($user->display_name); ?>" aria-required="true" />
				<span id="account_display_name_description" class="description"><?php esc_html_e('È il nome che vedi qui dentro e con cui ti scriviamo.', 'cardsrift'); ?></span>
			</p>

			<p class="woocommerce-form-row form-row form-row-wide">
				<label for="account_email"><?php esc_html_e('Email', 'cardsrift'); ?>&nbsp;<span class="required" aria-hidden="true">*</span></label>
				<input type="email" class="woocommerce-Input input-text" name="account_email" id="account_email" autocomplete="email" value="<?= esc_attr($user->user_email); ?>" aria-required="true" />
				<span class="description"><?php esc_html_e('Qui arrivano conferme d’ordine e tracciamenti.', 'cardsrift'); ?></span>
			</p>
		</div>

		<?php do_action('woocommerce_edit_account_form_fields'); ?>
	</div>

	<div class="cr-panel p-5 lg:p-6 mt-4">
		<h2 class="font-metropolis font-semibold !text-lg"><?php esc_html_e('Password', 'cardsrift'); ?></h2>
		<p class="text-sm text-th-muted mt-1.5 mb-5"><?php esc_html_e('Lascia tutto vuoto se non la vuoi cambiare.', 'cardsrift'); ?></p>

		<div class="cr-form__grid">
			<p class="woocommerce-form-row form-row form-row-wide">
				<label for="password_current"><?php esc_html_e('Password attuale', 'cardsrift'); ?></label>
				<input type="password" class="woocommerce-Input input-text" name="password_current" id="password_current" autocomplete="off" />
			</p>
			<p class="woocommerce-form-row form-row">
				<label for="password_1"><?php esc_html_e('Nuova password', 'cardsrift'); ?></label>
				<input type="password" class="woocommerce-Input input-text" name="password_1" id="password_1" autocomplete="off" />
			</p>
			<p class="woocommerce-form-row form-row">
				<label for="password_2"><?php esc_html_e('Ripeti la nuova password', 'cardsrift'); ?></label>
				<input type="password" class="woocommerce-Input input-text" name="password_2" id="password_2" autocomplete="off" />
			</p>
		</div>
	</div>

	<?php do_action('woocommerce_edit_account_form'); ?>

	<div class="mt-6">
		<?php wp_nonce_field('save_account_details', 'save-account-details-nonce'); ?>
		<button type="submit" class="cr-btn cr-btn-solid" name="save_account_details" value="<?php esc_attr_e('Salva le modifiche', 'cardsrift'); ?>"><?php esc_html_e('Salva le modifiche', 'cardsrift'); ?></button>
		<input type="hidden" name="action" value="save_account_details" />
	</div>

	<?php do_action('woocommerce_edit_account_form_end'); ?>
</form>

<?php do_action('woocommerce_after_edit_account_form'); ?>
