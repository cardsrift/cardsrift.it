<?php

/**
 * Template Name: My account
 *
 * Cornice dell'area account: la sezione, il container e il titolo che cambia con
 * l'endpoint (dashboard, ordini, indirizzi, dati). Dentro ci sta lo shortcode di
 * WooCommerce, che a seconda dello stato mostra il login (myaccount/form-login.php)
 * oppure navigazione + contenuto (myaccount/my-account.php).
 *
 * Il chrome sta QUI e non negli override perché deve avvolgere entrambi i casi.
 */

defined('ABSPATH') || exit;

get_header();

$cr_logged = is_user_logged_in();
?>

<main>
	<section class="cr-sec" data-th="light">
		<div class="tw-container tw-section">
			<div class="py-8 lg:py-12">

				<header class="mb-7 lg:mb-9">
					<div class="cr-eyebrow mb-2.5">
						<?= $cr_logged ? esc_html__('Area riservata', 'cardsrift') : esc_html__('Il tuo portale', 'cardsrift'); ?>
					</div>
					<h1 class="font-metropolis font-bold !text-2xl lg:!text-4xl !leading-tight">
						<?= esc_html(function_exists('cr_account_title') ? cr_account_title() : __('Il tuo account', 'cardsrift')); ?>
					</h1>
				</header>

				<?= do_shortcode('[woocommerce_my_account]'); ?>

			</div>
		</div>
	</section>

	<?php if (function_exists('cr_shop_trust_band')) cr_shop_trust_band(); ?>
</main>

<?php get_footer(); ?>
