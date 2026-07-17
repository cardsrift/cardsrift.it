<?php

/**
 * CONFIG — valori reali a bassa frequenza del sito.
 *
 * Questi NON sono copy (che vive nei manifest content/*.php, in __()) né dati DB
 * (che stanno in ACF): sono parametri/numeri che cambiano di rado. Si aggiornano
 * qui con un edit + deploy. Vengono iniettati nelle sezioni dai manifest.
 *
 * ⚠️ Alcuni sono SEGNAPOSTO da confermare (vedi docs/rework-fase-1.md § Domande aperte):
 * URL Telegram, shortcode newsletter.
 */

if (!defined('CR_BULK_PCT'))            define('CR_BULK_PCT', '+10%');          // bonus credito buylist (min. consigliato +10%)

// Reputazione Cardmarket — àncora di fiducia: sito nuovo, ma storico venditore verificabile.
if (!defined('CR_CM_URL'))              define('CR_CM_URL', 'https://www.cardmarket.com/it/Magic/Users/CardsRift'); // profilo pubblico
if (!defined('CR_CM_POSITIVE'))         define('CR_CM_POSITIVE', '100%');       // % valutazioni positive
if (!defined('CR_CM_SALES'))            define('CR_CM_SALES', '100+');          // vendite totali su Cardmarket
if (!defined('CR_SHOP_SINCE'))          define('CR_SHOP_SINCE', '2025');        // venditore su Cardmarket dal
if (!defined('CR_TELEGRAM_URL'))        define('CR_TELEGRAM_URL', 'https://t.me/cardsrift'); // canale — SEGNAPOSTO
if (!defined('CR_NEWSLETTER_SHORTCODE')) define('CR_NEWSLETTER_SHORTCODE', ''); // shortcode provider (vuoto = form placeholder)

// Tema dell'header: 'dark' | 'light' — intercambiabile con una riga (usa i token del design system).
if (!defined('CR_HEADER_THEME'))        define('CR_HEADER_THEME', 'light');

// Placeholder prodotti nelle sezioni VUOTE — aiuto di anteprima per vedere la home completa
// prima di avere il catalogo. Riempie SOLO le sezioni senza prodotti: appena fai il data
// entry (prodotti / offerte / data_uscita) i placeholder spariscono da soli, mostra il reale.
// ⚠️ METTERE false PRIMA DEL GO-LIVE (o quando il catalogo è pronto).
if (!defined('CR_PLACEHOLDER'))         define('CR_PLACEHOLDER', true);
