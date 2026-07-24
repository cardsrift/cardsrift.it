<?php

/**
 * NAVIGAZIONE ACCOUNT — override CardsRift.
 * Colonna verticale su desktop, riga di chip scorrevole su mobile. Ogni voce ha
 * la sua icona (stesso tratto 1.8 delle icone dell'header): in una lista corta
 * l'icona è ciò che si riconosce prima della parola.
 * L'uscita resta in fondo, staccata: è l'unica azione che chiude, non apre.
 *
 * @see woocommerce/templates/myaccount/navigation.php
 * @version 9.8.0
 */

defined('ABSPATH') || exit;

do_action('woocommerce_before_account_navigation');
?>

<nav class="woocommerce-MyAccount-navigation cr-accnav lg:sticky lg:top-[calc(var(--header-h-desktop)+1.5rem)]" aria-label="<?php esc_attr_e('Sezioni dell’account', 'cardsrift'); ?>">
	<ul>
		<?php foreach (wc_get_account_menu_items() as $endpoint => $label) : ?>
			<?php // l'uscita è l'unica voce che chiude invece di aprire: staccata dalle altre ?>
			<li class="<?= esc_attr(wc_get_account_menu_item_classes($endpoint)); ?><?= $endpoint === 'customer-logout' ? ' lg:mt-2 lg:pt-2 lg:border-t lg:border-th-line' : ''; ?>">
				<a href="<?= esc_url(wc_get_account_endpoint_url($endpoint)); ?>" <?= wc_is_current_account_menu_item($endpoint) ? 'aria-current="page"' : ''; ?>>
					<?= function_exists('cr_account_nav_icon') ? cr_account_nav_icon($endpoint) : ''; // phpcs:ignore ?>
					<?= esc_html($label); ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>

<?php do_action('woocommerce_after_account_navigation'); ?>
