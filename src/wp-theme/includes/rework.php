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
 * Nome del "gioco" del prodotto: il primo termine product_cat che NON è una categoria di tipo
 * (Singole/Sealed/Accessori). Così un prodotto può stare in "Magic" + "Singole" e il chip/calendario
 * mostrano sempre "Magic". Solo-lettura del DB: nessuna dipendenza dal plugin sync.
 */
function cr_product_game($product_id)
{
    $terms = get_the_terms($product_id, 'product_cat');
    if (!$terms || is_wp_error($terms)) {
        return '';
    }
    $tipo = defined('CR_CAT_TIPO') ? CR_CAT_TIPO : [];
    foreach ($terms as $t) {
        if (!in_array($t->slug, $tipo, true)) {
            return $t->name;
        }
    }
    return $terms[0]->name; // fallback: nessuna categoria gioco assegnata
}

/**
 * Chip "gioco · lingua" dedotto dal prodotto (gioco + attributo lingua).
 * Usato dalla card e dalla vetrina hero: l'etichetta è automatica, non da inserire a mano.
 */
function cr_product_chip($product_id)
{
    $chip    = cr_product_game($product_id);
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
 * tax_query per ESCLUDERE le singole: stanno SOLO nel raccoglitore, mai nelle griglie
 * generali / ticker / shop (il resto del catalogo è sealed e accessori).
 */
function cr_not_singole_tax()
{
    return [[
        'taxonomy' => 'product_cat',
        'field'    => 'slug',
        'terms'    => ['singole'],
        'operator' => 'NOT IN',
    ]];
}

/**
 * Query prodotti per le griglie del page builder.
 * $sorgente: manuale (ids da relationship) | recenti | offerte
 * I prodotti "in arrivo" (con data_uscita) e le singole sono esclusi.
 */
function cr_grid_products($sorgente, $manual_ids = [], $limit = 4, $game = '')
{
    if ($sorgente === 'manuale') {
        return array_slice(array_filter(array_map('intval', (array) $manual_ids)), 0, $limit);
    }

    // niente singole (stanno nel raccoglitore) e, se richiesto, un solo gioco (mai misto)
    $tax = cr_not_singole_tax();
    if ($game) {
        $tax = ['relation' => 'AND', ['taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => [$game]], $tax[0]];
    }

    $args = [
        'status'    => 'publish',
        'limit'     => $limit,
        'return'    => 'ids',
        'exclude'   => cr_preorder_products(100), // niente prodotti in arrivo nelle griglie
        'tax_query' => $tax,
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
 * Hero della landing di un gioco, curato a mano dalle Theme Options
 * (gruppo "Hero landing di gioco", un tab per gioco → campi hero_{gioco}_*).
 * Ritorna un array normalizzato; 'img' vuoto ⇒ la landing usa lo skin di brand.
 * I campi copy vuoti restano vuoti: i default li decide il template.
 */
function cr_game_hero($game)
{
    $gs  = str_replace('-', '_', (string) $game); // slug → nome campo (one-piece → one_piece)
    $img = get_field("hero_{$gs}_immagine", 'option');
    $url = '';
    if (is_array($img) && !empty($img['url'])) {
        $url = $img['url'];
    } elseif (is_string($img) && $img !== '') {
        $url = $img;
    }
    return [
        'img'         => $url,
        'sopratitolo' => trim((string) get_field("hero_{$gs}_sopratitolo", 'option')),
        'titolo'      => trim((string) get_field("hero_{$gs}_titolo", 'option')),
        'sottotitolo' => trim((string) get_field("hero_{$gs}_sottotitolo", 'option')),
        'data'        => trim((string) get_field("hero_{$gs}_data", 'option')),
        'cta_label'   => trim((string) get_field("hero_{$gs}_cta_label", 'option')),
        'cta_url'     => trim((string) get_field("hero_{$gs}_cta_url", 'option')),
    ];
}

/**
 * Singole (raccoglitore): sorgente automatica = prodotti in categoria "Singole".
 * Sono per lo più SEMPLICI (un articolo/condizione, mappa 1:1 con Cardmarket); variabili solo
 * se una carta è stoccata in più condizioni. Mostriamo i più recenti.
 */
function cr_singole_products($limit = 6)
{
    return wc_get_products([
        'status'   => 'publish',
        'category' => ['singole'],
        'limit'    => $limit,
        'orderby'  => 'date',
        'order'    => 'DESC',
        'return'   => 'ids',
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

    // Base condivisa dalle voci automatiche: niente singole e niente prodotti "in arrivo"
    // (coerente con le griglie: gli in-arrivo non sono acquistabili, non vanno reclamizzati).
    $base = [
        'status'    => 'publish',
        'limit'     => 1,
        'return'    => 'objects',
        'exclude'   => cr_preorder_products(100),
        'tax_query' => cr_not_singole_tax(),
    ];

    $recent = wc_get_products($base + ['orderby' => 'date', 'order' => 'DESC']);
    if (!empty($recent[0])) {
        $voci[] = ['testo' => __('Appena aggiunto', 'cardsrift'), 'evidenzia' => $recent[0]->get_name()];
    }

    $sale_ids = wc_get_product_ids_on_sale();
    if (!empty($sale_ids)) {
        $sale = wc_get_products($base + ['include' => $sale_ids]);
        if (!empty($sale[0])) {
            $voci[] = ['testo' => __('In offerta', 'cardsrift'), 'evidenzia' => $sale[0]->get_name()];
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

/**
 * Tasca singola REALE (raccoglitore + listati). Variabile → chip condizione/lingua
 * (primo valore) e CTA "Scegli condizione"; semplice → "Vedi carta". Echo diretto.
 */
function cr_pocket_card($pid)
{
    $product = wc_get_product($pid);
    if (!$product) {
        return;
    }
    $cond   = $product->get_attribute('condizione');
    $lingua = $product->get_attribute('lingua');
    $cond   = $cond ? trim(explode(',', $cond)[0]) : '';
    $lingua = $lingua ? trim(explode(',', $lingua)[0]) : '';
?>
    <a class="cr-pocket" href="<?= esc_url(get_permalink($pid)); ?>">
        <span class="cr-pocket__well">
            <?= $product->get_image('woocommerce_thumbnail'); ?>
        </span>
        <span class="flex flex-col gap-1 pt-2 px-0.5 pb-1">
            <span class="flex gap-1 flex-wrap">
                <?php if ($cond) : ?><span class="cr-cchip cr-cchip--cond"><?= esc_html($cond); ?></span><?php endif; ?>
                <?php if ($lingua) : ?><span class="cr-cchip"><?= esc_html($lingua); ?></span><?php endif; ?>
            </span>
            <span class="font-metropolis font-semibold text-xs leading-tight text-th-ink min-h-[2.6em]"><?= esc_html($product->get_name()); ?></span>
            <span class="cr-price !text-sm"><?= $product->get_price_html(); ?></span>
        </span>
        <span class="cr-pocket__pick"><?= $product->is_type('variable') ? esc_html__('Scegli condizione', 'cardsrift') : esc_html__('Vedi carta', 'cardsrift'); ?></span>
    </a>
<?php
}

/**
 * Faccette filtro disponibili per un listato (gioco+tipo). Solo gli attributi con
 * ALMENO 2 valori realmente presenti tra i prodotti in scope: niente filtri a opzione
 * singola. Chiave = taxonomy (pa_*), valore = ['label'=>..., 'terms'=>[...]].
 */
function cr_listing_facets($game, $tipo)
{
    $tax = ['relation' => 'AND'];
    if ($game) {
        $tax[] = ['taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => [$game]];
    }
    if ($tipo) {
        $tax[] = ['taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => [$tipo]];
    }
    $ids = get_posts([
        'post_type'   => 'product',
        'post_status' => 'publish',
        'numberposts' => -1,
        'fields'      => 'ids',
        'tax_query'   => $tax,
    ]);
    if (!$ids) {
        return [];
    }
    $wanted = [
        'pa_espansione' => __('Espansione', 'cardsrift'),
        'pa_condizione' => __('Condizione', 'cardsrift'),
        'pa_lingua'     => __('Lingua', 'cardsrift'),
        'pa_foil'       => __('Foil', 'cardsrift'),
    ];
    $facets = [];
    foreach ($wanted as $txn => $label) {
        if (!taxonomy_exists($txn)) {
            continue;
        }
        $terms = wp_get_object_terms($ids, $txn);
        if (is_wp_error($terms) || count($terms) < 2) {
            continue; // utile solo con ≥2 opzioni reali
        }
        $facets[$txn] = ['label' => $label, 'terms' => $terms];
    }
    return $facets;
}
