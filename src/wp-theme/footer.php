   <?php
	//base
	?>
   </div>

   <?php include('global-components/footer/template.php'); ?>

   <?php
	//wrapper
	?>
   </div>

   <?php // recap del carrello che entra da destra all'aggiunta (includes/shop.php) ?>
   <?php if (function_exists('cr_cart_drawer')) cr_cart_drawer(); ?>

   <?php // iubenda: il banner sta in cima al <head> (header.php) e iubenda.js entra
	// dalla coda degli script — vedi includes/iubenda.php. Qui non serve nulla. ?>
   <?php wp_footer(); ?>

   </body>

   </html>