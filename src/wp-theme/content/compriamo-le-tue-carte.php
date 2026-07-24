<?php

/**
 * MANIFEST COMPRIAMO LE TUE CARTE — la buylist. Struttura + copy in codice (vedi home.php).
 * Voce: prima persona PLURALE (voce del negozio), quieta — come la home. L'"io" è riservato
 * alla sola pagina del fondatore (chi-siamo). Àncora di fiducia = trasparenza + prezzi
 * Cardmarket. Pagamento contanti/PayPal. Contatto = Telegram (DM del titolare via cr_tg_dm,
 * con messaggio precompilato). Tariffe reali in config (CR_BUY_*).
 * REGOLA COPY: si dice solo cosa FACCIAMO — mai «non facciamo X»; ciò che non è scritto
 * non lo facciamo. Frasi in positivo, negazioni omesse (vedi [[brand-positioning]]).
 * Regia temi: dark → light → dark → light → dark → lilla2 (mai due lilla di fila).
 *
 * ⚠️ Richiede una Pagina WordPress con slug "compriamo-le-tue-carte" (la carica page.php).
 */

return [

	// 1 · Hero — invito diretto, CTA sul DM Telegram (messaggio precompilato)
	[
		'comp'        => 'intro-pagina',
		'tema'        => 'dark',
		'pattern'     => true,
		'eyebrow'     => __('Compriamo le tue carte', 'cardsrift'),
		'titolo'      => __("Hai carte da vendere?\nParliamone.", 'cardsrift'),
		'sottotitolo' => __('Valutiamo Pokémon e Magic sui prezzi correnti di Cardmarket — singole, sealed e bulk. Ti diciamo tutto prima, per iscritto, e paghiamo tracciato in 24/48h. Cambi idea? Ti rispediamo tutto a nostre spese.', 'cardsrift'),
		'cta_label'   => __('Scrivici su Telegram', 'cardsrift'),
		'cta_url'     => cr_tg_dm(__('Ciao! Ti scrivo dal sito CardsRift — avrei delle carte da vendere.', 'cardsrift')),
	],

	// 2 · Come funziona — 3 passi numerati
	[
		'comp'     => 'blocco-valori',
		'tema'     => 'light',
		'numerato' => true,
		'colonne'  => 3,
		'eyebrow'  => __('Come funziona', 'cardsrift'),
		'titolo'   => __('Tre passi, zero sorprese', 'cardsrift'),
		'valori'   => [
			['titolo' => __('Mandaci la lista', 'cardsrift'), 'testo' => __('Due foto o un file con set e condizioni: bastano per una prima stima. Ci accordiamo su Telegram.', 'cardsrift')],
			['titolo' => __('Ricevi l’offerta', 'cardsrift'), 'testo' => __('Valutiamo sui prezzi correnti di Cardmarket e ti scriviamo, carta per carta o a blocchi. Decidi tu con calma.', 'cardsrift')],
			['titolo' => __('Scegli e incassi', 'cardsrift'), 'testo' => __('Ci mandi le carte tracciate; appena arrivano e il conteggio combacia, paghiamo in contanti o PayPal entro 24/48h.', 'cardsrift')],
		],
	],

	// 3 · Quanto paghiamo — tariffe reali (listino trasparente, numeri da config)
	[
		'comp'    => 'blocco-tassi',
		'tema'    => 'dark',
		'eyebrow' => __('Quanto paghiamo', 'cardsrift'),
		'titolo'  => __('Percentuali chiare, sul valore reale', 'cardsrift'),
		'intro'   => __('Lavoriamo in percentuale sul prezzo di Cardmarket — quello pubblico, che puoi controllare anche tu. Su singole e sealed partiamo da qui e saliamo con la richiesta; le comuni sfuse le pesiamo.', 'cardsrift'),
		'voci'    => [
			['tipo' => __('Singole', 'cardsrift'),      'nota' => __('in base a condizione e lingua', 'cardsrift'),      'valore' => sprintf(__('da %s', 'cardsrift'), CR_BUY_MIN_PCT)],
			['tipo' => __('Sealed', 'cardsrift'),       'nota' => __('sigillato, sul valore di mercato', 'cardsrift'),   'valore' => sprintf(__('da %s', 'cardsrift'), CR_BUY_MIN_PCT)],
			['tipo' => __('Bulk Magic', 'cardsrift'),   'nota' => __('comuni e non comuni sfuse, a peso', 'cardsrift'),  'valore' => CR_BUY_BULK_MTG],
			['tipo' => __('Bulk Pokémon', 'cardsrift'), 'nota' => __('comuni e non comuni sfuse, a peso', 'cardsrift'),  'valore' => CR_BUY_BULK_POKEMON],
		],
		'nota'    => __('Percentuali sul prezzo Cardmarket del giorno della valutazione. Paghiamo in contanti o PayPal, tracciato. Acquistiamo da privati con regolare ricevuta (regime del margine).', 'cardsrift'),
	],

	// 4 · Le nostre garanzie — costruisce la fiducia (blocco-metodo pacato)
	[
		'comp'    => 'blocco-metodo',
		'tema'    => 'light',
		'eyebrow' => __('Le nostre garanzie', 'cardsrift'),
		'titolo'  => __('Perché puoi fidarti a scatola chiusa', 'cardsrift'),
		'voci'    => [
			['label' => __('Contiamo tutto in foto e video', 'cardsrift'), 'testo' => __('Prima di chiudere l’offerta documentiamo il conteggio: sai esattamente su cosa ci stiamo accordando.', 'cardsrift')],
			['label' => __('Te lo mettiamo per iscritto', 'cardsrift'),    'testo' => __('La valutazione te la mandiamo scritta: il prezzo che leggi è quello che incassi.', 'cardsrift')],
			['label' => __('Se rifiuti, reso gratis', 'cardsrift'),        'testo' => __('Decidi con calma: se cambi idea, ti rispediamo le carte a nostre spese.', 'cardsrift')],
			['label' => __('Pagamento tracciato', 'cardsrift'),            'testo' => __('Contanti o PayPal entro 24/48h dall’arrivo. Acquistiamo da privati con regolare ricevuta (regime del margine).', 'cardsrift')],
		],
	],

	// 5 · FAQ — obiezioni + SEO a coda lunga (emette schema FAQPage)
	[
		'comp'    => 'blocco-faq',
		'tema'    => 'dark',
		'eyebrow' => __('Domande frequenti', 'cardsrift'),
		'titolo'  => __('Prima di scriverci', 'cardsrift'),
		'faq'     => [
			['domanda' => __('Come valutate le mie carte?', 'cardsrift'),        'risposta' => sprintf(__('In percentuale sul prezzo di Cardmarket del giorno: da %s per singole e sealed, in base a condizione e lingua. Il bulk sfuso lo pesiamo.', 'cardsrift'), CR_BUY_MIN_PCT)],
			['domanda' => __('Come mi pagate?', 'cardsrift'),                    'risposta' => __('Contanti o PayPal, tracciato, entro 24/48h da quando riceviamo le carte e il conteggio combacia.', 'cardsrift')],
			['domanda' => __('Comprate anche il bulk?', 'cardsrift'),           'risposta' => sprintf(__('Sì: comuni e non comuni sfuse a peso — %s per Magic, %s per Pokémon. Per sealed e singole di valore andiamo a percentuale.', 'cardsrift'), CR_BUY_BULK_MTG, CR_BUY_BULK_POKEMON)],
			['domanda' => __('E se l’offerta non mi convince?', 'cardsrift'),    'risposta' => __('Ti rispediamo le carte a nostre spese, spedizione di ritorno inclusa. Resti libero di decidere fino all’ultimo.', 'cardsrift')],
			['domanda' => __('Comprate da tutta Italia?', 'cardsrift'),          'risposta' => __('Sì, da tutta Italia. Ci accordiamo su Telegram, ci mandi le carte tracciate e chiudiamo appena arrivano.', 'cardsrift')],
			['domanda' => __('Devo emettere fattura o ricevuta?', 'cardsrift'),  'risposta' => __('Alla parte fiscale pensiamo noi: acquistiamo da privati con regolare ricevuta d’acquisto (regime del margine). A te basta spedire le carte.', 'cardsrift')],
		],
	],

	// 6 · Chiusura — CTA finale sul DM Telegram (niente numeri, solo l'invito)
	[
		'comp'      => 'claim-progetto',
		'tema'      => 'lilla2',
		'eyebrow'   => __('Facciamo due conti', 'cardsrift'),
		'testo'     => __('<b>Una collezione intera, uno scatolone o solo quelle tre carte di troppo?</b> Scrivici: la valutazione è gratis, la decisione resta tua.', 'cardsrift'),
		'cta_label' => __('Scrivici su Telegram', 'cardsrift'),
		'cta_url'   => cr_tg_dm(__('Ciao! Ti scrivo dal sito CardsRift — avrei delle carte da vendere.', 'cardsrift')),
	],
];
