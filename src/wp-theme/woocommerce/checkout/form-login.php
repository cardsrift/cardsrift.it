<?php

/**
 * ACCESSO dal checkout — override CardsRift.
 * Qui resta SOLO il modulo (nascosto): l'invito "Hai già un account? Accedi"
 * vive accanto ai contatti, dove serve davvero (vedi checkout/form-billing.php).
 * Il link ha classe `showlogin`: checkout.js lo intercetta a livello di documento,
 * quindi può stare ovunque nella pagina.
 *
 * @see woocommerce/templates/checkout/form-login.php
 * @version 9.8.0
 */

defined('ABSPATH') || exit;

if (is_user_logged_in() || 'no' === get_option('woocommerce_enable_checkout_login_reminder')) {
	return;
}

woocommerce_login_form([
	'message'  => __('Se hai già ordinato da noi, accedi: ritrovi indirizzi e storico senza riscrivere niente.', 'cardsrift'),
	'redirect' => wc_get_checkout_url(),
	'hidden'   => true,
]);
