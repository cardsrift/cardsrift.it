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
 * Chip "gioco · lingua" dedotto dal prodotto (categoria principale + attributo lingua).
 * Usato dalla card e dalla vetrina hero: l'etichetta è automatica, non da inserire a mano.
 */
function cr_product_chip($product_id)
{
    $terms = get_the_terms($product_id, 'product_cat');
    $chip  = $terms && !is_wp_error($terms) ? $terms[0]->name : '';
    $product = wc_get_product($product_id);
    $lingua  = $product ? $product->get_attribute('lingua') : '';
    if ($lingua) {
        $chip .= ($chip ? ' · ' : '') . $lingua;
    }
    return $chip;
}

/**
 * Data di uscita per il display: il campo ACF "data_uscita" (date_picker) salva Ymd
 * → mostra "gg/mm". Accetta anche testo libero legacy (lo restituisce così com'è).
 */
function cr_format_uscita($raw)
{
    $raw = trim((string) $raw);
    if ($raw === '') {
        return '';
    }
    if (preg_match('/^\d{8}$/', $raw)) {
        return substr($raw, 6, 2) . '/' . substr($raw, 4, 2);
    }
    return $raw;
}

/**
 * Card prodotto unica per tutto il sito (griglie, offerte, "in arrivo").
 * Gestisce gli stati WooCommerce: prezzo (del/ins nativo), sconto %,
 * scorte (disponibile / ultimi X / esaurito+avvisami),
 * quick-add AJAX per i prodotti semplici, link alla PDP per i variabili.
 *
 * @param int   $product_id
 * @param array $opts { glass?: bool, top_deal?: bool, in_arrivo?: bool }
 *   in_arrivo = prodotto non ancora in vendita (sola vista): niente acquisto,
 *   badge "In arrivo", data di uscita dal campo ACF "data_uscita".
 */
function cr_product_card($product_id, $opts = [])
{
    $product = wc_get_product($product_id);
    if (!$product || $product->get_status() !== 'publish') {
        return;
    }

    $glass     = !empty($opts['glass']);
    $top_deal  = !empty($opts['top_deal']);
    $in_arrivo = !empty($opts['in_arrivo']);

    $in_stock  = $product->is_in_stock();
    $on_sale   = $product->is_on_sale();
    $stock_qty = $product->get_stock_quantity(); // null se non gestito
    $is_simple = $product->is_type('simple');

    $chip = cr_product_chip($product_id);

    // Badge % di sconto (prezzi semplici; per i variabili WooCommerce mostra il range)
    $sale_pct = 0;
    if ($on_sale && $is_simple && (float) $product->get_regular_price() > 0) {
        $sale_pct = round(100 - ((float) $product->get_sale_price() / (float) $product->get_regular_price()) * 100);
    }

    // Data di uscita ("in arrivo"): campo ACF date_picker "data_uscita" → "gg/mm"
    $data_uscita = $in_arrivo ? cr_format_uscita(get_field('data_uscita', $product_id)) : '';

    $classes = 'cr-card';
    if ($glass) $classes .= ' cr-card--glass';
    if ($top_deal) $classes .= ' cr-card--deal';
    if (!$in_stock && !$in_arrivo) $classes .= ' cr-card--soldout';
?>
    <a class="<?= esc_attr($classes); ?>" href="<?= esc_url(get_permalink($product_id)); ?>">

        <?php if ($in_arrivo) : ?>
            <span class="cr-badge cr-badge--pre"><?= esc_html__('In arrivo', 'cardsrift'); ?></span>
        <?php elseif (!$in_stock) : ?>
            <span class="cr-badge cr-badge--out"><?= esc_html__('Esaurito', 'cardsrift'); ?></span>
        <?php elseif ($top_deal) : ?>
            <span class="cr-badge cr-badge--top"><?= esc_html__('Top deal', 'cardsrift'); ?></span>
        <?php elseif ($sale_pct > 0) : ?>
            <span class="cr-badge cr-badge--sale">−<?= $sale_pct; ?>%</span>
        <?php endif; ?>

        <span class="cr-well">
            <?= $product->get_image('woocommerce_thumbnail'); ?>
            <?php if ($in_arrivo) : ?>
                <?php // sola vista: nessun acquisto ?>
            <?php elseif ($in_stock && $is_simple) : ?>
                <!-- quick-add AJAX WooCommerce (prodotti semplici) -->
                <span class="cr-qadd add_to_cart_button ajax_add_to_cart" data-product_id="<?= $product_id; ?>" data-quantity="1">
                    <?= esc_html__('Aggiungi al carrello', 'cardsrift'); ?>
                </span>
            <?php elseif ($in_stock) : ?>
                <span class="cr-qadd"><?= esc_html__('Scegli condizione', 'cardsrift'); ?></span>
            <?php endif; ?>
        </span>

        <span class="flex flex-col gap-2 flex-1 pt-3 px-4 pb-4">
            <?php if ($chip) : ?>
                <span class="cr-chip"><?= esc_html($chip); ?></span>
            <?php endif; ?>

            <span class="font-metropolis font-semibold text-sm leading-snug text-th-ink min-h-[2.7em]"><?= esc_html($product->get_name()); ?></span>

            <span class="flex items-center justify-between gap-2 mt-auto">
                <?php if ($in_arrivo) : ?>
                    <span class="font-metropolis font-semibold text-xs uppercase tracking-wider text-th-acc">
                        <?= $data_uscita ? esc_html(sprintf(__('Esce il %s', 'cardsrift'), $data_uscita)) : esc_html__('Prossimamente', 'cardsrift'); ?>
                    </span>
                <?php else : ?>
                    <span class="cr-price"><?= $product->get_price_html(); ?></span>
                    <?php if (!$in_stock) : ?>
                        <?php // nessuno stato: sotto compare "Avvisami quando torna" ?>
                    <?php elseif ($stock_qty !== null && $stock_qty <= 3) : ?>
                        <span class="cr-stock cr-stock--low"><?= esc_html(sprintf(__('Ultimi %d', 'cardsrift'), $stock_qty)); ?></span>
                    <?php else : ?>
                        <span class="cr-stock cr-stock--ok"><?= esc_html__('Disponibile', 'cardsrift'); ?></span>
                    <?php endif; ?>
                <?php endif; ?>
            </span>

            <?php if (!$in_stock && !$in_arrivo) : ?>
                <!-- back-in-stock: aggancio per il plugin di notifica (Fase 3) -->
                <span class="cr-btn cr-btn-ghost justify-center !text-xs !py-2.5 mt-1"><?= esc_html__('Avvisami quando torna', 'cardsrift'); ?></span>
            <?php endif; ?>
        </span>
    </a>
<?php
}

/**
 * Query prodotti per le griglie del page builder.
 * $sorgente: manuale (ids da relationship) | recenti | offerte
 * I prodotti "in arrivo" (con data_uscita) sono esclusi: non sono ancora in vendita.
 */
function cr_grid_products($sorgente, $manual_ids = [], $limit = 4)
{
    if ($sorgente === 'manuale') {
        return array_slice(array_filter(array_map('intval', (array) $manual_ids)), 0, $limit);
    }

    $args = [
        'status'  => 'publish',
        'limit'   => $limit,
        'return'  => 'ids',
        'exclude' => cr_preorder_products(100), // niente prodotti in arrivo nelle griglie
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

/**
 * Prodotti "in arrivo" (vetrina uscite): quelli con il campo ACF "data_uscita"
 * valorizzato, ordinati per data crescente (i più imminenti prima).
 * data_uscita è salvato Ymd → l'ordinamento per meta_value è cronologico.
 */
function cr_preorder_products($limit = 3)
{
    // Memoizzato: la lista completa ordinata si calcola una volta sola per request
    // (griglie, ticker e sezione "in arrivo" la chiedono più volte).
    static $all = null;
    if ($all === null) {
        $q = new WP_Query([
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_key'       => 'data_uscita',
            'orderby'        => 'meta_value',
            'order'          => 'ASC',
            'meta_query'     => [[
                'key'     => 'data_uscita',
                'value'   => '',
                'compare' => '!=',
            ]],
            'no_found_rows'  => true,
        ]);
        $all = $q->posts;
    }
    return $limit > 0 ? array_slice($all, 0, $limit) : $all;
}

/**
 * Singole (raccoglitore): sorgente automatica. Le singole sono prodotti VARIABILI
 * (attributi condizione/lingua): mostriamo i più recenti.
 */
function cr_singole_products($limit = 6)
{
    return wc_get_products([
        'status'  => 'publish',
        'type'    => 'variable',
        'limit'   => $limit,
        'orderby' => 'date',
        'order'   => 'DESC',
        'return'  => 'ids',
    ]);
}

/**
 * Link a un messaggio privato Telegram (@khewro) con testo precompilato: così, quando
 * qualcuno scrive dal sito, il titolare sa da dove arriva. Se $text è vuoto ripiega sul
 * solo profilo. Il GRUPPO community è un'altra cosa: CR_TELEGRAM_URL (link d'invito).
 */
function cr_tg_dm($text = '')
{
    $base = defined('CR_TELEGRAM_DM') ? CR_TELEGRAM_DM : '';
    return ($base && $text) ? $base . '?text=' . rawurlencode($text) : $base;
}

/**
 * Voci del ticker — automatiche, nessun inserimento a mano (ibrido 2 + 2):
 * 2 messaggi brand fissi (tradotti) + fino a 2 dai movimenti reali del negozio
 * (ultimo arrivo, un prodotto in offerta). Se il negozio è vuoto restano i 2 fissi.
 * Ritorna array di ['testo' => ..., 'evidenzia' => ...].
 */
function cr_ticker_voci()
{
    $voci = [
        ['testo' => __('Compriamo le tue carte', 'cardsrift'), 'evidenzia' => sprintf(__('singole e sealed dal %s Cardmarket', 'cardsrift'), CR_BUY_MIN_PCT)],
        ['testo' => __('Spedizione tracciata', 'cardsrift'),   'evidenzia' => __('in 24/48h', 'cardsrift')],
    ];

    $recent = wc_get_products([
        'status'  => 'publish',
        'limit'   => 1,
        'orderby' => 'date',
        'order'   => 'DESC',
        'exclude' => cr_preorder_products(100),
        'return'  => 'objects',
    ]);
    if (!empty($recent[0])) {
        $voci[] = ['testo' => __('Appena aggiunto', 'cardsrift'), 'evidenzia' => $recent[0]->get_name()];
    }

    $sale_ids = wc_get_product_ids_on_sale();
    if (!empty($sale_ids)) {
        $p = wc_get_product($sale_ids[0]);
        if ($p) {
            $voci[] = ['testo' => __('In offerta', 'cardsrift'), 'evidenzia' => $p->get_name()];
        }
    }

    return $voci;
}

/* =====================================================================
 * PLACEHOLDER (solo sviluppo, gate CR_PLACEHOLDER) — riempie le sezioni
 * prodotto quando il negozio è ancora vuoto, per vedere la home completa.
 * NON tocca la pipeline reale: scatta solo quando la query non trova nulla;
 * appena ci sono prodotti veri, questi vengono ignorati.
 * ===================================================================== */

/** Dati fittizi coerenti col mondo TCG (immagine = placeholder WooCommerce). */
function cr_placeholder_dataset()
{
    return [
        ['name' => 'Pokémon 151 · Display',       'chip' => 'Pokémon · IT',  'price' => '89,90'],
        ['name' => 'One Piece OP-09 · Box',        'chip' => 'One Piece · JP', 'price' => '109,00'],
        ['name' => 'MTG Bloomburrow · Bundle',     'chip' => 'Magic · EN',     'price' => '44,90'],
        ['name' => 'Shiny Treasure ex · Box',      'chip' => 'Pokémon · JP',   'price' => '74,50'],
        ['name' => 'Charizard ex · Special Art',   'chip' => 'Pokémon · IT',   'price' => '129,00'],
        ['name' => 'Luffy Leader · Alternate Art', 'chip' => 'One Piece · EN', 'price' => '34,90'],
    ];
}

/** Prossimo item fittizio (ruota sul dataset). Ritorna [dato, indice 1-based]. */
function cr_ph_item()
{
    static $i = 0;
    $set = cr_placeholder_dataset();
    $d = $set[$i % count($set)];
    return [$d, ++$i];
}

/** Card prodotto placeholder (stessa veste di cr_product_card, non cliccabile). */
function cr_ph_card($opts = [])
{
    list($d, $n) = cr_ph_item();
    $glass     = !empty($opts['glass']);
    $in_arrivo = !empty($opts['in_arrivo']);
    $top       = !empty($opts['top_deal']);
    $sale      = (!$in_arrivo && !$top && $n % 3 === 0);
    $img       = function_exists('wc_placeholder_img') ? wc_placeholder_img('woocommerce_thumbnail') : '';
    $classes   = 'cr-card' . ($glass ? ' cr-card--glass' : '') . ($top ? ' cr-card--deal' : '');
?>
    <span class="<?= esc_attr($classes); ?>" aria-hidden="true">
        <?php if ($in_arrivo) : ?>
            <span class="cr-badge cr-badge--pre"><?= esc_html__('In arrivo', 'cardsrift'); ?></span>
        <?php elseif ($top) : ?>
            <span class="cr-badge cr-badge--top"><?= esc_html__('Top deal', 'cardsrift'); ?></span>
        <?php elseif ($sale) : ?>
            <span class="cr-badge cr-badge--sale">−15%</span>
        <?php endif; ?>
        <span class="cr-well"><?= $img; ?></span>
        <span class="flex flex-col gap-2 flex-1 pt-3 px-4 pb-4">
            <span class="cr-chip"><?= esc_html($d['chip']); ?></span>
            <span class="font-metropolis font-semibold text-sm leading-snug text-th-ink min-h-[2.7em]"><?= esc_html($d['name']); ?></span>
            <span class="flex items-center justify-between gap-2 mt-auto">
                <span class="cr-price">€ <?= esc_html($d['price']); ?></span>
                <?php if ($in_arrivo) : ?>
                    <span class="font-metropolis font-semibold text-xs uppercase tracking-wider text-th-acc"><?= esc_html(sprintf(__('Esce il %s', 'cardsrift'), '26/09')); ?></span>
                <?php else : ?>
                    <span class="cr-stock cr-stock--ok"><?= esc_html__('Disponibile', 'cardsrift'); ?></span>
                <?php endif; ?>
            </span>
        </span>
    </span>
<?php
}

/** Tasca singola placeholder (stessa veste di .cr-pocket del raccoglitore). */
function cr_ph_pocket()
{
    list($d, $n) = cr_ph_item();
    $img  = function_exists('wc_placeholder_img') ? wc_placeholder_img('woocommerce_thumbnail') : '';
    $cond = ['NM', 'LP', 'MP'][$n % 3];
?>
    <span class="cr-pocket" aria-hidden="true">
        <span class="cr-pocket__well"><?= $img; ?></span>
        <span class="flex flex-col gap-1 pt-2 px-0.5 pb-1">
            <span class="flex gap-1 flex-wrap">
                <span class="cr-cchip cr-cchip--cond"><?= esc_html($cond); ?></span>
                <span class="cr-cchip">IT</span>
            </span>
            <span class="font-metropolis font-semibold text-xs leading-tight text-th-ink min-h-[2.6em]"><?= esc_html($d['name']); ?></span>
            <span class="cr-price !text-sm">€ <?= esc_html($d['price']); ?></span>
        </span>
        <span class="cr-pocket__pick"><?= esc_html__('Vedi carta', 'cardsrift'); ?></span>
    </span>
<?php
}
