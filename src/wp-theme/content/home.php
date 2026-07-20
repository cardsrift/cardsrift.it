<?php

/**
 * MANIFEST HOMEPAGE — struttura + copy in codice (regia "Notte").
 * Fonte copy: docs/copy-rework.md · voce/tono: docs/design-system.md §1.
 *
 * Ogni voce è una sezione: 'comp' (cartella in global-components), 'tema', il copy
 * (in __() → traducibile), e 'edit' SOLO dove serve un dato dal database
 * (i campi ACF vengono generati da qui, vedi includes/page-builder.php).
 * Sezioni senza 'edit' = automatiche o interamente da codice: niente backend.
 *
 * ⚠️ Non è un file eseguibile "a mano": lo carica l'engine (cr_render_page / cr_register_builder_fields).
 */

return [

	// 1 · Hero vetrina — l'UNICA sezione con un campo da backend (3 prodotti in vetrina)
	[
		'comp'        => 'hero-vetrina',
		'label'       => 'Hero vetrina',
		'tema'        => 'dark',
		'eyebrow'     => __('Il tuo portale per il collezionismo', 'cardsrift'),
		'titolo'      => __("Le carte che cerchi,\nscelte una per una.", 'cardsrift'),
		'sottotitolo' => __('Sealed, singole e accessori per Pokémon, One Piece e Magic. Le controlliamo una per una e le imballiamo come se restassero da noi.', 'cardsrift'),
		'cta_label'   => __('Scopri i giochi', 'cardsrift'),
		'cta_url'     => '#giochi', // ancora alle righe per-gioco qui sotto (niente catalogo globale)
		'cta2_label'  => __('Come lavoriamo', 'cardsrift'),
		'cta2_url'    => '/chi-siamo',
		'trust'       => [
			['evidenzia' => CR_CM_POSITIVE, 'testo' => __('valutazioni positive su Cardmarket', 'cardsrift')],
			['evidenzia' => '24/48h',               'testo' => __('spedizione tracciata', 'cardsrift')],
			['evidenzia' => __('Foto reali', 'cardsrift'), 'testo' => __('di ogni singola oltre i 50€', 'cardsrift')],
		],
		'edit'        => [
			'vetrina' => ['type' => 'post_object', 'post_type' => 'product', 'count' => 3, 'label' => __('Prodotti in vetrina (3)', 'cardsrift')],
		],
	],

	// 2 · Ticker — voci automatiche (2 brand + 2 dal negozio): nessun campo, nessun copy
	['comp' => 'ticker-info', 'tema' => 'dark'],

	// 3-5 · VETRINE PER-GIOCO — righe mono-gioco (mai miste); ogni riga → landing del gioco.
	// Le griglie escludono le singole (quelle si sfogliano nella landing → "Carte singole").
	[
		'comp'       => 'griglia-prodotti',
		'anchor'     => 'giochi',
		'tema'       => 'light',
		'sorgente'   => 'recenti',
		'gioco'      => 'magic',
		'colonne'    => 4,
		'eyebrow'    => __('Magic: The Gathering', 'cardsrift'),
		'titolo'     => __('Novità Magic: The Gathering', 'cardsrift'),
		'link_label' => __('Entra', 'cardsrift'),
		'link_url'   => '/magic/',
	],
	[
		'comp'       => 'griglia-prodotti',
		'tema'       => 'dark',
		'sorgente'   => 'recenti',
		'gioco'      => 'pokemon',
		'colonne'    => 4,
		'eyebrow'    => __('Pokémon', 'cardsrift'),
		'titolo'     => __('Novità Pokémon', 'cardsrift'),
		'link_label' => __('Entra', 'cardsrift'),
		'link_url'   => '/pokemon/',
	],
	[
		'comp'       => 'griglia-prodotti',
		'tema'       => 'light',
		'sorgente'   => 'recenti',
		'gioco'      => 'one-piece',
		'colonne'    => 4,
		'eyebrow'    => __('One Piece', 'cardsrift'),
		'titolo'     => __('Novità One Piece', 'cardsrift'),
		'link_label' => __('Entra', 'cardsrift'),
		'link_url'   => '/one-piece/',
	],

	// 6 · In arrivo — automatico (prodotti con "data di uscita", ordinati per data)
	[
		'comp'              => 'preordini-uscite',
		'tema'              => 'light',
		'eyebrow'           => __('Prossime uscite', 'cardsrift'),
		'titolo'            => __('In arrivo prossimamente', 'cardsrift'),
		'mostra_calendario' => true,
	],

	// 7 · Bulk banner — tariffa reale da config (CR_BUY_MIN_PCT), pagamento cash/PayPal
	[
		'comp'              => 'bulk-banner',
		'tema'              => 'lilla',
		'pattern'           => true,
		'percentuale'       => CR_BUY_MIN_PCT,
		'percentuale_label' => __('minimo del valore Cardmarket', 'cardsrift'),
		'eyebrow'           => __('Compriamo le tue carte', 'cardsrift'),
		'titolo'            => __('Le tue doppie valgono. Le valutiamo noi.', 'cardsrift'),
		'testo'             => __('È così che ci riforniamo di singole: dalle collezioni di chi, come te, ne ha qualcuna di troppo. Valutazione trasparente sui prezzi di Cardmarket, pagamento tracciato in contanti o PayPal entro 24/48h.', 'cardsrift'),
		'cta_label'         => __('Valuta le tue carte', 'cardsrift'),
		'cta_url'           => '/compriamo-le-tue-carte',
		'microtrust'        => [
			['testo' => __('Conteggio in foto e video, sempre', 'cardsrift')],
			['testo' => __('Reso gratis se rifiuti l’offerta', 'cardsrift')],
			['testo' => __('Pagamento tracciato in 24/48h', 'cardsrift')],
		],
	],

	// 8 · Claim progetto — numeri da config, testo in codice (il <b> diventa accent)
	[
		'comp'      => 'claim-progetto',
		'tema'      => 'dark',
		'pattern'   => true,
		'eyebrow'   => __('Il progetto', 'cardsrift'),
		'testo'     => __('Siamo collezionisti prima che negozianti. <b>CardsRift nasce per essere il portale che avremmo voluto trovare noi:</b> carte controllate una per una, condizioni oneste, foto vere del pezzo che ricevi, e qualcuno che risponde davvero dall’altra parte — <b>le carte che cerchi, trattate come se restassero nella nostra collezione.</b>', 'cardsrift'),
		'stats'     => [
			['valore' => CR_CM_POSITIVE,   'etichetta' => __('valutazioni positive', 'cardsrift')],
			['valore' => CR_CM_SALES,        'etichetta' => __('vendite su Cardmarket', 'cardsrift')],
			['valore' => CR_SHOP_SINCE,            'etichetta' => __('venditore dal', 'cardsrift')],
		],
		'cta_label' => __('Chi siamo', 'cardsrift'),
		'cta_url'   => '/chi-siamo',
	],

	// 9 · Banner Telegram — URL da config (CR_TELEGRAM_URL)
	[
		'comp'      => 'banner-telegram',
		'tema'      => 'dark',
		'eyebrow'   => __('Community', 'cardsrift'),
		'titolo'    => __('Il canale dove succede prima', 'cardsrift'),
		'testo'     => __('Restock, drop e chicche li annunciamo lì prima che altrove — con qualche prezzo riservato a chi è nel canale. Si entra gratis.', 'cardsrift'),
		'cta_label' => __('Entra nel canale', 'cardsrift'),
		'cta_url'   => CR_TELEGRAM_URL,
	],

	// 10 · Invito registrazione — sconto di benvenuto (niente provider newsletter) · respiro lilla2
	[
		'comp'      => 'newsletter-box',
		'tema'      => 'lilla2',
		'pattern'   => true,
		'eyebrow'   => __('Benvenuto', 'cardsrift'),
		'titolo'    => sprintf(__('Il primo ordine è −%s', 'cardsrift'), CR_WELCOME_PCT),
		'testo'     => __('Crea un account e ti mandiamo il codice di benvenuto via email. Al prossimo acquisto sei anche più veloce.', 'cardsrift'),
		'cta_label' => __('Crea un account', 'cardsrift'),
		'cta_url'   => '/my-account',
		'micro'     => __('Basta un minuto e una email.', 'cardsrift'),
	],
];
