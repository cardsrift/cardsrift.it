<!DOCTYPE html>
<html <?php language_attributes(); ?> class="scroll-smooth scroll-pt-[var(--header-h-mobile)] lg:scroll-pt-[var(--header-h-desktop)]">

<head>
	<meta charset="UTF-8">
	<?php // viewport-fit=cover: senza, env(safe-area-inset-*) vale 0 e la barra sticky del
	// carrello / il fondo del drawer finiscono sotto la home-indicator degli iPhone. ?>
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">

	<?php // BANNER COOKIE (iubenda) — il primo script della pagina, e sincrono di proposito:
	// da qui in giù iubenda può fermare ciò che installa tracciatori prima che parta.
	// Più in basso (o con async) bloccherebbe cose già avvenute. Sta sotto ai due meta
	// perché sono tag, non script: charset e viewport devono restare in testa o il
	// browser rischia di disegnare la prima riga con la larghezza sbagliata.
	// Configurazione (testi, colori, servizi) nel pannello iubenda — vedi includes/iubenda.php. ?>
	<link rel="preconnect" href="https://cdn.iubenda.com" crossorigin>
	<?php if (function_exists('cr_iubenda_banner')) cr_iubenda_banner(); ?>

	<meta name="SKYPE_TOOLBAR" content="SKYPE_TOOLBAR_PARSER_COMPATIBLE">
	<meta name="mobile-web-app-capable" content="yes">
	<meta name="format-detection" content="telephone=no">
	<?php // Il testo di corpo dipende da un foglio di stile su un altro dominio: senza
	// preconnect il browser scopre l'host solo quando incontra il <link>, e paga
	// DNS + TLS prima di poter disegnare una riga di testo. ?>
	<link rel="preconnect" href="https://use.typekit.net" crossorigin>
	<link rel="preconnect" href="https://p.typekit.net" crossorigin>
	<?php // Metropolis Bold veste ogni h1/h2 del sito (le utility .tw-h*): precaricarlo
	// evita che il titolo principale arrivi dopo il resto della pagina. ?>
	<link rel="preload" as="font" type="font/woff2" href="<?= esc_url(get_template_directory_uri() . '/assets/fonts/Metropolis-Bold.woff2'); ?>" crossorigin>
	<?php wp_head(); ?>
	<meta name="msapplication-TileColor" content="#ffffff">
	<meta name="theme-color" content="#ffffff">

	<!-- adelle font -->
	<link rel="stylesheet" href="https://use.typekit.net/lma6wbr.css">


</head>


<body <?= body_class();?>>

	<?php // SALTO AL CONTENUTO — primo elemento tabbabile della pagina. Invisibile finché
	// non lo si raggiunge col tasto Tab: senza, chi naviga da tastiera deve attraversare
	// due righe di header e tutto il menu a ogni cambio di pagina.
	// data-th="dark" sull'ancora stessa: fuori da una sezione i token th-* non avrebbero
	// un valore e il riquadro uscirebbe trasparente. ?>
	<a href="#main-content" data-th="dark" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[1000] focus:px-4 focus:py-3 focus:rounded-xl focus:no-underline focus:font-metropolis focus:font-semibold focus:bg-th-pg focus:text-th-ink focus:shadow-th focus:outline focus:outline-2 focus:outline-offset-2 focus:outline-th-acc"><?= esc_html__('Salta al contenuto', 'cardsrift'); ?></a>

	<?php
	// HEADER A DUE RIGHE (direzione B):
	//   riga 1 — servizio: promessa di spedizione, reputazione Cardmarket, pagine di
	//            assistenza e account. Solo desktop: su telefono scende nel menu.
	//   riga 2 — negozio: logo, giochi con le loro due porte, accessori, buylist, ricerca, carrello.
	// Il menu mobile è SEMPRE dark (data-th="dark") anche quando la barra è chiara:
	// si legge come uno strato sopra la pagina.
	// Le altezze vivono in --header-h-mobile / --header-h-desktop (tailwind/components/layout.css):
	// desktop 110px = 34 (servizio) + 76 (negozio). Chi si aggancia all'header usa quelle variabili.
	$cr_ht       = defined('CR_HEADER_THEME') ? CR_HEADER_THEME : 'light';
	$cr_logo     = ($cr_ht === 'light') ? get_field('logo_black', 'option') : get_field('logo', 'option');
	$cr_logo_url = is_array($cr_logo) ? ($cr_logo['url'] ?? '') : (string) $cr_logo;
	$cr_acct_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/');
	$cr_cart_url = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/');
	// Checkout: header ridotto a logo + rassicurazione. La navigazione, lì, è solo una via
	// d'uscita dall'ordine (vedi cr_is_focused_checkout() in includes/shop.php).
	$cr_focus    = function_exists('cr_is_focused_checkout') && cr_is_focused_checkout();

	$cr_games       = function_exists('cr_nav_games') ? cr_nav_games() : [];
	$cr_info        = function_exists('cr_nav_info') ? cr_nav_info() : [];
	$cr_info_full   = function_exists('cr_nav_info') ? cr_nav_info(true) : [];
	$cr_active_game = function_exists('cr_current_game') ? cr_current_game() : '';
	$cr_active_tipo = function_exists('cr_current_tipo') ? cr_current_tipo() : '';
	$cr_on_access   = (get_query_var('cr_tipo') === 'accessori');
	// Ricerca già ristretta al gioco in cui ti trovi: i giochi non si mescolano nemmeno qui.
	$cr_search_g    = $cr_active_game ?: (function_exists('cr_search_game') ? cr_search_game() : '');
	$cr_search_ph   = $cr_search_g && function_exists('cr_game_label_short')
		? sprintf(__('Cerca in %s…', 'cardsrift'), cr_game_label_short($cr_search_g))
		: __('Cerca una carta o un set…', 'cardsrift');
	?>
	<header data-th="<?= esc_attr($cr_ht); ?>" class="cr-header fixed top-0 left-0 w-full z-50 bg-th-pg border-b border-th-line transition-transform duration-300 will-change-transform">

		<?php if ($cr_focus) : ?>

			<!-- CHECKOUT: nessuna navigazione, solo la rassicurazione che serve lì -->
			<div class="tw-container tw-section h-[var(--header-h-mobile)] lg:h-[var(--header-h-desktop)] flex items-center justify-between gap-4">
				<a class="flex items-center shrink-0 w-[135px] lg:w-[175px] py-3" href="<?= esc_url(home_url('/')); ?>" aria-label="CardsRift">
					<?php if ($cr_logo_url) : ?><img class="w-full h-auto<?= $cr_ht === 'dark' ? ' brightness-0 invert' : ''; ?>" src="<?= esc_url($cr_logo_url); ?>" alt="CardsRift"><?php endif; ?>
				</a>
				<span class="flex items-center gap-2 text-th-muted text-xs font-metropolis font-semibold">
					<svg class="w-4 h-4 stroke-th-acc fill-transparent shrink-0" viewBox="0 0 24 24" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7 3v6c0 4.5-3 7.7-7 9-4-1.3-7-4.5-7-9V6l7-3z" /></svg>
					<span class="max-sm:hidden"><?= esc_html__('Pagamento sicuro · connessione cifrata', 'cardsrift'); ?></span>
					<span class="sm:hidden"><?= esc_html__('Pagamento sicuro', 'cardsrift'); ?></span>
				</span>
			</div>

		<?php else : ?>

			<!-- ============ RIGA 1 · SERVIZIO (solo desktop) ============ -->
			<div class="max-lg:hidden bg-th-sur2 border-b border-th-line">
				<div class="tw-container tw-section h-[34px] flex items-center gap-5 font-metropolis text-xs text-th-muted">
					<span class="max-xl:hidden"><?= esc_html__('Spedizione tracciata in 24/48h', 'cardsrift'); ?></span>
					<?php if (defined('CR_CM_URL') && CR_CM_URL) : ?>
						<a class="text-th-acc font-semibold no-underline hover:underline whitespace-nowrap" href="<?= esc_url(CR_CM_URL); ?>" target="_blank" rel="noopener">
							<?php printf(esc_html__('★ %s positive su Cardmarket', 'cardsrift'), esc_html(defined('CR_CM_POSITIVE') ? CR_CM_POSITIVE : '100%')); ?>
						</a>
					<?php endif; ?>
					<nav class="ml-auto flex items-center gap-4" aria-label="<?= esc_attr__('Assistenza', 'cardsrift'); ?>">
						<?php foreach ($cr_info as $cr_l) : ?>
							<a class="no-underline hover:text-th-acc transition-colors whitespace-nowrap" href="<?= esc_url($cr_l['url']); ?>"><?= esc_html($cr_l['label']); ?></a>
						<?php endforeach; ?>
						<span class="w-px h-3.5 bg-th-lines" aria-hidden="true"></span>
						<a class="font-semibold text-th-ink no-underline hover:text-th-acc transition-colors whitespace-nowrap" href="<?= esc_url($cr_acct_url); ?>"><?= esc_html__('Il mio account', 'cardsrift'); ?></a>
					</nav>
				</div>
			</div>

			<!-- ============ RIGA 2 · NEGOZIO ============ -->
			<div class="tw-container tw-section h-[var(--header-h-mobile)] lg:h-[76px] flex items-center gap-4">

				<!-- LOGO -->
				<a class="flex items-center shrink-0 w-[135px] lg:w-[175px] py-3" href="<?= esc_url(home_url('/')); ?>" aria-label="CardsRift">
					<?php // su header dark il campo ACF `logo` (colored) ha il wordmark scuro: forzalo bianco (design-system §6). Su light resta invariato. ?>
					<?php if ($cr_logo_url) : ?><img class="w-full h-auto<?= $cr_ht === 'dark' ? ' brightness-0 invert' : ''; ?>" src="<?= esc_url($cr_logo_url); ?>" alt="CardsRift"><?php endif; ?>
				</a>

				<!-- CATALOGO (desktop) — un gioco per voce, con le sue due porte.
					 La tendina si apre in CSS: hover sul contenitore o focus da tastiera, niente JS. -->
				<nav class="hidden lg:flex items-center gap-1 ml-4 font-metropolis text-sm font-medium" aria-label="<?= esc_attr__('Catalogo', 'cardsrift'); ?>">
					<?php foreach ($cr_games as $cr_g) : $cr_is = ($cr_g['slug'] === $cr_active_game); ?>
						<div class="relative group">
							<a class="flex items-center gap-1.5 px-3 py-2.5 rounded-[10px] whitespace-nowrap no-underline transition-colors group-hover:bg-th-accsoft group-hover:text-th-acc <?= $cr_is ? 'text-th-acc font-semibold' : 'text-th-ink'; ?>" href="<?= esc_url($cr_g['url']); ?>">
								<?= esc_html($cr_g['label']); ?>
								<svg class="w-3 h-3 stroke-current fill-transparent opacity-60 transition-transform group-hover:rotate-180" viewBox="0 0 24 24" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6" /></svg>
							</a>
							<?php // il padding-top tiene il mouse "attaccato" mentre scende dal titolo al pannello ?>
							<div class="absolute top-full left-0 pt-2 z-20 opacity-0 invisible -translate-y-1.5 transition duration-200 group-hover:opacity-100 group-hover:visible group-hover:translate-y-0 group-focus-within:opacity-100 group-focus-within:visible group-focus-within:translate-y-0">
								<div class="w-[290px] p-2 rounded-2xl bg-th-surface border border-th-line shadow-th">
									<?php foreach ($cr_g['porte'] as $cr_p) : ?>
										<a class="block px-3 py-2.5 rounded-xl no-underline transition-colors hover:bg-th-accsoft" href="<?= esc_url($cr_p['url']); ?>">
											<span class="block font-semibold text-th-ink"><?= esc_html($cr_p['label']); ?></span>
											<?php if ($cr_p['desc']) : ?><span class="block text-xs text-th-muted mt-0.5"><?= esc_html($cr_p['desc']); ?></span><?php endif; ?>
										</a>
									<?php endforeach; ?>
									<span class="block h-px bg-th-line mx-3 my-1.5" aria-hidden="true"></span>
									<a class="flex items-center justify-between px-3 py-2 font-semibold text-sm text-th-acc no-underline hover:underline" href="<?= esc_url($cr_g['url']); ?>">
										<?php printf(esc_html__('Tutto %s', 'cardsrift'), esc_html($cr_g['label'])); ?> <span aria-hidden="true">→</span>
									</a>
								</div>
							</div>
						</div>
					<?php endforeach; ?>

					<a class="px-3 py-2.5 rounded-[10px] whitespace-nowrap no-underline transition-colors hover:bg-th-accsoft hover:text-th-acc <?= $cr_on_access ? 'text-th-acc font-semibold' : 'text-th-ink'; ?>" href="<?= esc_url(home_url('/accessori/')); ?>"><?= esc_html__('Accessori', 'cardsrift'); ?></a>
					<span class="w-px h-4 bg-th-lines mx-2 shrink-0" aria-hidden="true"></span>
					<a class="px-3 py-2.5 rounded-[10px] whitespace-nowrap no-underline text-th-ink transition-colors hover:bg-th-accsoft hover:text-th-acc" href="<?= esc_url(home_url('/compriamo-le-tue-carte/')); ?>"><?= esc_html__('Compriamo le tue carte', 'cardsrift'); ?></a>
				</nav>

				<!-- RICERCA + CARRELLO + MENU -->
				<div class="flex items-center gap-2 lg:gap-3 shrink-0 ml-auto">

					<?php // 176px a lg: a 1024 esatti (iPad in orizzontale) logo + menu + ricerca da 240
					// sommavano 1041px e l'icona del carrello finiva tagliata fuori dallo schermo. ?>
					<?php cr_search_form([
						'id'          => 'cr-search-desktop',
						'class'       => 'hidden lg:block w-[176px] xl:w-[288px] h-10',
						'placeholder' => $cr_search_ph,
						'game'        => $cr_search_g,
					]); ?>

					<?php // resta un link vero: senza JavaScript porta al carrello, con JavaScript apre il drawer ?>
					<a class="w-10 h-10 grid place-items-center rounded-full text-th-ink hover:text-th-acc hover:bg-th-accsoft transition-colors relative" href="<?= esc_url($cr_cart_url); ?>" data-cr-cart-toggle aria-label="<?= esc_attr__('Carrello', 'cardsrift'); ?>">
						<svg class="w-5 h-5 stroke-current fill-transparent" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 4h2l2.4 12h11.2l2.4-9H7" /><circle cx="9" cy="20" r="1.4" /><circle cx="17" cy="20" r="1.4" /></svg>
						<?= function_exists('cr_cart_badge') ? cr_cart_badge() : ''; ?>
					</a>

					<!-- HAMBURGER (mobile) -->
					<button type="button" class="group lg:hidden w-11 h-11 relative text-th-ink" data-cr-menu-toggle data-open="false" aria-expanded="false" aria-controls="cr-mobile-menu" aria-label="<?= esc_attr__('Menu', 'cardsrift'); ?>">
						<span class="absolute left-1/2 top-1/2 w-[21px] h-0.5 -translate-x-1/2 -translate-y-[calc(50%+6px)] bg-current rounded-full transition-transform duration-300 group-data-[open=true]:translate-y-[-50%] group-data-[open=true]:rotate-45"></span>
						<span class="absolute left-1/2 top-1/2 w-[21px] h-0.5 -translate-x-1/2 -translate-y-1/2 bg-current rounded-full transition-opacity duration-200 group-data-[open=true]:opacity-0"></span>
						<span class="absolute left-1/2 top-1/2 w-[21px] h-0.5 -translate-x-1/2 -translate-y-[calc(50%-6px)] bg-current rounded-full transition-transform duration-300 group-data-[open=true]:translate-y-[-50%] group-data-[open=true]:-rotate-45"></span>
					</button>
				</div>
			</div>


		<?php endif; ?>

	</header>

	<?php if (!$cr_focus) : ?>
		<?php // Il pannello sta FUORI da <header> di proposito: l'header si ritrae con
		// transform, e un antenato trasformato diventa il blocco contenitore dei figli
		// position:fixed — il menu si sarebbe ancorato alla barra invece che allo schermo. ?>
		<!-- ============ MENU MOBILE · sempre dark ============ -->
		<div id="cr-mobile-menu" data-th="dark" data-open="false" class="lg:hidden fixed left-0 right-0 top-[var(--header-h-mobile)] bottom-0 z-40 bg-th-pg text-th-ink overflow-y-auto overscroll-contain px-6 pb-[calc(2.5rem+env(safe-area-inset-bottom))] opacity-0 invisible -translate-y-2 transition duration-300 data-[open=true]:opacity-100 data-[open=true]:visible data-[open=true]:translate-y-0">

			<?php cr_search_form([
				'id'          => 'cr-search-mobile',
				'class'       => 'block h-12 mt-4',
				'placeholder' => $cr_search_ph,
				'game'        => $cr_search_g,
			]); ?>

			<?php
			// FIDUCIA — su desktop vive nella riga di servizio, che su telefono non c'è.
			// Per un negozio nuovo è il messaggio che conta di più: qui era l'unico posto
			// dove chi compra da telefono (cioè quasi tutti) non lo vedeva mai.
			?>
			<div class="flex flex-wrap items-center gap-x-4 gap-y-1.5 mt-4 pb-4 border-b border-th-line font-metropolis text-xs text-th-muted">
				<?php if (defined('CR_CM_URL') && CR_CM_URL) : ?>
					<a class="text-th-acc font-semibold no-underline" href="<?= esc_url(CR_CM_URL); ?>" target="_blank" rel="noopener">
						<?php printf(esc_html__('★ %s positive su Cardmarket', 'cardsrift'), esc_html(defined('CR_CM_POSITIVE') ? CR_CM_POSITIVE : '100%')); ?>
					</a>
				<?php endif; ?>
				<span><?= esc_html__('Spedizione tracciata in 24/48h', 'cardsrift'); ?></span>
			</div>

			<nav class="mt-2 font-metropolis" aria-label="<?= esc_attr__('Menu', 'cardsrift'); ?>">
				<?php // <details>: accordion nativo, apre e chiude senza JavaScript ?>
				<?php foreach ($cr_games as $cr_g) : $cr_is = ($cr_g['slug'] === $cr_active_game); ?>
					<details class="cr-acc group border-b border-th-line" <?= $cr_is ? 'open' : ''; ?>>
						<summary class="flex items-center justify-between gap-3 py-4 cursor-pointer list-none font-semibold text-base <?= $cr_is ? 'text-th-acc' : 'text-th-ink'; ?>">
							<?= esc_html($cr_g['label']); ?>
							<svg class="w-3.5 h-3.5 stroke-current fill-transparent opacity-50 transition-transform group-open:rotate-180" viewBox="0 0 24 24" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6" /></svg>
						</summary>
						<?php // il wrapper serve alla transizione d'altezza (grid-template-rows): vedi layout.css ?>
						<div class="cr-acc__wrap">
							<?php // niente padding sul corpo: sarebbe padding del box e terrebbe la riga
							// aperta di 12px anche da chiuso. Lo spazio in fondo lo dà l'ultima voce. ?>
							<div class="cr-acc__body flex flex-col gap-1.5">
								<?php foreach ($cr_g['porte'] as $cr_p) : $cr_pis = ($cr_is && $cr_p['slug'] === $cr_active_tipo); ?>
									<a class="flex items-center justify-between px-3 py-3 rounded-[10px] bg-th-sur2 no-underline text-sm font-medium <?= $cr_pis ? 'text-th-acc' : 'text-th-ink'; ?>" href="<?= esc_url($cr_p['url']); ?>"><?= esc_html($cr_p['label']); ?></a>
								<?php endforeach; ?>
								<a class="flex items-center justify-between px-3 py-3 mb-3 rounded-[10px] bg-th-sur2 no-underline text-sm font-medium text-th-acc" href="<?= esc_url($cr_g['url']); ?>">
									<?php printf(esc_html__('Tutto %s', 'cardsrift'), esc_html($cr_g['label'])); ?> <span aria-hidden="true">→</span>
								</a>
							</div>
						</div>
					</details>
				<?php endforeach; ?>

				<a class="block py-4 border-b border-th-line no-underline font-semibold text-base <?= $cr_on_access ? 'text-th-acc' : 'text-th-ink'; ?>" href="<?= esc_url(home_url('/accessori/')); ?>"><?= esc_html__('Accessori', 'cardsrift'); ?></a>
				<a class="block py-4 border-b border-th-line no-underline font-semibold text-base text-th-ink" href="<?= esc_url(home_url('/compriamo-le-tue-carte/')); ?>"><?= esc_html__('Compriamo le tue carte', 'cardsrift'); ?></a>

				<div class="cr-eyebrow !text-th-soft mt-7 mb-3"><?= esc_html__('Info', 'cardsrift'); ?></div>
				<?php // py-3 = 44px di zona toccabile per riga: sotto, due voci vicine si sbagliano ?>
				<div class="grid grid-cols-2 gap-x-4">
					<?php foreach ($cr_info_full as $cr_l) : ?>
						<a class="py-3 no-underline text-sm text-th-muted" href="<?= esc_url($cr_l['url']); ?>"><?= esc_html($cr_l['label']); ?></a>
					<?php endforeach; ?>
					<?php if (defined('CR_TELEGRAM_URL') && CR_TELEGRAM_URL) : ?>
						<a class="py-3 no-underline text-sm text-th-muted" href="<?= esc_url(CR_TELEGRAM_URL); ?>" target="_blank" rel="noopener"><?= esc_html__('Telegram', 'cardsrift'); ?></a>
					<?php endif; ?>
				</div>

				<a class="flex items-center gap-2.5 mt-6 px-4 py-3.5 rounded-xl bg-th-accsoft text-th-acc no-underline font-semibold" href="<?= esc_url($cr_acct_url); ?>">
					<svg class="w-[18px] h-[18px] stroke-current fill-transparent" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="8" r="4" /><path d="M4 21c0-4 4-6 8-6s8 2 8 6" /></svg>
					<?= esc_html__('Il mio account', 'cardsrift'); ?>
				</a>
			</nav>
		</div>
	<?php endif; ?>



	<div class="wrapper pt-[var(--header-h-mobile)] lg:pt-[var(--header-h-desktop)]">
		<?php if (function_exists('cr_render_game_subnav')) cr_render_game_subnav(); ?>
			<?php // id="main-content": bersaglio del salto al contenuto. Sta qui e non su un
			// <main> di pagina perché .base è l'unico contenitore presente su OGNI vista
			// (home, pagine-manifest, carrello, checkout), e così l'ancora non si duplica. ?>
			<div id="main-content" class="base <?= function_exists('cr_is_builder_page') && cr_is_builder_page() ? '!max-w-none !px-0' : 'container'; ?>">
