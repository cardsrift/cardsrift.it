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
 * Il prodotto è una singola FOIL? (attributo pa_foil = foil o reverse-holo, non "normale").
 * Serve al riflesso olografico: l'effetto foil sta SOLO sulle singole foil.
 */
function cr_is_foil($product_id)
{
    $product = wc_get_product($product_id);
    if (!$product) {
        return false;
    }
    $foil = strtolower((string) $product->get_attribute('foil'));
    return $foil !== '' && strpos($foil, 'normale') === false;
}

/** Attributi effetti per la card: tilt "peso" su tutte, holo solo sulle foil. */
function cr_card_fx_attrs($product_id)
{
    return 'data-cr-tilt' . (cr_is_foil($product_id) ? ' data-cr-holo' : '');
}

/**
 * Markup del contatore carrello (usato dall'header E dal fragment WooCommerce, così
 * si aggiorna in AJAX all'add-to-cart e il JS può farlo "poppare"). Le classi Tailwind
 * qui sono scansionate perché il file vive in src/wp-theme/includes.
 */
function cr_cart_badge()
{
    $ct = (function_exists('WC') && WC() && WC()->cart) ? (int) WC()->cart->get_cart_contents_count() : 0;
    $hidden = $ct > 0 ? '' : ' hidden';
    return '<span class="cart-contents-count absolute top-1 right-1 min-w-[17px] h-[17px] px-1 grid place-items-center rounded-full bg-th-acc text-th-pg text-[10px] font-bold leading-none' . $hidden . '">' . $ct . '</span>';
}
add_filter('woocommerce_add_to_cart_fragments', 'cr_cart_fragment');
function cr_cart_fragment($fragments)
{
    $fragments['span.cart-contents-count'] = cr_cart_badge();
    return $fragments;
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
 * Il prodotto ha una foto vera (non il segnaposto di WooCommerce)?
 *
 * ⚠️ Non usare `has_post_thumbnail()` / `get_the_post_thumbnail_url()` sui prodotti:
 * le foto del catalogo NON stanno nella libreria media, arrivano dal plugin di sync
 * come URL remoto in `_ct_image` (CardTrader). `$product->get_image()` le serve
 * comunque, ma `get_image_id()` resta vuoto — motivo per cui l'hero mostrava carte
 * bianche mentre le griglie, che usano `get_image()`, erano a posto.
 */
function cr_product_has_image($product)
{
    if (!$product instanceof WC_Product) {
        return false;
    }
    if ($product->get_image_id()) {
        return true;
    }
    return (bool) get_post_meta($product->get_id(), '_ct_image', true);
}

/**
 * Le foto di listato si caricano pigramente, sempre.
 *
 * Misurato su telefono: un listato di singole scarica ~1,5 MB di sole foto. Il
 * caricamento differito c'era già, ma solo su una parte delle immagini (24 su 29 nel
 * listato, 3 su 8 nel sealed): dipendeva da chi generava l'`<img>`, e quelle iniettate
 * dal plugin di sync uscivano senza attributo. Qui lo si mette a tutte, insieme a
 * `decoding="async"` — la decodifica di un JPEG da 960px blocca il thread principale
 * mentre si scorre. La foto grande della scheda prodotto è esclusa: è il contenuto
 * principale della pagina e differirla peggiorerebbe l'LCP.
 *
 * @param string $html markup dell'immagine
 */
function cr_lazy_image($html)
{
    if (!is_string($html) || $html === '') {
        return $html;
    }
    if (strpos($html, 'loading=') === false) {
        $html = str_replace('<img ', '<img loading="lazy" ', $html);
    }
    if (strpos($html, 'decoding=') === false) {
        $html = str_replace('<img ', '<img decoding="async" ', $html);
    }
    return $html;
}

/**
 * QUALITÀ DELLE FOTO — usa sempre la versione piena di CardTrader.
 *
 * Il plugin di sync serve l'anteprima (**180×180**) a tutte le size tranne quelle
 * della scheda prodotto. Ma le nostre card mostrano la foto a ~286 CSS px, che su
 * uno schermo retina significa 572 px reali: l'anteprima veniva ingrandita più di
 * tre volte e si vedeva. La versione piena è 960×960 ed è già salvata in
 * `_ct_image_full`: basta preferirla.
 *
 * Il filtro gira DOPO quello del plugin (priorità 20) e si limita a sostituire
 * l'URL — nessuna logica duplicata, e se un giorno il prodotto avrà una foto in
 * libreria media questa funzione si fa da parte.
 *
 * Costo: ~134 KB per foto invece di 10. Accettabile perché è lo stesso file su
 * tutto il sito (una sola copia in cache, riusata da griglie, carrello e drawer)
 * e perché l'anteprima non basterebbe comunque a nessuna densità di schermo.
 */
add_filter('woocommerce_product_get_image', 'cr_full_res_image', 20, 2);
function cr_full_res_image($html, $product)
{
    if (!$product instanceof WC_Product || $product->get_image_id()) {
        return $html; // foto in libreria media: decide WordPress, non noi
    }
    $pid     = $product->get_id();
    $preview = get_post_meta($pid, '_ct_image', true);
    $full    = get_post_meta($pid, '_ct_image_full', true);

    if (!$preview || !$full || strpos($html, $preview) === false) {
        return $html;
    }
    return str_replace($preview, $full, $html);
}

/**
 * Quanti pezzi si possono ANCORA aggiungere: scorte meno quelli già nel carrello
 * di chi sta guardando.
 *
 * È la differenza fra "ne restano 3" e "ne restano 3, ma 3 ce li hai già tu".
 * Su un catalogo fatto di pezzi unici la seconda è l'unica informazione utile:
 * senza, si clicca su "aggiungi" per sentirsi dire di no dal server.
 *
 * @return int|null null = scorte non gestite (quantità sconosciuta, sempre acquistabile)
 */
function cr_stock_left($product)
{
    if (!$product instanceof WC_Product || !$product->managing_stock()) {
        return null;
    }
    $qty = $product->get_stock_quantity();
    if ($qty === null) {
        return null;
    }

    $in_cart = 0;
    if (function_exists('WC') && WC()->cart) {
        foreach (WC()->cart->get_cart() as $item) {
            $id = !empty($item['variation_id']) ? (int) $item['variation_id'] : (int) $item['product_id'];
            if ($id === $product->get_id()) {
                $in_cart += (int) $item['quantity'];
            }
        }
    }
    return max(0, (int) $qty - $in_cart);
}

/**
 * Riga di disponibilità: quantità reale, e quando è finita lo dice chiaramente.
 * `$left === 0` con scorte a magazzino significa "ce l'hai già tutto nel carrello":
 * lo diciamo così invece che "Esaurito", che farebbe temere di averlo perso.
 */
function cr_stock_line($product, $small = false)
{
    $left = cr_stock_left($product);
    $cls  = $small ? ' !text-[11px]' : '';

    if ($left === null) {
        printf('<span class="cr-stock cr-stock--ok%s" data-cr-stock-for="%d">%s</span>', $cls, $product->get_id(), esc_html__('Disponibile', 'cardsrift'));
        return;
    }
    if ($left === 0) {
        printf('<span class="cr-stock cr-stock--incart%s" data-cr-stock-for="%d">%s</span>', $cls, $product->get_id(), esc_html__('Tutto nel carrello', 'cardsrift'));
        return;
    }
    // Sulle SINGOLE avere un pezzo solo è la norma, non un allarme: sono carte usate,
    // ne esiste una copia per condizione e lingua. Marcarle tutte come "scorta bassa"
    // tingeva di giallo l'intero listato e faceva sembrare il negozio vuoto invece che
    // curato — e un avviso ripetuto su ogni riga smette comunque di dire qualcosa.
    // L'urgenza resta dov'è un'informazione vera: sul sigillato, che si riassortisce.
    $unique = function_exists('cr_product_tipo_slug') && cr_product_tipo_slug($product->get_id()) === 'singole';

    printf(
        '<span class="cr-stock %s%s" data-cr-stock-for="%d" data-cr-left="%d"%s>%s</span>',
        (!$unique && $left <= 3) ? 'cr-stock--low' : 'cr-stock--ok',
        $cls,
        $product->get_id(),
        $left,
        // lo legge components/shop.js: dopo un'aggiunta in AJAX ricalcola la riga e
        // senza questo tornerebbe a colorarla di giallo
        $unique ? ' data-cr-unique="1"' : '',
        esc_html(sprintf(_n('%d disponibile', '%d disponibili', $left, 'cardsrift'), $left))
    );
}

/**
 * Comando "aggiungi al carrello" della card e della tasca.
 *
 * Sta SEMPRE nella riga del prezzo, mai sopra la foto: in un negozio di carte
 * l'immagine è il prodotto, e un overlay che ne copre un terzo nasconde proprio
 * ciò che si sta comprando. Essendo sempre visibile funziona anche dove `:hover`
 * non esiste (touch) — prima il pulsante era invisibile ma cliccabile, quindi su
 * mobile un tocco sulla carta aggiungeva al carrello invece di aprire la scheda.
 *
 * @param WC_Product $product
 * @param string     $size 'md' (card) | 'sm' (tasca del raccoglitore)
 */
function cr_add_control($product, $size = 'md')
{
	$cls  = 'cr-add' . ($size === 'sm' ? ' cr-add--sm' : '');
	$name = $product->get_name();

	// Già tutto nel carrello: il comando resta al suo posto (niente salti di layout)
	// ma è spento, così non si prova un'aggiunta che il server rifiuterebbe.
	if (cr_stock_left($product) === 0) {
		printf(
			'<span class="%s cr-add--full" data-cr-add-for="%d" aria-hidden="true" title="%s">%s</span>',
			esc_attr($cls),
			$product->get_id(),
			esc_attr__('Hai già tutte le copie disponibili nel carrello', 'cardsrift'),
			cr_icon_check()
		);
		return;
	}

	// Variabile: la scelta di condizione/lingua si fa sulla scheda, non da qui.
	if (!$product->is_type('simple')) {
		printf(
			'<a class="%s" href="%s" aria-label="%s">%s</a>',
			esc_attr($cls),
			esc_url(get_permalink($product->get_id())),
			esc_attr(sprintf(__('Scegli condizione e lingua di %s', 'cardsrift'), $name)),
			cr_icon_arrow()
		);
		return;
	}
	?>
	<button type="button"
		class="<?= esc_attr($cls); ?> add_to_cart_button ajax_add_to_cart"
		data-product_id="<?= esc_attr($product->get_id()); ?>"
		data-cr-add-for="<?= esc_attr($product->get_id()); ?>"
		data-quantity="1"
		aria-label="<?= esc_attr(sprintf(__('Aggiungi %s al carrello', 'cardsrift'), $name)); ?>">
		<?= cr_icon_cart(); // phpcs:ignore ?>
		<?= cr_icon_check(); // phpcs:ignore ?>
		<span class="cr-add__spin" aria-hidden="true"></span>
	</button>
	<?php
}

/** Glifi condivisi: stesso tratto (1.8, round) delle icone dell'header. */
function cr_icon_cart()
{
	return '<svg class="cr-add__ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 4h2l2.4 12h11.2l2.4-9H7"/><circle cx="9" cy="20" r="1.4"/><circle cx="17" cy="20" r="1.4"/></svg>';
}
function cr_icon_check()
{
	return '<svg class="cr-add__ok" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6L9 17l-5-5"/></svg>';
}
function cr_icon_arrow()
{
	return '<svg class="cr-add__ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>';
}

/**
 * Card prodotto unica per tutto il sito (griglie, offerte, "in arrivo").
 * Gestisce gli stati WooCommerce: prezzo (del/ins nativo), sconto %,
 * scorte (disponibile / ultimi X / esaurito+avvisami), aggiunta al carrello.
 *
 * Struttura: la card è un <div>, non un <a>. Il link vero è il titolo, che si
 * "distende" su tutta la card con ::after (.cr-card__link); il pulsante di
 * aggiunta gli sta sopra. Così l'HTML è valido (niente controlli dentro un link),
 * la tastiera raggiunge prima la scheda e poi l'aggiunta, e non restano zone
 * cliccabili invisibili.
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
    <div class="<?= esc_attr($classes); ?>" <?= cr_card_fx_attrs($product_id); ?>>

        <?php if ($in_arrivo) : ?>
            <span class="cr-badge cr-badge--pre"><?= esc_html__('In arrivo', 'cardsrift'); ?></span>
        <?php elseif (!$in_stock) : ?>
            <span class="cr-badge cr-badge--out"><?= esc_html__('Esaurito', 'cardsrift'); ?></span>
        <?php elseif ($top_deal) : ?>
            <span class="cr-badge cr-badge--top"><?= esc_html__('Top deal', 'cardsrift'); ?></span>
        <?php elseif ($sale_pct > 0) : ?>
            <span class="cr-badge cr-badge--sale">−<?= $sale_pct; ?>%</span>
        <?php endif; ?>

        <?php // il well resta libero: niente sopra la foto ?>
        <span class="cr-well"><?= cr_lazy_image($product->get_image('woocommerce_thumbnail')); ?></span>

        <div class="flex flex-col gap-2 flex-1 pt-3 px-4 pb-4">

            <div class="flex flex-wrap items-center justify-between gap-x-2 gap-y-1 min-h-[22px]">
                <?php if ($chip) : ?><span class="cr-chip"><?= esc_html($chip); ?></span><?php endif; ?>
                <?php if (!$in_arrivo && $in_stock) cr_stock_line($product, true); ?>
            </div>

            <a class="cr-card__link font-metropolis font-semibold text-sm leading-snug text-th-ink min-h-[2.7em] no-underline" href="<?= esc_url(get_permalink($product_id)); ?>"><?= esc_html($product->get_name()); ?></a>

            <div class="flex items-center justify-between gap-2 mt-auto min-h-[40px]">
                <?php if ($in_arrivo) : ?>
                    <span class="font-metropolis font-semibold text-xs uppercase tracking-wider text-th-acc">
                        <?= $data_uscita ? esc_html(sprintf(__('Esce il %s', 'cardsrift'), $data_uscita)) : esc_html__('Prossimamente', 'cardsrift'); ?>
                    </span>
                <?php else : ?>
                    <span class="cr-price"><?= $product->get_price_html(); ?></span>
                    <?php if ($in_stock) cr_add_control($product); ?>
                <?php endif; ?>
            </div>

            <?php if (!$in_stock && !$in_arrivo) : ?>
                <!-- back-in-stock: aggancio per il plugin di notifica (Fase 3) -->
                <span class="cr-btn cr-btn-ghost justify-center !text-xs !py-2.5 mt-1"><?= esc_html__('Avvisami quando torna', 'cardsrift'); ?></span>
            <?php endif; ?>
        </div>
    </div>
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
    $gs  = str_replace('-', '_', (string) $game); // slug → nome campo (es. one-piece → one_piece)
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
        ['name' => 'MTG Foundations · Bundle',     'chip' => 'Magic · IT',     'price' => '109,00'],
        ['name' => 'MTG Bloomburrow · Bundle',     'chip' => 'Magic · EN',     'price' => '44,90'],
        ['name' => 'Shiny Treasure ex · Box',      'chip' => 'Pokémon · JP',   'price' => '74,50'],
        ['name' => 'Charizard ex · Special Art',   'chip' => 'Pokémon · IT',   'price' => '129,00'],
        ['name' => 'Sheoldred · Extended Art',     'chip' => 'Magic · EN',     'price' => '34,90'],
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
            <span class="flex flex-wrap items-center justify-between gap-x-2 gap-y-1 min-h-[22px]">
                <span class="cr-chip"><?= esc_html($d['chip']); ?></span>
                <?php if (!$in_arrivo) : ?><span class="cr-stock cr-stock--ok !text-[11px]"><?= esc_html__('Disponibile', 'cardsrift'); ?></span><?php endif; ?>
            </span>
            <span class="font-metropolis font-semibold text-sm leading-snug text-th-ink min-h-[2.7em]"><?= esc_html($d['name']); ?></span>
            <span class="flex items-center justify-between gap-2 mt-auto min-h-[40px]">
                <?php if ($in_arrivo) : ?>
                    <span class="font-metropolis font-semibold text-xs uppercase tracking-wider text-th-acc"><?= esc_html(sprintf(__('Esce il %s', 'cardsrift'), '26/09')); ?></span>
                <?php else : ?>
                    <span class="cr-price">€ <?= esc_html($d['price']); ?></span>
                    <span class="cr-add"><?= cr_icon_cart(); // phpcs:ignore ?></span>
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
            <span class="flex gap-1 flex-wrap min-h-[16px]">
                <span class="cr-cchip cr-cchip--cond"><?= esc_html($cond); ?></span>
                <span class="cr-cchip">IT</span>
            </span>
            <span class="font-metropolis font-semibold text-xs leading-tight text-th-ink min-h-[2.6em]"><?= esc_html($d['name']); ?></span>
            <span class="flex items-center min-h-[16px]">
                <span class="cr-stock cr-stock--ok !text-[11px]"><?= esc_html__('Disponibile', 'cardsrift'); ?></span>
            </span>
            <span class="flex items-center justify-between gap-1.5 min-h-[32px]">
                <span class="cr-price !text-sm">€ <?= esc_html($d['price']); ?></span>
                <span class="cr-add cr-add--sm"><?= cr_icon_cart(); // phpcs:ignore ?></span>
            </span>
        </span>
    </span>
<?php
}

/**
 * Tasca singola REALE (raccoglitore + listati).
 * Stessa regola della card: niente overlay: prima "Vedi carta" copriva il prezzo
 * proprio mentre lo si stava leggendo. Il comando di aggiunta sta accanto al
 * prezzo, sempre visibile; il resto della tasca porta alla scheda.
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
    <div class="cr-pocket<?= $product->is_in_stock() ? '' : ' cr-pocket--soldout'; ?>" <?= cr_card_fx_attrs($pid); ?>>
        <span class="cr-pocket__well">
            <?= cr_lazy_image($product->get_image('woocommerce_thumbnail')); ?>
        </span>
        <?php // tre righe distinte — chip, nome, disponibilità — invece di chip e
        // disponibilità appaiati: nella tasca stretta si contendevano la stessa riga ?>
        <div class="flex flex-col gap-1 pt-2 px-0.5 pb-1">
            <span class="flex gap-1 flex-wrap min-h-[16px]">
                <?php if ($cond) : ?><span class="cr-cchip cr-cchip--cond"><?= esc_html($cond); ?></span><?php endif; ?>
                <?php if ($lingua) : ?><span class="cr-cchip"><?= esc_html($lingua); ?></span><?php endif; ?>
            </span>
            <a class="cr-card__link font-metropolis font-semibold text-xs leading-tight text-th-ink min-h-[2.6em] no-underline" href="<?= esc_url(get_permalink($pid)); ?>"><?= esc_html($product->get_name()); ?></a>
            <?php // min-h anche da esaurita: senza, le tasche della griglia si sfalsano ?>
            <span class="flex items-center min-h-[16px]">
                <?php if ($product->is_in_stock()) cr_stock_line($product, true); ?>
            </span>
            <span class="flex items-center justify-between gap-1.5 min-h-[32px]">
                <span class="cr-price !text-sm"><?= $product->get_price_html(); ?></span>
                <?php if ($product->is_in_stock()) : ?>
                    <?php cr_add_control($product, 'sm'); ?>
                <?php else : ?>
                    <span class="cr-cchip !bg-transparent !text-th-soft"><?= esc_html__('Esaurita', 'cardsrift'); ?></span>
                <?php endif; ?>
            </span>
        </div>
    </div>
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
