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
		'cta_label'   => __('Esplora il catalogo', 'cardsrift'),
		'cta_url'     => '/shop',
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

	// 3 · Griglia "Aggiunti di recente" — automatica (ultimi prodotti)
	[
		'comp'       => 'griglia-prodotti',
		'tema'       => 'light',
		'sorgente'   => 'recenti',
		'colonne'    => 4,
		'eyebrow'    => __('Appena arrivati', 'cardsrift'),
		'titolo'     => __('Aggiunti di recente', 'cardsrift'),
		'link_label' => __('Tutte le novità', 'cardsrift'),
		'link_url'   => '/shop?orderby=date',
	],

	// 4 · Griglia "In offerta" — automatica (prodotti in saldo) · respiro chiaro
	[
		'comp'       => 'griglia-prodotti',
		'tema'       => 'light',
		'sorgente'   => 'offerte',
		'colonne'    => 4,
		'eyebrow'    => __('Occasioni', 'cardsrift'),
		'titolo'     => __('In offerta questa settimana', 'cardsrift'),
		'link_label' => __('Tutte le offerte', 'cardsrift'),
		'link_url'   => '/shop',
	],

	// 5 · Raccoglitore singole — automatico (variabili più recenti)
	[
		'comp'       => 'raccoglitore-singole',
		'tema'       => 'lilla2',
		'eyebrow'    => __('Carte singole', 'cardsrift'),
		'titolo'     => __('Sfoglia il raccoglitore', 'cardsrift'),
		'link_label' => __('Apri il raccoglitore', 'cardsrift'),
		'link_url'   => '/carte-singole',
	],

	// 6 · In arrivo — automatico (prodotti con "data di uscita", ordinati per data)
	[
		'comp'              => 'preordini-uscite',
		'tema'              => 'light',
		'eyebrow'           => __('Prossime uscite', 'cardsrift'),
		'titolo'            => __('In arrivo prossimamente', 'cardsrift'),
		'mostra_calendario' => true,
	],

	// 7 · Bulk banner — % da config (CR_BULK_PCT)
	[
		'comp'              => 'bulk-banner',
		'tema'              => 'lilla',
		'pattern'           => true,
		'percentuale'       => CR_BULK_PCT,
		'percentuale_label' => __('in credito negozio', 'cardsrift'),
		'eyebrow'           => __('Compriamo le tue carte', 'cardsrift'),
		'titolo'            => __('Le tue doppie valgono. Da noi diventano credito.', 'cardsrift'),
		'testo'             => __('È così che ci riforniamo di singole: dalle collezioni di chi, come te, ne ha qualcuna di troppo. Valutazione trasparente sui prezzi di Cardmarket, pagamento tracciato in 24/48h.', 'cardsrift'),
		'cta_label'         => __('Valuta le tue carte', 'cardsrift'),
		'cta_url'           => '/compriamo-le-tue-carte',
		'microtrust'        => [
			['testo' => __('Conteggio in foto e video, sempre', 'cardsrift')],
			['testo' => __('Reso gratis se rifiuti l’offerta', 'cardsrift')],
			['testo' => sprintf(__('Pagamento tracciato, oppure %s in credito', 'cardsrift'), CR_BULK_PCT)],
		],
	],

	// 8 · Claim progetto — numeri da config, testo in codice (il <b> diventa accent)
	[
		'comp'      => 'claim-progetto',
		'tema'      => 'dark',
		'pattern'   => true,
		'eyebrow'   => __('Il progetto', 'cardsrift'),
		'testo'     => __('Siamo collezionisti prima che negozianti. <b>CardsRift nasce per essere il portale che avremmo voluto trovare noi:</b> carte controllate una per una, condizioni oneste, e qualcuno che risponde davvero dall’altra parte. Niente foto finte, niente sorprese all’apertura — <b>solo le carte che cerchi, trattate come se restassero nella nostra collezione.</b>', 'cardsrift'),
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
		'testo'     => __('Restock, drop e chicche li annunciamo lì prima che altrove — con qualche prezzo che sul sito non trovi. Si entra gratis, e senza spam.', 'cardsrift'),
		'cta_label' => __('Entra nel canale', 'cardsrift'),
		'cta_url'   => CR_TELEGRAM_URL,
	],

	// 10 · Newsletter — shortcode da config (vuoto = form placeholder) · respiro lilla2
	[
		'comp'           => 'newsletter-box',
		'tema'           => 'lilla2',
		'pattern'        => true,
		'eyebrow'        => __('Newsletter', 'cardsrift'),
		'titolo'         => __('−5% sul tuo primo ordine', 'cardsrift'),
		'testo'          => __('Una mail solo quando arriva qualcosa che vale: le uscite, i restock, e lo sconto di benvenuto appena ti iscrivi.', 'cardsrift'),
		'micro'          => __('Niente spam. Ti cancelli quando vuoi, con un clic.', 'cardsrift'),
		'form_shortcode' => CR_NEWSLETTER_SHORTCODE,
	],
];
