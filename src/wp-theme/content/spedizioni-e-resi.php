<?php

/**
 * MANIFEST SPEDIZIONI E RESI — pagina istituzionale, raggiungibile dal footer (link già presente).
 * Voce: prima persona plurale (voce del negozio). Copy in positivo (mai «cosa non facciamo»).
 * Dati reali allineati a WooCommerce: Italia tariffa fissa CR_SHIP_IT_COST; l'estero (Europa)
 * è dichiarato su richiesta finché le zone EU non sono configurate in WC. Il testo LEGALE
 * completo (recesso/condizioni) vive nelle Condizioni di vendita (iubenda, CR_TERMS_URL): qui
 * solo la versione chiara e onesta. Vedi [[brand-positioning]].
 * Regia temi: dark → light → dark → lilla2 (mai due lilla di fila).
 *
 * ⚠️ Richiede una Pagina WordPress con slug "spedizioni-e-resi" (la carica page.php).
 */

return [

	// 1 · Hero
	[
		'comp'        => 'intro-pagina',
		'tema'        => 'dark',
		'pattern'     => true,
		'eyebrow'     => __('Spedizioni e resi', 'cardsrift'),
		'titolo'      => __("Spedizione tracciata,\nreso semplice.", 'cardsrift'),
		'sottotitolo' => __('Ogni ordine parte protetto e tracciato in 24/48h. E se ci ripensi, hai 14 giorni per il reso — te lo rendiamo facile.', 'cardsrift'),
	],

	// 2 · Spedizione — come arriva il pacco (dati allineati a WooCommerce)
	[
		'comp'    => 'blocco-metodo',
		'tema'    => 'light',
		'eyebrow' => __('La spedizione', 'cardsrift'),
		'titolo'  => __('Come arriva il pacco', 'cardsrift'),
		'voci'    => [
			['label' => __('In Italia', 'cardsrift'),      'testo' => sprintf(__('Spedizione tracciata con Poste Italiane a %s, in tutta Italia. Il pacco parte entro 24/48h dall’ordine.', 'cardsrift'), CR_SHIP_IT_COST)],
			['label' => __('In Europa', 'cardsrift'),      'testo' => __('Spediamo anche nel resto d’Europa: scrivici prima dell’ordine e ti confermiamo tempi e costo per il tuo Paese.', 'cardsrift')],
			['label' => __('Imballaggio', 'cardsrift'),    'testo' => __('Ogni carta protetta secondo il valore: sleeve, top loader o rinforzo, poi busta o scatola rigida.', 'cardsrift')],
			['label' => __('Tracciamento', 'cardsrift'),   'testo' => __('Appena parte ricevi il codice, e segui il pacco fino alla porta.', 'cardsrift')],
		],
	],

	// 3 · Resi e diritto di recesso — versione chiara (il testo legale completo → Condizioni di vendita)
	[
		'comp'    => 'blocco-metodo',
		'tema'    => 'dark',
		'eyebrow' => __('Resi e recesso', 'cardsrift'),
		'titolo'  => __('Se ci ripensi, hai 14 giorni', 'cardsrift'),
		'voci'    => [
			['label' => __('14 giorni per ripensarci', 'cardsrift'), 'testo' => __('Per legge puoi esercitare il diritto di recesso entro 14 giorni dalla consegna. Ti basta scriverci.', 'cardsrift')],
			['label' => __('Come funziona', 'cardsrift'),           'testo' => __('Ci rimandi le carte protette come le hai ricevute; appena arrivano, partiamo col rimborso.', 'cardsrift')],
			['label' => __('Il rimborso', 'cardsrift'),             'testo' => __('Ti torna tutto sullo stesso metodo di pagamento, entro 14 giorni dal reso.', 'cardsrift')],
			['label' => __('La spedizione di ritorno', 'cardsrift'), 'testo' => __('È a tuo carico per il ripensamento; se l’errore è nostro — carta sbagliata o arrivata danneggiata — la copriamo noi.', 'cardsrift')],
		],
		'nota'    => CR_TERMS_URL
			? sprintf(__('Il testo completo — diritto di recesso, garanzie ed eccezioni — è nelle <a href="%s">Condizioni di vendita</a>.', 'cardsrift'), esc_url(CR_TERMS_URL))
			: '',
	],

	// 4 · Chiusura — supporto
	[
		'comp'      => 'claim-progetto',
		'tema'      => 'lilla2',
		'eyebrow'   => __('Un intoppo?', 'cardsrift'),
		'testo'     => __('<b>Qualcosa è andato storto con un ordine?</b> Scrivici e lo risolviamo — un pacco alla volta, come li spediamo.', 'cardsrift'),
		'cta_label' => __('Scrivici su Telegram', 'cardsrift'),
		'cta_url'   => cr_tg_dm(__('Ciao! Ti scrivo dal sito CardsRift — ho una domanda su un ordine.', 'cardsrift')),
	],
];
