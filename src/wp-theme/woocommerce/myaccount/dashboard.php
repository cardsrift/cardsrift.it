<?php

/**
 * BACHECA ACCOUNT — override CardsRift.
 *
 * Il testo predefinito di WooCommerce ("Dalla bacheca puoi vedere i tuoi ordini,
 * gestire gli indirizzi…") descrive il menu che sta già lì accanto. Al suo posto
 * la cosa che l'utente è venuto davvero a vedere: a che punto è l'ultimo ordine.
 *
 * @see woocommerce/templates/myaccount/dashboard.php
 * @version 9.8.0
 */

defined('ABSPATH') || exit;

$cr_orders = wc_get_orders([
	'customer' => get_current_user_id(),
	'limit'    => 1,
	'orderby'  => 'date',
	'order'    => 'DESC',
	'status'   => array_keys(wc_get_order_statuses()),
]);
$cr_order = $cr_orders ? $cr_orders[0] : null;
?>

<p class="text-th-muted leading-relaxed mb-6">
	<?php printf(
		wp_kses(__('Ciao <strong class="font-metropolis font-semibold text-th-ink">%1$s</strong> — non sei tu? <a class="text-th-acc no-underline hover:underline" href="%2$s">Esci</a>.', 'cardsrift'), ['strong' => ['class' => []], 'a' => ['href' => [], 'class' => []]]),
		esc_html($current_user->display_name),
		esc_url(wc_logout_url())
	); ?>
</p>

<?php if ($cr_order) : ?>

	<!-- ---------- ULTIMO ORDINE ---------- -->
	<div class="cr-panel p-5 lg:p-6">
		<div class="flex flex-wrap items-center gap-3 mb-4">
			<?php cr_order_status_chip($cr_order); ?>
			<span class="font-metropolis font-semibold text-sm text-th-ink tabular-nums">#<?= esc_html($cr_order->get_order_number()); ?></span>
			<span class="text-xs text-th-soft"><?= esc_html(wc_format_datetime($cr_order->get_date_created())); ?></span>
			<span class="cr-price ml-auto"><?= wp_kses_post($cr_order->get_formatted_order_total()); ?></span>
		</div>

		<?php if ($cr_line = cr_order_status_line($cr_order)) : ?>
			<p class="text-sm text-th-muted leading-relaxed mb-4"><?= esc_html($cr_line); ?></p>
		<?php endif; ?>

		<div class="flex flex-wrap items-center gap-2">
			<?php
			$cr_items = $cr_order->get_items();
			$cr_shown = array_slice($cr_items, 0, 5, true);
			foreach ($cr_shown as $cr_item) :
				$cr_product = $cr_item->get_product();
				if (!$cr_product) continue;
			?>
				<span class="cr-thumb !w-10" title="<?= esc_attr($cr_item->get_name()); ?>">
					<?= $cr_product->get_image('woocommerce_thumbnail'); // phpcs:ignore ?>
				</span>
			<?php endforeach; ?>
			<?php if (count($cr_items) > 5) : ?>
				<span class="font-metropolis font-semibold text-xs text-th-soft ml-1">
					<?= esc_html(sprintf(_n('+%d altro', '+%d altri', count($cr_items) - 5, 'cardsrift'), count($cr_items) - 5)); ?>
				</span>
			<?php endif; ?>

			<a class="cr-btn cr-btn-ghost !py-2.5 !text-xs ml-auto" href="<?= esc_url($cr_order->get_view_order_url()); ?>">
				<?php esc_html_e('Vedi il dettaglio', 'cardsrift'); ?>
			</a>
		</div>
	</div>

<?php else : ?>

	<!-- ---------- NESSUN ORDINE ---------- -->
	<div class="cr-panel p-6 lg:p-8 text-center">
		<h2 class="font-metropolis font-semibold !text-lg"><?php esc_html_e('Non hai ancora ordinato niente', 'cardsrift'); ?></h2>
		<p class="text-sm text-th-muted leading-relaxed mt-2 max-w-[46ch] mx-auto"><?php esc_html_e('Quando farai il primo ordine lo troverai qui, con lo stato aggiornato e il codice di tracciamento.', 'cardsrift'); ?></p>
		<a class="cr-btn cr-btn-solid mt-5" href="<?= esc_url(home_url('/')); ?>"><?php esc_html_e('Sfoglia il catalogo', 'cardsrift'); ?></a>
	</div>

<?php endif; ?>

<!-- ---------- SCORCIATOIE ---------- -->
<div class="grid grid-cols-1 tb:grid-cols-2 gap-4 mt-4">
	<?php
	$cr_cards = [
		[
			'url'   => wc_get_endpoint_url('edit-address'),
			'title' => __('Indirizzi', 'cardsrift'),
			'text'  => __('Fatturazione e spedizione, pronti al prossimo ordine.', 'cardsrift'),
			'icon'  => 'edit-address',
		],
		[
			'url'   => wc_get_endpoint_url('edit-account'),
			'title' => __('Dati dell’account', 'cardsrift'),
			'text'  => __('Nome, email e password.', 'cardsrift'),
			'icon'  => 'edit-account',
		],
	];
	foreach ($cr_cards as $cr_c) : ?>
		<a class="cr-panel p-5 no-underline transition-colors hover:border-th-acc2 flex items-start gap-4" href="<?= esc_url($cr_c['url']); ?>">
			<span class="grid place-items-center w-10 h-10 shrink-0 rounded-xl bg-th-accsoft text-th-acc">
				<?= function_exists('cr_account_nav_icon') ? cr_account_nav_icon($cr_c['icon']) : ''; // phpcs:ignore ?>
			</span>
			<span class="min-w-0">
				<span class="block font-metropolis font-semibold text-sm text-th-ink"><?= esc_html($cr_c['title']); ?></span>
				<span class="block text-xs text-th-muted leading-relaxed mt-1"><?= esc_html($cr_c['text']); ?></span>
			</span>
		</a>
	<?php endforeach; ?>
</div>

<?php
do_action('woocommerce_account_dashboard');
do_action('woocommerce_before_my_account');
do_action('woocommerce_after_my_account');
