<?php

/**
 * ACCESSO E REGISTRAZIONE — override CardsRift.
 *
 * La registrazione va GIUSTIFICATA, non chiesta: la colonna destra dice cosa si
 * ottiene (seguire l'ordine, indirizzi salvati, il codice di benvenuto) prima di
 * chiedere l'email. Un modulo "Registrati" senza motivo è un ostacolo.
 * Il vantaggio economico è quello già configurato in includes/config.php.
 *
 * ⚠️ Nomi dei campi, classi `login`/`register` e i due nonce sono richiesti dai
 * gestori di WooCommerce.
 *
 * @see woocommerce/templates/myaccount/form-login.php
 * @version 9.8.0
 */

defined('ABSPATH') || exit;

do_action('woocommerce_before_customer_login_form');

$cr_register = 'yes' === get_option('woocommerce_enable_myaccount_registration');
$cr_pct      = defined('CR_WELCOME_PCT') ? CR_WELCOME_PCT : '5%';
?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 lg:gap-6 items-start" id="customer_login">

	<!-- ============ ACCEDI ============ -->
	<div class="u-column1 col-1 cr-panel p-5 lg:p-7">
		<h2 class="font-metropolis font-semibold !text-lg mb-5"><?php esc_html_e('Accedi', 'cardsrift'); ?></h2>

		<form class="woocommerce-form woocommerce-form-login login cr-form" method="post" novalidate>
			<?php do_action('woocommerce_login_form_start'); ?>

			<p class="woocommerce-form-row form-row">
				<label for="username"><?php esc_html_e('Email o nome utente', 'cardsrift'); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e('obbligatorio', 'cardsrift'); ?></span></label>
				<input type="text" class="woocommerce-Input input-text" name="username" id="username" autocomplete="username" value="<?= (!empty($_POST['username'])) ? esc_attr(wp_unslash($_POST['username'])) : ''; // phpcs:ignore ?>" required aria-required="true" />
			</p>

			<p class="woocommerce-form-row form-row">
				<label for="password"><?php esc_html_e('Password', 'cardsrift'); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e('obbligatorio', 'cardsrift'); ?></span></label>
				<input class="woocommerce-Input input-text" type="password" name="password" id="password" autocomplete="current-password" required aria-required="true" />
			</p>

			<?php do_action('woocommerce_login_form'); ?>

			<p class="form-row">
				<label class="cr-check woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme">
					<input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox" id="rememberme" value="forever" />
					<span><?php esc_html_e('Resta collegato', 'cardsrift'); ?></span>
				</label>
				<?php wp_nonce_field('woocommerce-login', 'woocommerce-login-nonce'); ?>
			</p>

			<div class="flex flex-wrap items-center gap-4 mt-5">
				<button type="submit" class="cr-btn cr-btn-solid woocommerce-button woocommerce-form-login__submit" name="login" value="<?php esc_attr_e('Accedi', 'cardsrift'); ?>"><?php esc_html_e('Accedi', 'cardsrift'); ?></button>
				<a class="woocommerce-LostPassword lost_password font-metropolis font-semibold text-sm text-th-muted no-underline hover:text-th-acc transition-colors" href="<?= esc_url(wp_lostpassword_url()); ?>"><?php esc_html_e('Password dimenticata?', 'cardsrift'); ?></a>
			</div>

			<?php do_action('woocommerce_login_form_end'); ?>
		</form>
	</div>

	<?php if ($cr_register) : ?>

		<!-- ============ PRIMA VOLTA QUI ============ -->
		<div class="u-column2 col-2 cr-panel p-5 lg:p-7 bg-th-sur2">
			<div class="cr-eyebrow mb-2"><?php esc_html_e('Prima volta qui?', 'cardsrift'); ?></div>
			<h2 class="font-metropolis font-semibold !text-lg"><?php esc_html_e('Crea un account', 'cardsrift'); ?></h2>

			<ul class="flex flex-col gap-3 my-5 text-sm text-th-muted">
				<?php
				$cr_reasons = [
					__('Segui l’ordine passo passo, dalla preparazione al tracciamento.', 'cardsrift'),
					__('Indirizzi salvati: il prossimo ordine si chiude in due tocchi.', 'cardsrift'),
					sprintf(__('Un codice −%s di benvenuto, subito via email.', 'cardsrift'), $cr_pct),
				];
				foreach ($cr_reasons as $cr_r) : ?>
					<li class="flex items-start gap-2.5 leading-relaxed">
						<svg class="w-4 h-4 mt-0.5 stroke-th-acc fill-transparent shrink-0" viewBox="0 0 24 24" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5" /></svg>
						<?= esc_html($cr_r); ?>
					</li>
				<?php endforeach; ?>
			</ul>

			<form method="post" class="woocommerce-form woocommerce-form-register register cr-form" <?php do_action('woocommerce_register_form_tag'); ?>>
				<?php do_action('woocommerce_register_form_start'); ?>

				<?php if ('no' === get_option('woocommerce_registration_generate_username')) : ?>
					<p class="woocommerce-form-row form-row">
						<label for="reg_username"><?php esc_html_e('Nome utente', 'cardsrift'); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e('obbligatorio', 'cardsrift'); ?></span></label>
						<input type="text" class="woocommerce-Input input-text" name="username" id="reg_username" autocomplete="username" value="<?= (!empty($_POST['username'])) ? esc_attr(wp_unslash($_POST['username'])) : ''; // phpcs:ignore ?>" required aria-required="true" />
					</p>
				<?php endif; ?>

				<p class="woocommerce-form-row form-row">
					<label for="reg_email"><?php esc_html_e('Email', 'cardsrift'); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e('obbligatorio', 'cardsrift'); ?></span></label>
					<input type="email" class="woocommerce-Input input-text" name="email" id="reg_email" autocomplete="email" value="<?= (!empty($_POST['email'])) ? esc_attr(wp_unslash($_POST['email'])) : ''; // phpcs:ignore ?>" required aria-required="true" />
				</p>

				<?php if ('no' === get_option('woocommerce_registration_generate_password')) : ?>
					<p class="woocommerce-form-row form-row">
						<label for="reg_password"><?php esc_html_e('Password', 'cardsrift'); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e('obbligatorio', 'cardsrift'); ?></span></label>
						<input type="password" class="woocommerce-Input input-text" name="password" id="reg_password" autocomplete="new-password" required aria-required="true" />
					</p>
				<?php else : ?>
					<p class="text-sm text-th-muted leading-relaxed"><?php esc_html_e('Ti mandiamo per email un link per impostare la password.', 'cardsrift'); ?></p>
				<?php endif; ?>

				<?php do_action('woocommerce_register_form'); ?>

				<p class="woocommerce-form-row form-row !mt-5">
					<?php wp_nonce_field('woocommerce-register', 'woocommerce-register-nonce'); ?>
					<button type="submit" class="cr-btn cr-btn-solid w-full justify-center woocommerce-Button woocommerce-button woocommerce-form-register__submit" name="register" value="<?php esc_attr_e('Crea l’account', 'cardsrift'); ?>"><?php esc_html_e('Crea l’account', 'cardsrift'); ?></button>
				</p>

				<?php do_action('woocommerce_register_form_end'); ?>
			</form>
		</div>

	<?php endif; ?>
</div>

<?php do_action('woocommerce_after_customer_login_form'); ?>
