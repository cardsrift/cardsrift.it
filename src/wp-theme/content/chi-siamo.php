<?php

/**
 * MANIFEST CHI SIAMO — struttura + copy in codice (vedi content/home.php per il pattern).
 * Voce: prima persona, quieta, da collezionista (docs/design-system.md §1). Niente città
 * (scelta di riservatezza). Il nome "Marco" compare UNA volta sola (sezione "La storia").
 * Àncora di fiducia = reputazione Cardmarket (sito nuovo, storico venditore verificabile).
 * Regia temi: dark → light → dark → light → dark → lilla2 → dark (mai due lilla di fila).
 *
 * ⚠️ Richiede una Pagina WordPress con slug "chi-siamo" (la carica page.php via cr_render_page).
 */

return [

	// 1 · Hero — intestazione editoriale (niente città; keyword per la SEO)
	[
		'comp'        => 'intro-pagina',
		'tema'        => 'dark',
		'pattern'     => true,
		'eyebrow'     => __('Chi siamo', 'cardsrift'),
		'titolo'      => __('Un negozio di carte collezionabili tenuto da chi le colleziona', 'cardsrift'),
		'sottotitolo' => __('CardsRift è la mia collezione diventata negozio: Pokémon e Magic, sealed e singole, controllate una a una e spedite come vorrei riceverle io. Il sito è nuovo, ma le carte le spedisco già da un po’ — con il 100% di valutazioni positive su Cardmarket.', 'cardsrift'),
	],

	// 2 · La storia — l'unico punto in cui compare il nome
	[
		'comp'    => 'blocco-testo',
		'tema'    => 'light',
		'eyebrow' => __('La storia', 'cardsrift'),
		'titolo'  => __('Come è nato CardsRift', 'cardsrift'),
		'testo'   => __("Mi chiamo <b>Marco</b> e colleziono carte da molto prima di venderle. CardsRift nasce da lì: dal voler trasformare una passione in un mestiere, e avere un motivo in più per continuare a raccontarla a chi la condivide.\n\nHo iniziato dove iniziano quasi tutti i collezionisti seri, su Cardmarket. Cento e più spedizioni dopo, con ogni valutazione tornata positiva, ho aperto questo portale per fare le cose fino in fondo a modo mio: foto reali, condizioni dette come stanno, e qualcuno che risponde davvero dall’altra parte.\n\nSono <b>una persona sola, non un magazzino.</b> Ogni carta che parte è passata dalle mie mani.", 'cardsrift'),
	],

	// 3 · Come lavoro — cosa succede davvero a una carta (concreto, non un elenco di virtù)
	[
		'comp'    => 'blocco-metodo',
		'tema'    => 'dark',
		'eyebrow' => __('In concreto', 'cardsrift'),
		'titolo'  => __('Cosa succede a una carta prima di arrivarti', 'cardsrift'),
		'voci'    => [
			['label' => __('La controllo', 'cardsrift'), 'testo' => __('Apro, verifico e giudico le condizioni sulla scala di Cardmarket — da Mint a Poor — sempre per difetto. Se è Light Played, nella scheda leggi Light Played.', 'cardsrift')],
			['label' => __('La fotografo', 'cardsrift'), 'testo' => __('Se ha valore la scansiono fronte e retro: quello che vedi nella scheda è esattamente il pezzo che ricevi.', 'cardsrift')],
			['label' => __('La imballo', 'cardsrift'),   'testo' => __('Sleeve, top loader o rinforzo secondo il valore, poi busta o scatola rigida. Parte tracciata in 24/48h — come vorrei riceverla io.', 'cardsrift')],
		],
	],

	// 4 · Reputazione — la prova Cardmarket (numeri veri + profilo verificabile)
	[
		'comp'      => 'claim-progetto',
		'tema'      => 'light',
		'eyebrow'   => __('La reputazione conta', 'cardsrift'),
		'testo'     => __('<b>La fiducia si costruisce un pacco alla volta.</b> Le stesse carte che trovi qui le vendo su Cardmarket — il posto dove i collezionisti guardano i numeri e si fidano dei fatti. Il portale è nuovo; la cura con cui spedisco è quella di sempre.', 'cardsrift'),
		'stats'     => [
			['valore' => CR_CM_POSITIVE, 'etichetta' => __('valutazioni positive', 'cardsrift')],
			['valore' => CR_CM_SALES,    'etichetta' => __('vendite su Cardmarket', 'cardsrift')],
			['valore' => CR_SHOP_SINCE,  'etichetta' => __('venditore dal', 'cardsrift')],
		],
		'cta_label' => __('Guarda il profilo su Cardmarket', 'cardsrift'),
		'cta_url'   => CR_CM_URL,
	],

	// 5 · FAQ — smonta le obiezioni + SEO a coda lunga (emette schema FAQPage)
	[
		'comp'    => 'blocco-faq',
		'tema'    => 'dark',
		'eyebrow' => __('Domande frequenti', 'cardsrift'),
		'titolo'  => __('Quello che di solito mi chiedono', 'cardsrift'),
		'faq'     => [
			['domanda' => __('Le carte sono originali?', 'cardsrift'),               'risposta' => __('Sempre. Tratto solo prodotto autentico: sealed e singole verificate una a una. Le carte di valore le trovi fotografate fronte e retro nella scheda.', 'cardsrift')],
			['domanda' => __('Come giudichi le condizioni?', 'cardsrift'),           'risposta' => __('Uso la scala di Cardmarket — da Mint a Poor — applicata per difetto. Se una carta è Light Played la chiamo così. Trovi ogni grado spiegato nella <a href="/guida-alle-condizioni">guida alle condizioni</a>.', 'cardsrift')],
			['domanda' => __('Come spedisci, e in quanto tempo?', 'cardsrift'),       'risposta' => __('Spedizione tracciata in 24/48h in tutta Italia. Ogni carta parte imballata secondo il suo valore: sleeve, top loader o rinforzo, poi busta o scatola rigida.', 'cardsrift')],
			['domanda' => __('Vendi solo Pokémon e Magic?', 'cardsrift'),  'risposta' => __('Per ora sì: sono i giochi che conosco meglio e su cui posso garantire davvero. Gli altri arriveranno con calma.', 'cardsrift')],
			['domanda' => __('Posso vendervi le mie carte?', 'cardsrift'),           'risposta' => __('Sì. Le valuto sui prezzi correnti di Cardmarket e ti faccio un’offerta. Trovi tutti i dettagli nella <a href="/compriamo-le-tue-carte">pagina dedicata</a>.', 'cardsrift')],
			['domanda' => __('Chi c’è dietro CardsRift?', 'cardsrift'),              'risposta' => __('Una persona sola, non un magazzino: un collezionista che apre, controlla e imballa ogni ordine di persona.', 'cardsrift')],
		],
	],

	// 6 · Vendi le tue carte — richiamo SOLO evocativo (niente numeri/prezzi qui) → pagina dedicata
	[
		'comp'      => 'claim-progetto',
		'tema'      => 'lilla2',
		'eyebrow'   => __('Anche il contrario', 'cardsrift'),
		'testo'     => __('<b>Hai carte ferme in un cassetto?</b> Da me trovano un’altra collezione dove stare. Le valuto con calma, ti dico tutto prima — e se cambi idea, ci siamo salutati bene lo stesso.', 'cardsrift'),
		'cta_label' => __('Come vendere le tue carte', 'cardsrift'),
		'cta_url'   => '/compriamo-le-tue-carte',
	],

	// 7 · Community — chiusura onesta (canale piccolo = invito, non vanto)
	[
		'comp'      => 'banner-telegram',
		'tema'      => 'dark',
		'eyebrow'   => __('Community', 'cardsrift'),
		'titolo'    => __('Siamo agli inizi. Entra dal principio.', 'cardsrift'),
		'testo'     => __('Il canale Telegram è ancora piccolo, e va bene così: restock, nuove uscite e qualche chicca prima che altrove. Si entra gratis — e da qui cresciamo insieme.', 'cardsrift'),
		'cta_label' => __('Entra nel canale', 'cardsrift'),
		'cta_url'   => CR_TELEGRAM_URL,
	],
];
