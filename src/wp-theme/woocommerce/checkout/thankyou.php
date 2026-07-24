<?php

/**
 * ORDINE CONFERMATO — override CardsRift.
 *
 * L'unico "momento lilla" del flusso (design-system §2: lilla = momento):
 * una banda di ringraziamento, poi i fatti su fondo chiaro, poi cosa succede
 * adesso. Chi ha appena speso dei soldi vuole due certezze: è andata a buon
 * fine, e so cosa aspettarmi.
 *
 * @see woocommerce/templates/checkout/thankyou.php
 * @version 9.8.0
 */

defined('ABSPATH') || exit;

if (!$order) : ?>
	<div class="woocommerce-order">
		<?php cr_shop_open_section(['step' => 'done', 'width' => 'tw-container-md']); ?>
		<?php wc_get_template('checkout/order-received.php', ['order' => false]); ?>
		<?php cr_shop_close_section(); ?>
	</div>
	<?php return;
endif;

do_action('woocommerce_before_thankyou', $order->get_id());

$cr_name   = $order->get_billing_first_name();
$cr_failed = $order->has_status('failed');
?>

<div class="woocommerce-order">

	<!-- ============ IL MOMENTO ============ -->
	<section class="cr-sec cr-patt" data-th="<?= $cr_failed ? 'dark' : 'lilla'; ?>">
		<div class="tw-container tw-section">
			<div class="py-10 lg:py-16">
				<?php cr_flow_steps('done'); ?>

				<?php if ($cr_failed) : ?>
					<h1 class="font-metropolis font-bold !text-2xl lg:!text-4xl !leading-tight mt-7"><?php esc_html_e('Il pagamento non è andato a buon fine', 'cardsrift'); ?></h1>
					<p class="text-th-muted leading-relaxed mt-3 max-w-[56ch]"><?php esc_html_e('La banca ha rifiutato la transazione e l’ordine non è stato completato. Non ti abbiamo addebitato nulla: puoi riprovare, oppure scriverci e lo sistemiamo insieme.', 'cardsrift'); ?></p>
					<div class="flex flex-wrap gap-3 mt-7">
						<a class="cr-btn cr-btn-solid" href="<?= esc_url($order->get_checkout_payment_url()); ?>"><?php esc_html_e('Riprova il pagamento', 'cardsrift'); ?></a>
						<?php if (function_exists('cr_tg_dm') && cr_tg_dm(__('Ciao! Ho avuto un problema col pagamento di un ordine.', 'cardsrift'))) : ?>
							<a class="cr-btn cr-btn-ghost" href="<?= esc_url(cr_tg_dm(__('Ciao! Ho avuto un problema col pagamento di un ordine.', 'cardsrift'))); ?>" target="_blank" rel="noopener"><?php esc_html_e('Scrivici', 'cardsrift'); ?></a>
						<?php endif; ?>
					</div>

				<?php else : ?>
					<div class="cr-eyebrow mt-7 mb-2.5"><?php esc_html_e('Ordine ricevuto', 'cardsrift'); ?></div>
					<h1 class="font-metropolis font-bold !text-2xl lg:!text-5xl !leading-tight">
						<?= $cr_name ? esc_html(sprintf(__('Grazie, %s.', 'cardsrift'), $cr_name)) : esc_html__('Grazie.', 'cardsrift'); ?>
					</h1>
					<p class="text-th-ink/85 text-md lg:text-lg leading-relaxed mt-4 max-w-[54ch]">
						<?php esc_html_e('Le tue carte sono già sul nostro banco di lavoro. Ti abbiamo mandato una mail con il riepilogo: se non la vedi, controlla lo spam.', 'cardsrift'); ?>
					</p>
					<p class="font-bylon italic text-th-ink/70 mt-5"><?= esc_html(cr_order_status_line($order)); ?></p>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<?php if (!$cr_failed) : ?>

		<!-- ============ I FATTI ============ -->
		<section class="cr-sec" data-th="light">
			<div class="tw-container tw-section">
				<div class="py-10 lg:py-14">

					<ul class="woocommerce-order-overview order_details grid grid-cols-2 lg:grid-cols-4 gap-px rounded-[20px] overflow-hidden bg-th-line list-none p-0 m-0">
						<li class="woocommerce-order-overview__order order bg-th-surface p-5">
							<span class="cr-label"><?php esc_html_e('Ordine', 'cardsrift'); ?></span>
							<strong class="font-metropolis font-semibold text-base text-th-ink tabular-nums">#<?= esc_html($order->get_order_number()); ?></strong>
						</li>
						<li class="woocommerce-order-overview__date date bg-th-surface p-5">
							<span class="cr-label"><?php esc_html_e('Data', 'cardsrift'); ?></span>
							<strong class="font-metropolis font-semibold text-base text-th-ink"><?= esc_html(wc_format_datetime($order->get_date_created())); ?></strong>
						</li>
						<li class="woocommerce-order-overview__total total bg-th-surface p-5">
							<span class="cr-label"><?php esc_html_e('Totale', 'cardsrift'); ?></span>
							<strong class="cr-price"><?= wp_kses_post($order->get_formatted_order_total()); ?></strong>
						</li>
						<li class="woocommerce-order-overview__payment-method method bg-th-surface p-5">
							<span class="cr-label"><?php esc_html_e('Pagamento', 'cardsrift'); ?></span>
							<strong class="font-metropolis font-semibold text-base text-th-ink"><?= $order->get_payment_method_title() ? wp_kses_post($order->get_payment_method_title()) : '—'; ?></strong>
						</li>
					</ul>

					<?php // istruzioni del gateway (es. coordinate per il bonifico): servono SUBITO ?>
					<div class="cr-thankyou-gateway mt-8 lg:mt-10">
						<?php do_action('woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id()); ?>
					</div>

					<div class="mt-8 lg:mt-10">
						<?php // dettaglio ordine + indirizzi (order/order-details.php) ?>
						<?php do_action('woocommerce_thankyou', $order->get_id()); ?>
					</div>

					<div class="flex flex-wrap gap-3 mt-8">
						<?php if (is_user_logged_in()) : ?>
							<a class="cr-btn cr-btn-solid" href="<?= esc_url(wc_get_endpoint_url('orders', '', wc_get_page_permalink('myaccount'))); ?>"><?php esc_html_e('Vedi i tuoi ordini', 'cardsrift'); ?></a>
						<?php endif; ?>
						<a class="cr-btn cr-btn-ghost" href="<?= esc_url(home_url('/')); ?>"><?php esc_html_e('Continua a sfogliare', 'cardsrift'); ?></a>
					</div>

				</div>
			</div>
		</section>

		<!-- ============ COSA SUCCEDE ADESSO ============ -->
		<section class="cr-sec" data-th="dark">
			<div class="tw-container tw-section">
				<div class="py-12 lg:py-14">
					<div class="cr-eyebrow mb-2.5"><?php esc_html_e('Da qui in poi', 'cardsrift'); ?></div>
					<h2 class="font-metropolis font-bold !text-xl lg:!text-3xl mb-8"><?php esc_html_e('Cosa succede adesso', 'cardsrift'); ?></h2>

					<ol class="grid grid-cols-1 tb:grid-cols-3 gap-7 lg:gap-10 list-none p-0 m-0">
						<?php
						$cr_steps = [
							['n' => '1', 't' => __('Le prepariamo', 'cardsrift'), 'd' => __('Ricontrolliamo una a una le carte del tuo ordine: condizione, lingua, integrità. Se qualcosa non ci convince, ti scriviamo prima di spedire.', 'cardsrift')],
							['n' => '2', 'd' => __('Bustina, toploader e cartoncino rigido dentro una busta imbottita. Poi parte con spedizione tracciata: il codice ti arriva via mail.', 'cardsrift'), 't' => __('Le imballiamo e spediamo', 'cardsrift')],
							['n' => '3', 'd' => __('In genere 24/48h dalla partenza. Qualsiasi cosa non torni, scrivici su Telegram: rispondiamo noi, non un modulo.', 'cardsrift'), 't' => __('Arrivano da te', 'cardsrift')],
						];
						foreach ($cr_steps as $cr_s) : ?>
							<li class="flex flex-col gap-2.5">
								<span class="grid place-items-center w-9 h-9 rounded-full bg-th-accsoft text-th-acc font-metropolis font-bold text-sm"><?= esc_html($cr_s['n']); ?></span>
								<span class="font-metropolis font-semibold text-base text-th-ink mt-1"><?= esc_html($cr_s['t']); ?></span>
								<p class="text-sm leading-relaxed text-th-muted m-0"><?= esc_html($cr_s['d']); ?></p>
							</li>
						<?php endforeach; ?>
					</ol>
				</div>
			</div>
		</section>

	<?php endif; ?>
</div>
