<?php

/**
 * REWORK — helper centralizzati del design system.
 * Documentazione completa: docs/rework-fase-1.md
 */

/**
 * Tema di sezione dal campo ACF radio "tema" (dark|light|lilla|lilla2).
 * Ritorna sempre un valore valido: il dark è la base del sito.
 */
function cr_theme($component_data)
{
    $valid = ['dark', 'light', 'lilla', 'lilla2'];
    $tema = is_array($component_data) ? ($component_data['tema'] ?? '') : '';
    return in_array($tema, $valid, true) ? $tema : 'dark';
}

/**
 * Card prodotto unica per tutto il sito (griglie, offerte, preordini).
 * Gestisce gli stati WooCommerce: prezzo (del/ins nativo), sconto %,
 * scorte (disponibile / ultimi X / esaurito+avvisami), preordine con data,
 * quick-add AJAX per i prodotti semplici, link alla PDP per i variabili.
 *
 * @param int   $product_id
 * @param array $opts { glass?: bool, top_deal?: bool, preordine?: bool }
 */
function cr_product_card($product_id, $opts = [])
{
    $product = wc_get_product($product_id);
    if (!$product || $product->get_status() !== 'publish') {
        return;
    }

    $glass     = !empty($opts['glass']);
    $top_deal  = !empty($opts['top_deal']);
    $preordine = !empty($opts['preordine']);

    $in_stock  = $product->is_in_stock();
    $on_sale   = $product->is_on_sale();
    $stock_qty = $product->get_stock_quantity(); // null se non gestito
    $is_simple = $product->is_type('simple');

    // Chip "gioco · lingua" dalla categoria principale + attributo lingua (se presente)
    $terms = get_the_terms($product_id, 'product_cat');
    $chip  = $terms && !is_wp_error($terms) ? $terms[0]->name : '';
    $lingua = $product->get_attribute('lingua');
    if ($lingua) {
        $chip .= ($chip ? ' · ' : '') . $lingua;
    }

    // Badge % di sconto (prezzi semplici; per i variabili WooCommerce mostra il range)
    $sale_pct = 0;
    if ($on_sale && $is_simple && (float) $product->get_regular_price() > 0) {
        $sale_pct = round(100 - ((float) $product->get_sale_price() / (float) $product->get_regular_price()) * 100);
    }

    // Data di uscita (preordini): campo ACF "data_uscita" sul prodotto
    $data_uscita = $preordine ? get_field('data_uscita', $product_id) : '';

    $classes = 'cr-card';
    if ($glass) $classes .= ' cr-card--glass';
    if ($top_deal) $classes .= ' cr-card--deal';
    if (!$in_stock) $classes .= ' cr-card--soldout';
?>
    <a class="<?= esc_attr($classes); ?>" href="<?= esc_url(get_permalink($product_id)); ?>">

        <?php if (!$in_stock) : ?>
            <span class="cr-badge cr-badge--out"><?= __('Esaurito', 'cardsrift'); ?></span>
        <?php elseif ($top_deal) : ?>
            <span class="cr-badge cr-badge--top"><?= __('Top deal', 'cardsrift'); ?></span>
        <?php elseif ($preordine) : ?>
            <span class="cr-badge cr-badge--pre"><?= __('Preordine', 'cardsrift'); ?></span>
        <?php elseif ($sale_pct > 0) : ?>
            <span class="cr-badge cr-badge--sale">−<?= $sale_pct; ?>%</span>
        <?php endif; ?>

        <span class="cr-well">
            <?= $product->get_image('woocommerce_thumbnail'); ?>
            <?php if ($in_stock && $is_simple) : ?>
                <!-- quick-add AJAX WooCommerce (prodotti semplici) -->
                <span class="cr-qadd add_to_cart_button ajax_add_to_cart" data-product_id="<?= $product_id; ?>" data-quantity="1">
                    <?= $preordine ? __('Preordina', 'cardsrift') : __('Aggiungi al carrello', 'cardsrift'); ?>
                </span>
            <?php elseif ($in_stock) : ?>
                <span class="cr-qadd"><?= __('Scegli condizione', 'cardsrift'); ?></span>
            <?php endif; ?>
        </span>

        <span class="flex flex-col gap-2 flex-1 pt-3 px-4 pb-4">
            <?php if ($chip) : ?>
                <span class="cr-chip"><?= esc_html($chip); ?></span>
            <?php endif; ?>

            <span class="font-metropolis font-semibold text-sm leading-snug text-th-ink min-h-[2.7em]"><?= esc_html($product->get_name()); ?></span>

            <span class="flex items-center justify-between gap-2 mt-auto">
                <span class="cr-price"><?= $product->get_price_html(); ?></span>

                <?php if ($preordine && $data_uscita) : ?>
                    <span class="font-metropolis font-semibold text-xs uppercase tracking-wider text-th-acc"><?= __('Esce il', 'cardsrift') . ' ' . esc_html($data_uscita); ?></span>
                <?php elseif (!$in_stock) : ?>
                    <?php // nessuno stato: sotto compare "Avvisami quando torna" ?>
                <?php elseif ($stock_qty !== null && $stock_qty <= 3) : ?>
                    <span class="cr-stock cr-stock--low"><?= sprintf(__('Ultimi %d', 'cardsrift'), $stock_qty); ?></span>
                <?php else : ?>
                    <span class="cr-stock cr-stock--ok"><?= __('Disponibile', 'cardsrift'); ?></span>
                <?php endif; ?>
            </span>

            <?php if (!$in_stock) : ?>
                <!-- back-in-stock: aggancio per il plugin di notifica (Fase 3) -->
                <span class="cr-btn cr-btn-ghost justify-center !text-xs !py-2.5 mt-1"><?= __('Avvisami quando torna', 'cardsrift'); ?></span>
            <?php endif; ?>
        </span>
    </a>
<?php
}

/**
 * Query prodotti per le griglie del page builder.
 * $sorgente: manuale (ids da relationship) | recenti | offerte
 */
function cr_grid_products($sorgente, $manual_ids = [], $limit = 4)
{
    if ($sorgente === 'manuale') {
        return array_slice(array_filter(array_map('intval', (array) $manual_ids)), 0, $limit);
    }

    $args = [
        'status' => 'publish',
        'limit'  => $limit,
        'return' => 'ids',
    ];

    if ($sorgente === 'offerte') {
        $args['include'] = wc_get_product_ids_on_sale();
        if (empty($args['include'])) {
            return [];
        }
    } else { // recenti
        $args['orderby'] = 'date';
        $args['order']   = 'DESC';
    }

    return wc_get_products($args);
}
