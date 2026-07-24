<?php

/**
 * PDP "PORTALE" — single-product.php green-field (task #22).
 * Prodotti SEMPLICI (singole e sealed): galleria + buy-box con fatti statici e add-to-cart
 * semplice. "Altre versioni" = fratelli con lo stesso _cardmarket_id (altra condizione/lingua),
 * mostrati solo se esistono. In fondo: descrizione, correlati (stesso gioco+tipo) e JSON-LD.
 * Nessun colore hardcoded: solo token th- e cr-. Il carrello resta globale.
 */

defined('ABSPATH') || exit;

get_header('shop');

while (have_posts()) : the_post();
	$product = wc_get_product(get_the_ID());
	if (!$product) {
		continue;
	}
	$pid = $product->get_id();

	// contesto gioco/tipo (per breadcrumb + correlati; il carrello NON è scoped)
	$game_slug  = function_exists('cr_product_game_slug') ? cr_product_game_slug($pid) : '';
	$tipo_slug  = function_exists('cr_product_tipo_slug') ? cr_product_tipo_slug($pid) : '';
	$game_label = $game_slug ? cr_game_label($game_slug) : '';
	$tipo_label = $tipo_slug ? cr_tipo_label($tipo_slug) : '';
	$is_singole = ($tipo_slug === 'singole');

	// dati carta
	$cm_id  = get_post_meta($pid, '_cardmarket_id', true);
	$cond   = $product->get_attribute('condizione');
	$lingua = $product->get_attribute('lingua');
	$foil   = $product->get_attribute('foil');
	$esp    = $product->get_attribute('espansione');
	$chip   = function_exists('cr_product_chip') ? cr_product_chip($pid) : '';

	// "in arrivo" (data_uscita valorizzata → non acquistabile, come le vetrine uscite)
	$uscita    = function_exists('cr_format_uscita') ? cr_format_uscita(get_field('data_uscita', $pid)) : '';
	$in_arrivo = (bool) $uscita;

	// stock
	$in_stock = $product->is_in_stock();
	$qty      = $product->get_stock_quantity();

	// fatti statici (solo i valorizzati; "Normale" foil = default, non lo mostriamo)
	$cond_full = ['MT' => 'Mint', 'NM' => 'Near Mint', 'EX' => 'Excellent', 'GD' => 'Good', 'LP' => 'Light Played', 'PL' => 'Played', 'PO' => 'Poor'];
	$facts = [];
	if ($esp)    $facts[__('Espansione', 'cardsrift')] = $esp;
	if ($cond)   $facts[__('Condizione', 'cardsrift')] = strtoupper($cond) . (isset($cond_full[strtoupper($cond)]) ? ' · ' . $cond_full[strtoupper($cond)] : '');
	if ($lingua) $facts[__('Lingua', 'cardsrift')]     = $lingua;
	if ($foil && strtolower($foil) !== 'normale') $facts[__('Foil', 'cardsrift')] = $foil;

	// altre versioni = stesso _cardmarket_id, altri prodotti
	$siblings = [];
	if ($cm_id) {
		$siblings = get_posts([
			'post_type'    => 'product',
			'post_status'  => 'publish',
			'numberposts'  => -1,
			'fields'       => 'ids',
			'post__not_in' => [$pid],
			'meta_key'     => '_cardmarket_id',
			'meta_value'   => $cm_id,
		]);
	}

	// correlati: stesso gioco + tipo, esclusa questa (mai misto)
	$related = [];
	if ($game_slug && $tipo_slug) {
		$related = get_posts([
			'post_type'    => 'product',
			'post_status'  => 'publish',
			'numberposts'  => 4,
			'fields'       => 'ids',
			'orderby'      => 'rand',
			'post__not_in' => array_merge([$pid], (array) $siblings),
			'tax_query'    => [
				'relation' => 'AND',
				['taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => [$game_slug]],
				['taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => [$tipo_slug]],
			],
		]);
	}

	$gallery = $product->get_gallery_image_ids();
?>

<main>
	<section class="cr-sec" data-th="light">
		<div class="tw-container tw-section">
			<div class="py-8 lg:py-12">

				<!-- breadcrumb -->
				<nav class="cr-eyebrow !text-th-soft !tracking-[.16em] mb-6 flex flex-wrap items-center" aria-label="<?php esc_attr_e('Percorso', 'cardsrift'); ?>">
					<a class="no-underline hover:text-th-acc transition-colors" href="<?= esc_url(home_url('/')); ?>"><?php esc_html_e('Home', 'cardsrift'); ?></a>
					<?php if ($game_slug) : ?>
						<span class="opacity-40 px-1.5">/</span>
						<a class="no-underline hover:text-th-acc transition-colors" href="<?= esc_url(home_url('/' . $game_slug . '/')); ?>"><?= esc_html($game_label); ?></a>
					<?php endif; ?>
					<?php if ($tipo_slug && $game_slug) : ?>
						<span class="opacity-40 px-1.5">/</span>
						<a class="no-underline hover:text-th-acc transition-colors" href="<?= esc_url(home_url('/' . $game_slug . '/' . $tipo_slug . '/')); ?>"><?= esc_html($tipo_label); ?></a>
					<?php endif; ?>
					<span class="opacity-40 px-1.5">/</span><span class="text-th-acc truncate max-w-[40vw]"><?= esc_html($product->get_name()); ?></span>
				</nav>

				<?php if (function_exists('woocommerce_output_all_notices')) woocommerce_output_all_notices(); ?>

				<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12 items-start">

					<!-- ========== GALLERIA ========== -->
					<div class="lg:sticky lg:top-[calc(var(--header-h-desktop)+1.5rem)]">
						<div class="relative grid place-items-center bg-white-pure rounded-2xl p-6 lg:p-10 min-h-[360px] lg:min-h-[520px] shadow-th">
							<?php if ($in_arrivo) : ?>
								<span class="cr-badge cr-badge--pre"><?php esc_html_e('In arrivo', 'cardsrift'); ?></span>
							<?php elseif (!$in_stock) : ?>
								<span class="cr-badge cr-badge--out"><?php esc_html_e('Esaurito', 'cardsrift'); ?></span>
							<?php elseif ($product->is_on_sale()) : ?>
								<span class="cr-badge cr-badge--sale"><?php esc_html_e('Offerta', 'cardsrift'); ?></span>
							<?php endif; ?>
							<?= $product->get_image('woocommerce_single', ['class' => 'max-h-[300px] lg:max-h-[460px] w-auto object-contain']); ?>
						</div>
						<?php if ($gallery) : ?>
							<div class="grid grid-cols-5 gap-2 mt-2">
								<?php foreach (array_slice(array_merge([$product->get_image_id()], $gallery), 0, 5) as $img_id) : ?>
									<a href="<?= esc_url(wp_get_attachment_url($img_id)); ?>" target="_blank" rel="noopener" class="grid place-items-center bg-white-pure rounded-lg p-2 aspect-square border border-th-line hover:border-th-acc transition-colors">
										<?= wp_get_attachment_image($img_id, 'woocommerce_gallery_thumbnail', false, ['class' => 'max-h-full w-auto object-contain']); ?>
									</a>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>

					<!-- ========== BUY BOX ========== -->
					<div class="flex flex-col gap-5">

						<div>
							<?php if ($chip) : ?><span class="cr-chip mb-3"><?= esc_html($chip); ?></span><?php endif; ?>
							<h1 class="font-metropolis font-bold !text-2xl lg:!text-4xl !leading-tight mt-2"><?= esc_html($product->get_name()); ?></h1>
						</div>

						<div class="flex items-center gap-4 flex-wrap">
							<div class="cr-price !text-3xl"><?= $product->get_price_html(); ?></div>
							<?php if ($in_arrivo) : ?>
								<span class="font-bylon uppercase tracking-[.14em] text-sm text-th-acc"><?php printf(esc_html__('Esce il %s', 'cardsrift'), esc_html($uscita)); ?></span>
							<?php elseif ($in_stock) : ?>
								<?php // disponibilità reale: scorte meno quello che è già nel carrello ?>
								<?php cr_stock_line($product); ?>
							<?php else : ?>
								<span class="cr-stock" style="color:var(--cr-muted)"><?php esc_html_e('Non disponibile', 'cardsrift'); ?></span>
							<?php endif; ?>
						</div>

						<!-- fatti statici -->
						<?php if ($facts) : ?>
							<dl class="grid grid-cols-[auto_1fr] gap-x-6 gap-y-2.5 py-4 border-y border-th-line">
								<?php foreach ($facts as $k => $v) : ?>
									<dt class="cr-label !mb-0 self-center"><?= esc_html($k); ?></dt>
									<dd class="font-metropolis font-semibold text-sm text-th-ink"><?= esc_html($v); ?></dd>
								<?php endforeach; ?>
							</dl>
						<?php endif; ?>

						<!-- add to cart -->
						<?php if ($in_arrivo) : ?>
							<div class="cr-glass rounded-xl p-4 text-sm text-th-muted"><?php esc_html_e('Non ancora acquistabile: annunciamo l’apertura degli ordini sul canale Telegram e via email.', 'cardsrift'); ?></div>
						<?php elseif ($in_stock && $product->is_purchasable() && cr_stock_left($product) === 0) : ?>
							<?php // tutte le copie disponibili sono già nel carrello di chi guarda ?>
							<div class="cr-panel p-4 flex items-center gap-3">
								<svg class="w-5 h-5 stroke-th-ok fill-transparent shrink-0" viewBox="0 0 24 24" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5" /></svg>
								<p class="text-sm text-th-muted leading-relaxed m-0">
									<?php esc_html_e('Hai già nel carrello tutte le copie che abbiamo.', 'cardsrift'); ?>
									<a class="text-th-acc no-underline hover:underline" href="<?= esc_url(wc_get_cart_url()); ?>"><?php esc_html_e('Vedi il carrello', 'cardsrift'); ?></a>
								</p>
							</div>

						<?php elseif ($in_stock && $product->is_purchasable()) : ?>
							<?php
							// Aggiunta in AJAX per i prodotti semplici: senza, la pagina si ricarica e
							// si perde la posizione — fastidioso proprio sulle singole, dove subito
							// sotto ci sono le "altre versioni" della stessa carta. Il <form> resta
							// e funziona da solo se JavaScript non c'è: WooCommerce intercetta il
							// click solo quando lo script è attivo.
							$cr_ajax = $product->is_type('simple') ? ' add_to_cart_button ajax_add_to_cart' : '';
							// il massimo è ciò che resta DAVVERO, non le scorte a magazzino
							$cr_left = cr_stock_left($product);
							?>
							<form class="cart flex items-stretch gap-3" action="<?= esc_url(apply_filters('woocommerce_add_to_cart_form_action', $product->get_permalink())); ?>" method="post">
								<input type="number" name="quantity" value="1" min="1" <?= $cr_left !== null ? 'max="' . esc_attr($cr_left) . '"' : ''; ?> step="1" class="cr-input !w-20 text-center font-semibold" aria-label="<?php esc_attr_e('Quantità', 'cardsrift'); ?>">
								<button type="submit" name="add-to-cart" value="<?= esc_attr($pid); ?>" class="cr-btn cr-btn-solid flex-1 justify-center single_add_to_cart_button<?= esc_attr($cr_ajax); ?>" data-product_id="<?= esc_attr($pid); ?>" data-quantity="1">
									<span class="cr-btn__label"><?php esc_html_e('Aggiungi al carrello', 'cardsrift'); ?></span>
									<span class="cr-btn__done">
										<svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6L9 17l-5-5" /></svg>
										<?php esc_html_e('Aggiunto', 'cardsrift'); ?>
									</span>
								</button>
							</form>
						<?php else : ?>
							<button type="button" class="cr-btn cr-btn-ghost opacity-60 cursor-not-allowed w-full justify-center" disabled><?php esc_html_e('Esaurito', 'cardsrift'); ?></button>
						<?php endif; ?>

						<!-- trust strip -->
						<ul class="flex flex-col gap-2 text-sm text-th-muted mt-1">
							<li class="flex items-center gap-2.5"><svg class="w-4 h-4 stroke-th-acc fill-transparent shrink-0" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg><?php esc_html_e('Spedizione tracciata in 24/48h', 'cardsrift'); ?></li>
							<li class="flex items-center gap-2.5"><svg class="w-4 h-4 stroke-th-acc fill-transparent shrink-0" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg><?php esc_html_e('Controllata e imballata con cura', 'cardsrift'); ?></li>
							<li class="flex items-center gap-2.5"><svg class="w-4 h-4 stroke-th-acc fill-transparent shrink-0" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg><?php echo esc_html(sprintf(__('%s valutazioni positive su Cardmarket', 'cardsrift'), defined('CR_CM_POSITIVE') ? CR_CM_POSITIVE : '')); ?></li>
						</ul>

						<!-- altre versioni (stesso _cardmarket_id) -->
						<?php if ($siblings) : ?>
							<div class="mt-2">
								<div class="cr-eyebrow mb-3"><?php esc_html_e('Altre versioni di questa carta', 'cardsrift'); ?></div>
								<div class="flex flex-col gap-2">
									<?php foreach ($siblings as $sid) :
										$sp = wc_get_product($sid);
										if (!$sp) continue;
										$scond = $sp->get_attribute('condizione');
										$sling = $sp->get_attribute('lingua');
									?>
										<a class="flex items-center justify-between gap-3 rounded-xl border border-th-line px-4 py-3 no-underline transition-colors hover:border-th-acc hover:bg-th-accsoft" href="<?= esc_url(get_permalink($sid)); ?>">
											<span class="flex items-center gap-2 flex-wrap">
												<?php if ($scond) : ?><span class="cr-cchip cr-cchip--cond"><?= esc_html($scond); ?></span><?php endif; ?>
												<?php if ($sling) : ?><span class="cr-cchip"><?= esc_html($sling); ?></span><?php endif; ?>
												<span class="font-metropolis text-sm text-th-muted"><?= $sp->is_in_stock() ? esc_html__('Disponibile', 'cardsrift') : esc_html__('Esaurito', 'cardsrift'); ?></span>
											</span>
											<span class="cr-price !text-sm whitespace-nowrap"><?= $sp->get_price_html(); ?></span>
										</a>
									<?php endforeach; ?>
								</div>
							</div>
						<?php endif; ?>

					</div>
				</div>

				<!-- ========== DESCRIZIONE ========== -->
				<?php if ($product->get_description()) : ?>
					<div class="mt-14 lg:mt-20 max-w-[70ch]">
						<div class="cr-eyebrow mb-3"><?php esc_html_e('Descrizione', 'cardsrift'); ?></div>
						<div class="prose-cr font-adelle text-th-ink leading-relaxed [&_p]:mb-4 [&_a]:text-th-acc"><?= wp_kses_post(wpautop($product->get_description())); ?></div>
					</div>
				<?php endif; ?>

			</div>
		</div>
	</section>

	<!-- ========== CORRELATI (stesso gioco + tipo) ========== -->
	<?php if ($related) : ?>
		<section class="cr-sec" data-th="dark">
			<div class="tw-container tw-section">
				<div class="py-14 lg:py-16">
					<div class="flex items-end justify-between gap-4 mb-7">
						<div>
							<div class="cr-eyebrow"><?= esc_html($game_label); ?></div>
							<h2 class="font-metropolis font-bold !text-xl lg:!text-3xl mt-2"><?php esc_html_e('Continua a sfogliare', 'cardsrift'); ?></h2>
						</div>
						<a class="font-metropolis font-semibold text-xs uppercase tracking-wider text-th-acc no-underline hover:underline whitespace-nowrap pb-1" href="<?= esc_url(home_url('/' . $game_slug . '/' . $tipo_slug . '/')); ?>"><?php esc_html_e('Vedi tutto', 'cardsrift'); ?> →</a>
					</div>
					<?php if ($is_singole) : ?>
						<div class="cr-glass rounded-[20px] p-4 lg:p-5">
							<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3" data-fx-stagger>
								<?php foreach ($related as $rid) cr_pocket_card($rid); ?>
							</div>
						</div>
					<?php else : ?>
						<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 lg:gap-4" data-fx-stagger>
							<?php foreach ($related as $rid) cr_product_card($rid); ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php
	// ---- JSON-LD: Product/Offer + BreadcrumbList (SEO) ----
	$img_url = wp_get_attachment_url($product->get_image_id());
	$ld_product = array_filter([
		'@context'    => 'https://schema.org/',
		'@type'       => 'Product',
		'name'        => $product->get_name(),
		'sku'         => $product->get_sku(),
		'image'       => $img_url ?: null,
		'description' => wp_strip_all_tags($product->get_short_description() ?: $product->get_description()),
		'offers'      => [
			'@type'         => 'Offer',
			'price'         => $product->get_price(),
			'priceCurrency' => get_woocommerce_currency(),
			'availability'  => $in_stock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
			'url'           => get_permalink($pid),
			'itemCondition' => 'https://schema.org/UsedCondition',
		],
	]);
	$crumbs = [['name' => __('Home', 'cardsrift'), 'url' => home_url('/')]];
	if ($game_slug) $crumbs[] = ['name' => $game_label, 'url' => home_url('/' . $game_slug . '/')];
	if ($game_slug && $tipo_slug) $crumbs[] = ['name' => $tipo_label, 'url' => home_url('/' . $game_slug . '/' . $tipo_slug . '/')];
	$crumbs[] = ['name' => $product->get_name(), 'url' => get_permalink($pid)];
	$ld_crumbs = ['@context' => 'https://schema.org/', '@type' => 'BreadcrumbList', 'itemListElement' => []];
	foreach ($crumbs as $i => $c) {
		$ld_crumbs['itemListElement'][] = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $c['name'], 'item' => $c['url']];
	}
	echo '<script type="application/ld+json">' . wp_json_encode($ld_product, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
	echo '<script type="application/ld+json">' . wp_json_encode($ld_crumbs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
	?>

<?php endwhile; ?>
</main>
<?php
get_footer();
