<!DOCTYPE html>
<html lang="en" class="">

<head>
	<meta charset="UTF-8">
	<title><?= wp_title(''); ?></title>
	<meta name="viewport" content="width=device-width">
	<meta name="SKYPE_TOOLBAR" content="SKYPE_TOOLBAR_PARSER_COMPATIBLE">
	<meta name="mobile-web-app-capable" content="yes">
	<meta name="format-detection" content="telephone=no">
	<meta property="fb:app_id" content="822703432306613" />
	<?php wp_head(); ?>
	<meta name="msapplication-TileColor" content="#ffffff">
	<meta name="theme-color" content="#ffffff">
	<link rel="stylesheet" href="https://use.typekit.net/bny3ihn.css">
	<script src="https://player.vimeo.com/api/player.js"></script>
</head>


<body <?= body_class();?>>

	<header class="z-50 flex bg-yellow-light py-3 lg:p-5 fixed top-0 left-0 w-full transition-all h-[var(--header-h-mobile)] lg:h-[var(--header-h-desktop)] z-">
		<div class="tw-container  relative mx-auto flex justify-between ">
			<!-- LOGO -->
			<a class=" w-auto flex h-[30px] lg:h-auto" href="<?php echo get_home_url(); ?>">
				<img src="<?php echo get_field('logo', 'option')['sizes']['medium']; ?>" alt="">
			</a>

			<!-- MENU DESKTOP -->
			<div class="mainMenu hidden fixed top-0 bottom-0 left-0 right-0 bg-blue-dark lg:block lg:static lg:bg-transparent">
				<?php include('nav-menu.php') ?>
			</div>

			<!-- HAMBURGER -->
			<div class="hamburgerMenu flex absolute right-5 top-1/2 transform -translate-y-1/2 items-center cursor-pointer lg:hidden">
				<div class="space-y-2">
					<span class="hamburgerLine block w-8 h-0.5 bg-white transition-all"></span>
					<span class="hamburgerLine block w-8 h-0.5 bg-white transition-all"></span>
					<span class="hamburgerLine block w-8 h-0.5 bg-white transition-all"></span>
				</div>
			</div>
		</div>
	</header>


	<div class="wrapper pt-[var(--header-h-mobile)] lg:pt-[var(--header-h-desktop)]">
		<div class="base">