<?php

/**
 * CODICE SCONTO al checkout — override CardsRift.
 * Sta sopra il modulo perché è un <form> a sé (non può stare dentro quello del
 * checkout), ma resta una riga discreta: un campo coupon in evidenza manda la
 * gente a cercare codici altrove invece di concludere.
 *
 * ⚠️ `.checkout_coupon` e il link `.showcoupon` servono a checkout.js per aprire
 * e chiudere il blocco.
 *
 * @see woocommerce/templates/checkout/form-coupon.php
 * @version 9.8.0
 */

defined('ABSPATH') || exit;

if (!wc_coupons_enabled()) {
	return;
}
?>

<div class="woocommerce-form-coupon-toggle text-sm text-th-muted mb-5">
	<?php esc_html_e('Hai un codice sconto?', 'cardsrift'); ?>
	<a href="#" role="button" class="showcoupon font-metropolis font-semibold text-th-acc no-underline hover:underline" aria-controls="woocommerce-checkout-form-coupon" aria-expanded="false"><?php esc_html_e('Inseriscilo qui', 'cardsrift'); ?></a>
</div>

<form class="checkout_coupon woocommerce-form-coupon cr-form cr-panel p-5 mb-6" method="post" style="display:none" id="woocommerce-checkout-form-coupon">
	<div class="flex flex-col tb:flex-row gap-2.5">
		<p class="form-row flex-1 !mt-0">
			<label for="coupon_code"><?php esc_html_e('Codice sconto', 'cardsrift'); ?></label>
			<input type="text" name="coupon_code" class="input-text" placeholder="<?php esc_attr_e('Es. BENVENUTO5', 'cardsrift'); ?>" id="coupon_code" value="" autocapitalize="characters" />
		</p>
		<p class="form-row !mt-0 tb:self-end">
			<button type="submit" class="cr-btn cr-btn-ghost w-full justify-center" name="apply_coupon" value="<?php esc_attr_e('Applica', 'cardsrift'); ?>"><?php esc_html_e('Applica', 'cardsrift'); ?></button>
		</p>
	</div>
</form>
