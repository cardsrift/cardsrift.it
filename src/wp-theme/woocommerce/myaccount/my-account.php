<?php

/**
 * AREA ACCOUNT — override CardsRift.
 * Due colonne: navigazione a sinistra (su mobile diventa una riga di chip
 * scorrevole), contenuto a destra. La cornice della pagina — sezione, container
 * e titolo — sta nel template my-account.php del tema, perché deve avvolgere
 * anche il modulo di accesso, che passa da un altro template.
 *
 * @see woocommerce/templates/myaccount/my-account.php
 * @version 9.8.0
 */

defined('ABSPATH') || exit;
?>

<div class="grid grid-cols-1 lg:grid-cols-[220px_minmax(0,1fr)] gap-6 lg:gap-10 items-start">

	<?php do_action('woocommerce_account_navigation'); ?>

	<div class="woocommerce-MyAccount-content min-w-0">
		<?php do_action('woocommerce_account_content'); ?>
	</div>
</div>
