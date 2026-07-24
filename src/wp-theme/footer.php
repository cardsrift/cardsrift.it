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

    <!-- IUBENDA -->
    <script type="text/javascript" src="//embeds.iubenda.com/widgets/ad10d836-343c-4fc1-933e-5b1048ccce4a.js"></script>
   <?php wp_footer(); ?>

   </body>

   </html>