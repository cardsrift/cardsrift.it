<!DOCTYPE html>
<html lang="en" class="">

<head>
	<meta charset="UTF-8">
	<title><?= wp_title(''); ?></title>
	<meta name="viewport" content="width=device-width">
	<meta name="SKYPE_TOOLBAR" content="SKYPE_TOOLBAR_PARSER_COMPATIBLE">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="format-detection" content="telephone=no">
	<meta property="fb:app_id" content="822703432306613" />
	<?php wp_head(); ?>
	<meta name="msapplication-TileColor" content="#ffffff">
	<meta name="theme-color" content="#ffffff">
	<link rel="stylesheet" href="https://use.typekit.net/bny3ihn.css">
</head>

<body <?= body_class('relative'); ?>>
	<!-- Scroll	spY  -->
	<?php if (is_front_page()): ?>
		<nav class="fixed top-[50%] right-0 text-right z-50 text-xxs flex flex-col items-end p-3 text-lime pr-6 max-[1750px]:hidden bg-blue bg-opacity-30 rounded-lg mr-4" id="navScrollSpy">
			<?php
			// Recupera il menu chiamato "Homepage Scroll Menu"
			$menu_name = 'homepage_scroll_menu';
			$locations = get_nav_menu_locations();

			if (isset($locations[$menu_name])) {
				$menu_id = $locations[$menu_name];
				$menu_items = wp_get_nav_menu_items($menu_id);

				if ($menu_items) {
					$is_first = true; // Variabile per tracciare il primo elemento
					foreach ($menu_items as $menu_item) {
						// Recupera il colore dal campo ACF
						$item_color = get_field('homepage_item_color', $menu_item);
						// Aggiungi la classe "active" solo al primo elemento
						$class = $is_first ? 'scrollSpyElement active' : 'scrollSpyElement';
						echo '<a href="' . esc_url($menu_item->url) . '" class="' . esc_attr($class) . ' ' . $item_color . '"><span>' . esc_html($menu_item->title) . '</span></a>';
						$is_first = false; // Dopo il primo elemento, imposta su false
					}
				}
			} else {
				echo 'Menu non trovato';
			}
			?>
		</nav>
	<?php endif; ?>
	<header class=" z-[60] fixed top-0 left-0 w-full transition-all h-[var(--header-h-mobile)] lg:h-[var(--header-h-desktop)]">
		<!-- 	MENU TOP -->
		<div class="menu-top max-lg:hidden tw-section bg-blue text-sm uppercase text-white h-[32px] w-full ">
			<div class=" flex justify-end h-full">
				<ul class="flex items-center divide-x divide-blue divide-opacity-20 divide-solid">
					<li class="px-4 hover:opacity-70 transition-all"> <a href="">AREA CANDIDATI</a></li>
					<li class="px-4 hover:opacity-70 transition-all flex">
						<a href="">Search <svg class="icon icon-search transition-all inline-block fill-white ml-2 w-4 h-4">
								<use xlink:href="<?php echo esc_url(get_template_directory_uri()); ?>/assets/images/sprite/sprite.svg#search"></use>
							</svg></a>
					</li>
					<li class="px-4 transition-all mt-[3px]"> <?php echo do_shortcode('[wpml_language_selector_widget]'); ?></li>
				</ul>
			</div>
		</div>

		<!-- MENU BOTTOM -->
		<div class="menu-bottom absolute  h-full lg:h-[80px] bg-white tw-section  mx-auto flex justify-between items-center w-full">
			<!-- LOGO -->
			<a class="logo-home w-auto z-10 flex h-[32px] lg:h-[44px]" href="<?php echo get_home_url(); ?>">
				<picture>
					<source media="(max-width:768px)" srcset="<?php echo get_field('logo', 'option')['sizes']['medium']; ?>" type="">
					<img class="w-full h-full block" src="<?php echo get_field('logo', 'option')['sizes']['medium']; ?>" alt="">
				</picture>
			</a>

			<!-- MENU DESKTOP -->
			<div class="mainMenu max-lg:overflow-y-scroll max-lg:tw-section max-lg:opacity-0 max-lg:invisible  transition-opacity fixed top-0 bottom-0 left-0 right-0 bg-blue lg:block lg:static lg:bg-transparent">

				<?php include('nav-menu.php') ?>
			</div>
			<div class=" wrapperHeaderOpen bg-blue  left-0 right-0 top-0 h-[var(--header-h-mobile)] lg:hidden">

				<!-- White Logo -->
				<a class="whiteLogo z-10 hidden lg:hidden w-auto absolute top-[1.1rem] left-[1.3rem] flex h-[32px] lg:h-[44px]" href="<?php echo get_home_url(); ?>">
				<picture>
					<source media="(max-width:768px)" srcset="<?php echo get_field('logo_white', 'option')['sizes']['medium']; ?>" type="">
					<img src="<?php echo get_field('logo_white', 'option')['sizes']['medium']; ?>" alt="">
				</picture>
				</a>
				<!-- Search Mobile -->
				<button class=" searchButton hidden lg:hidden w-auto absolute top-[1.2rem] right-[5rem] flex ">
					<svg class="icon fill-white w-7 h-7">
						<use xlink:href="<?php echo esc_url(get_template_directory_uri()); ?>/assets/images/sprite/sprite.svg#search"></use>
					</svg>
				</button>
				<!-- HAMBURGER -->
				<div class="hamburgerMenu  h-[var(--header-h-mobile)] flex absolute right-5 top-0  items-center cursor-pointer lg:hidden">
					<div class="space-y-2.5">
						<span class="hamburgerLine block w-8 h-0.5 bg-blue transition-all"></span>
						<span class="hamburgerLine block w-8 h-0.5 bg-blue transition-all"></span>
					</div>
				</div>
			</div>
		</div>
	</header>


	<div class="wrapper pt-[var(--header-h-mobile)] lg:pt-[var(--header-h-desktop)]">
		<div class="base bg-white">