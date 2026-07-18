<?php

/**
 * CONFIG — valori reali a bassa frequenza del sito.
 *
 * Questi NON sono copy (che vive nei manifest content/*.php, in __()) né dati DB
 * (che stanno in ACF): sono parametri/numeri che cambiano di rado. Si aggiornano
 * qui con un edit + deploy. Vengono iniettati nelle sezioni dai manifest.
 *
 * ⚠️ Ancora SEGNAPOSTO da confermare (vedi docs/rework-fase-1.md § Domande aperte):
 * shortcode newsletter.
 */

// Buylist "Compriamo le tue carte" — tariffe reali (pagamento SOLO contanti/PayPal, no credito negozio).
if (!defined('CR_BUY_MIN_PCT'))         define('CR_BUY_MIN_PCT', '70%');        // minimo su singole e sealed (sale con la richiesta)
if (!defined('CR_BUY_BULK_MTG'))        define('CR_BUY_BULK_MTG', '5 €/kg');    // bulk Magic, comuni/non comuni sfuse a peso
if (!defined('CR_BUY_BULK_POKEMON'))    define('CR_BUY_BULK_POKEMON', '6 €/kg'); // bulk Pokémon, comuni/non comuni sfuse a peso

// Spedizioni & resi — allineare a WooCommerce → Impostazioni → Spedizione.
if (!defined('CR_SHIP_IT_COST'))        define('CR_SHIP_IT_COST', '10,90 €');   // tariffa fissa Italia, Poste Italiane (⚠️ allinea a WooCommerce → Spedizione, ora 8,90)
if (!defined('CR_TERMS_URL'))           define('CR_TERMS_URL', '');             // Condizioni di vendita (iubenda) — SEGNAPOSTO da generare

// Reputazione Cardmarket — àncora di fiducia: sito nuovo, ma storico venditore verificabile.
if (!defined('CR_CM_URL'))              define('CR_CM_URL', 'https://www.cardmarket.com/it/Magic/Users/CardsRift'); // profilo pubblico
if (!defined('CR_CM_POSITIVE'))         define('CR_CM_POSITIVE', '100%');       // % valutazioni positive
if (!defined('CR_CM_SALES'))            define('CR_CM_SALES', '100+');          // vendite totali su Cardmarket
if (!defined('CR_SHOP_SINCE'))          define('CR_SHOP_SINCE', '2025');        // venditore su Cardmarket dal
if (!defined('CR_TELEGRAM_URL'))        define('CR_TELEGRAM_URL', 'https://t.me/+4XSpVIOPA3FjMmFk'); // GRUPPO community (link d'invito) — per "unisciti"
if (!defined('CR_TELEGRAM_DM'))         define('CR_TELEGRAM_DM', 'https://t.me/khewro');              // DM privato del titolare — per contatto/buylist (usa cr_tg_dm() per il testo precompilato)
if (!defined('CR_NEWSLETTER_SHORTCODE')) define('CR_NEWSLETTER_SHORTCODE', ''); // shortcode provider (vuoto = form placeholder)

// Sconto di benvenuto alla registrazione (al posto della newsletter): il codice va creato in
// WooCommerce (vedi docs/go-live.md); il tema lo mostra soltanto (email nuovo account + home).
if (!defined('CR_WELCOME_COUPON'))      define('CR_WELCOME_COUPON', 'BENVENUTO5'); // codice coupon fisso -5%
if (!defined('CR_WELCOME_PCT'))         define('CR_WELCOME_PCT', '5%');            // entità sconto (solo display)

// Tema dell'header: 'dark' | 'light' — intercambiabile con una riga (usa i token del design system).
if (!defined('CR_HEADER_THEME'))        define('CR_HEADER_THEME', 'light');

// Placeholder prodotti nelle sezioni VUOTE — aiuto di anteprima per vedere la home completa
// prima di avere il catalogo. Riempie SOLO le sezioni senza prodotti: appena fai il data
// entry (prodotti / offerte / data_uscita) i placeholder spariscono da soli, mostra il reale.
// ⚠️ METTERE false PRIMA DEL GO-LIVE (o quando il catalogo è pronto).
if (!defined('CR_PLACEHOLDER'))         define('CR_PLACEHOLDER', true);

// Categorie prodotto "di tipo" (Singole/Sealed/Accessori): NON sono giochi. Servono al tema per
// distinguere il termine gioco (Pokémon/Magic/One Piece) dal tipo — senza dipendere dal plugin sync.
if (!defined('CR_CAT_TIPO'))            define('CR_CAT_TIPO', ['singole', 'sealed', 'accessori']);
