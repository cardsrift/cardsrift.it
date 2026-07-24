<!DOCTYPE html>
<html <?php language_attributes(); ?> class="scroll-smooth scroll-pt-[var(--header-h-mobile)] lg:scroll-pt-[var(--header-h-desktop)]">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="SKYPE_TOOLBAR" content="SKYPE_TOOLBAR_PARSER_COMPATIBLE">
	<meta name="mobile-web-app-capable" content="yes">
	<meta name="format-detection" content="telephone=no">
	<?php wp_head(); ?>
	<meta name="msapplication-TileColor" content="#ffffff">
	<meta name="theme-color" content="#ffffff">
	
	<!-- adelle font -->
	<link rel="stylesheet" href="https://use.typekit.net/lma6wbr.css"> 

	
</head>


<body <?= body_class();?>>

	<?php
	// Header intercambiabile dark/light: guidato da CR_HEADER_THEME + token del design system.
	$cr_ht       = defined('CR_HEADER_THEME') ? CR_HEADER_THEME : 'light';
	$cr_logo     = ($cr_ht === 'light') ? get_field('logo_black', 'option') : get_field('logo', 'option');
	$cr_logo_url = is_array($cr_logo) ? ($cr_logo['url'] ?? '') : (string) $cr_logo;
	$cr_menu     = function_exists('sort_wp_nav') ? sort_wp_nav('header') : [];
	$cr_shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/');
	$cr_acct_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/');
	$cr_cart_url = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/');
	// Checkout: header ridotto a logo + rassicurazione. La navigazione, lì, è solo una via
	// d'uscita dall'ordine (vedi cr_is_focused_checkout() in includes/shop.php).
	$cr_focus    = function_exists('cr_is_focused_checkout') && cr_is_focused_checkout();
	?>
	<header data-th="<?= esc_attr($cr_ht); ?>" class="cr-header fixed top-0 left-0 w-full z-50 h-[var(--header-h-mobile)] lg:h-[var(--header-h-desktop)] bg-th-pg border-b border-th-line transition-[top] duration-300">
		<div class="tw-container h-full flex items-center justify-between gap-4">

			<!-- LOGO -->
			<a class="flex items-center shrink-0 w-[135px] lg:w-[175px] relative z-50" href="<?= esc_url(home_url('/')); ?>" aria-label="CardsRift">
				<?php // su header dark il campo ACF `logo` (colored) ha il wordmark scuro: forzalo bianco (design-system §6). Su light resta invariato. ?>
				<?php if ($cr_logo_url) : ?><img class="w-full h-auto<?= $cr_ht === 'dark' ? ' brightness-0 invert' : ''; ?>" src="<?= esc_url($cr_logo_url); ?>" alt="CardsRift"><?php endif; ?>
			</a>

			<?php if ($cr_focus) : ?>

				<!-- CHECKOUT: nessuna navigazione, solo la rassicurazione che serve lì -->
				<span class="flex items-center gap-2 text-th-muted text-xs font-metropolis font-semibold ml-auto">
					<svg class="w-4 h-4 stroke-th-acc fill-transparent shrink-0" viewBox="0 0 24 24" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7 3v6c0 4.5-3 7.7-7 9-4-1.3-7-4.5-7-9V6l7-3z" /></svg>
					<span class="max-sm:hidden"><?= esc_html__('Pagamento sicuro · connessione cifrata', 'cardsrift'); ?></span>
					<span class="sm:hidden"><?= esc_html__('Pagamento sicuro', 'cardsrift'); ?></span>
				</span>

			<?php else : ?>

			<!-- NAV = GAME-SWITCHER (i 3 giochi + Accessori globale) · gioco attivo evidenziato -->
			<?php
			$cr_games       = defined('CR_GAMES') ? CR_GAMES : ['magic', 'pokemon'];
			$cr_active_game = function_exists('cr_current_game') ? cr_current_game() : '';
			$cr_on_access   = (get_query_var('cr_tipo') === 'accessori');
			?>
			<nav class="mainMenu flex items-center gap-6 font-metropolis font-medium text-sm lg:flex-1 lg:justify-center max-lg:fixed max-lg:top-[var(--header-h-mobile)] max-lg:left-0 max-lg:right-0 max-lg:bottom-0 max-lg:z-40 max-lg:flex-col max-lg:justify-center max-lg:gap-7 max-lg:text-lg max-lg:bg-th-pg">
				<?php foreach ($cr_games as $cr_g) : $cr_is = ($cr_g === $cr_active_game); ?>
					<a class="whitespace-nowrap transition-colors <?= $cr_is ? 'text-th-acc font-semibold' : 'text-th-ink hover:text-th-acc'; ?>" href="<?= esc_url(home_url('/' . $cr_g . '/')); ?>"><?= esc_html(function_exists('cr_game_label') ? cr_game_label($cr_g) : $cr_g); ?></a>
				<?php endforeach; ?>
				<span class="hidden lg:block w-px h-4 bg-th-lines shrink-0"></span>
				<a class="whitespace-nowrap transition-colors <?= $cr_on_access ? 'text-th-acc font-semibold' : 'text-th-ink hover:text-th-acc'; ?>" href="<?= esc_url(home_url('/accessori/')); ?>"><?= esc_html__('Accessori', 'cardsrift'); ?></a>
			</nav>

			<!-- ICONE -->
			<div class="flex items-center gap-0.5 lg:gap-1.5 shrink-0 relative z-50">
				<a class="w-10 h-10 grid place-items-center rounded-full text-th-ink hover:text-th-acc hover:bg-th-accsoft transition-colors" href="<?= esc_url(home_url('/')); ?>" aria-label="<?= esc_attr__('Cerca', 'cardsrift'); ?>">
					<svg class="w-5 h-5 stroke-current fill-transparent" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M16.5 16.5L21 21"/></svg>
				</a>
				<a class="w-10 h-10 grid place-items-center rounded-full text-th-ink hover:text-th-acc hover:bg-th-accsoft transition-colors max-sm:hidden" href="<?= esc_url($cr_acct_url); ?>" aria-label="<?= esc_attr__('Account', 'cardsrift'); ?>">
					<svg class="w-5 h-5 stroke-current fill-transparent" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg>
				</a>
				<?php // resta un link vero: senza JavaScript porta al carrello, con JavaScript apre il drawer ?>
				<a class="w-10 h-10 grid place-items-center rounded-full text-th-ink hover:text-th-acc hover:bg-th-accsoft transition-colors relative" href="<?= esc_url($cr_cart_url); ?>" data-cr-cart-toggle aria-label="<?= esc_attr__('Carrello', 'cardsrift'); ?>">
					<svg class="w-5 h-5 stroke-current fill-transparent" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 4h2l2.4 12h11.2l2.4-9H7"/><circle cx="9" cy="20" r="1.4"/><circle cx="17" cy="20" r="1.4"/></svg>
					<?= function_exists('cr_cart_badge') ? cr_cart_badge() : ''; ?>
				</a>

				<!-- HAMBURGER (mobile) -->
				<button type="button" class="hamburgerMenu lg:hidden w-10 h-10 grid place-items-center ml-0.5" aria-label="<?= esc_attr__('Menu', 'cardsrift'); ?>">
					<span class="flex flex-col gap-1.5">
						<span class="hamburgerLine block w-6 h-0.5 bg-th-ink transition-all"></span>
						<span class="hamburgerLine block w-6 h-0.5 bg-th-ink transition-all"></span>
						<span class="hamburgerLine block w-6 h-0.5 bg-th-ink transition-all"></span>
					</span>
				</button>
			</div>

			<?php endif; ?>

		</div>
	</header>


	<div class="wrapper pt-[var(--header-h-mobile)] lg:pt-[var(--header-h-desktop)]">
		<?php if (function_exists('cr_render_game_subnav')) cr_render_game_subnav(); ?>
			<div class="base <?= function_exists('cr_is_builder_page') && cr_is_builder_page() ? '!max-w-none !px-0' : 'container'; ?>">