<?php

/**
 * MANIFEST GUIDA ALLE CONDIZIONI — pagina istituzionale/SEO, raggiungibile SOLO dal footer
 * (link /guida-alle-condizioni già presente). Fuori dal menu principale.
 * Voce: prima persona plurale (voce del negozio). Copy in positivo (mai «cosa non facciamo»).
 * Scala: quella UFFICIALE di Cardmarket, 7 gradi (MT · NM · EX · GD · LP · PL · PO) — allineata
 * a chi-siamo e agli attributi prodotto. Vedi [[brand-positioning]].
 * Regia temi: dark → light → dark → lilla2 (mai due lilla di fila).
 *
 * ⚠️ Richiede una Pagina WordPress con slug "guida-alle-condizioni" (la carica page.php).
 */

return [

	// 1 · Hero — cos'è la guida e come applichiamo la scala
	[
		'comp'        => 'intro-pagina',
		'tema'        => 'dark',
		'pattern'     => true,
		'eyebrow'     => __('Guida alle condizioni', 'cardsrift'),
		'titolo'      => __("Come classifichiamo\nle condizioni", 'cardsrift'),
		'sottotitolo' => __('La condizione conta quanto la carta stessa. Usiamo la scala ufficiale di Cardmarket — sette gradi, dalla Mint alla Poor — e la applichiamo sempre per difetto: nel dubbio, scendiamo di un gradino.', 'cardsrift'),
	],

	// 2 · La scala — i 7 gradi Cardmarket, dal migliore al peggiore (blocco-metodo)
	[
		'comp'    => 'blocco-metodo',
		'tema'    => 'light',
		'eyebrow' => __('La scala', 'cardsrift'),
		'titolo'  => __('Dalla Mint alla Poor', 'cardsrift'),
		'intro'   => __('Gli stessi sette gradi ufficiali di Cardmarket, il mercato di riferimento. Li leggi in ogni scheda prodotto, accanto al prezzo.', 'cardsrift'),
		'voci'    => [
			['label' => __('Mint · MT', 'cardsrift'),         'testo' => __('Perfetta, come appena uscita dalla busta: angoli netti, superficie pulita, immacolata anche vista da vicino.', 'cardsrift')],
			['label' => __('Near Mint · NM', 'cardsrift'),    'testo' => __('Quasi perfetta: al massimo un’impercettibile imperfezione, visibile solo a un esame attento. È la condizione più richiesta.', 'cardsrift')],
			['label' => __('Excellent · EX', 'cardsrift'),    'testo' => __('Segni d’uso lievi: qualche micro-graffio o un accenno di consumo sui bordi, percepibili da vicino.', 'cardsrift')],
			['label' => __('Good · GD', 'cardsrift'),         'testo' => __('Uso evidente ma carta integra: graffi, leggero sbiancamento dei bordi, a volte una piega minima.', 'cardsrift')],
			['label' => __('Light Played · LP', 'cardsrift'), 'testo' => __('Uso marcato e ben visibile: bordi sbiancati, graffi sulla superficie, qualche piega leggera.', 'cardsrift')],
			['label' => __('Played · PL', 'cardsrift'),       'testo' => __('Molto giocata: graffi profondi, pieghe evidenti, sbiancamento diffuso, piccoli difetti strutturali.', 'cardsrift')],
			['label' => __('Poor · PO', 'cardsrift'),         'testo' => __('Fortemente segnata: pieghe importanti, strappi, aloni o scritte. Resta riconoscibile e giocabile in bustina.', 'cardsrift')],
		],
	],

	// 3 · Il nostro metodo — come applichiamo la scala (trust)
	[
		'comp'    => 'blocco-testo',
		'tema'    => 'dark',
		'eyebrow' => __('Il nostro metodo', 'cardsrift'),
		'titolo'  => __('Nel dubbio, un gradino sotto', 'cardsrift'),
		'testo'   => __('Giudichiamo <b>alla luce piena</b>, fronte e retro, prima di assegnare un grado. Quando una carta è a cavallo tra due condizioni scegliamo la più bassa: <b>meglio una sorpresa in positivo all’apertura.</b> Le carte di valore le trovi <b>fotografate davvero</b> nella scheda, così il grado lo verifichi con i tuoi occhi.', 'cardsrift'),
	],

	// 4 · Chiusura — rimando al catalogo
	[
		'comp'      => 'claim-progetto',
		'tema'      => 'lilla2',
		'eyebrow'   => __('In pratica', 'cardsrift'),
		'testo'     => __('<b>Quello che vedi nella scheda è quello che ricevi.</b> Ogni carta di valore è fotografata fronte e retro, con la sua condizione scritta accanto.', 'cardsrift'),
		'cta_label' => __('Sfoglia il catalogo', 'cardsrift'),
		// ⚠️ Non /shop: quell'URL cr_guard_no_mixed() lo rimanda sempre alla home (niente
		// catalogo globale). Si punta alle righe per-gioco, come fa la CTA dell'hero.
		'cta_url'   => '/#giochi',
	],
];
