<?php

/**
 * MANIFEST FAQ — pagina istituzionale (l'ultima), raggiungibile dal footer ("Domande frequenti").
 * FAQ generale del sito: aggrega le domande più comuni (prodotti, spedizioni, resi, vendita,
 * fiducia). Voce "noi", copy in positivo (mai «cosa non facciamo»). Emette schema FAQPage.
 * NB: nessuna domanda sui pagamenti finché non c'è un gateway attivo in WooCommerce.
 * Regia temi: dark → light → lilla2 (mai due lilla di fila).
 *
 * ⚠️ Richiede una Pagina WordPress con slug "faq" (la carica page.php).
 */

return [

	// 1 · Hero
	[
		'comp'        => 'intro-pagina',
		'tema'        => 'dark',
		'pattern'     => true,
		'eyebrow'     => __('Domande frequenti', 'cardsrift'),
		'titolo'      => __("Le risposte, prima\ndi ordinare", 'cardsrift'),
		'sottotitolo' => __('Le domande che ci fanno più spesso, in un posto solo. Se ne resta una, siamo a un messaggio di distanza.', 'cardsrift'),
	],

	// 2 · Le FAQ (accordion + schema FAQPage)
	[
		'comp'    => 'blocco-faq',
		'tema'    => 'light',
		'eyebrow' => __('Domande & risposte', 'cardsrift'),
		'titolo'  => __('Quello che ci chiedete', 'cardsrift'),
		'faq'     => [
			['domanda' => __('Le carte sono originali?', 'cardsrift'),                'risposta' => __('Sempre. Trattiamo solo prodotto autentico: sealed e singole verificate una a una. Le carte di valore le trovi fotografate fronte e retro nella scheda.', 'cardsrift')],
			['domanda' => __('Come giudicate le condizioni?', 'cardsrift'),           'risposta' => __('Sulla scala ufficiale di Cardmarket, da Mint a Poor, applicata per difetto. Ogni grado è spiegato nella <a href="/guida-alle-condizioni">guida alle condizioni</a>.', 'cardsrift')],
			['domanda' => __('Quali giochi trattate?', 'cardsrift'),                  'risposta' => __('Pokémon, One Piece e Magic: i giochi che conosciamo meglio e su cui possiamo garantire davvero. Gli altri arriveranno con calma.', 'cardsrift')],
			['domanda' => __('Quanto costa la spedizione e in quanto arriva?', 'cardsrift'), 'risposta' => sprintf(__('In Italia la spedizione tracciata con Poste Italiane costa %s. Il pacco parte entro 24/48h dall’ordine.', 'cardsrift'), CR_SHIP_IT_COST)],
			['domanda' => __('Come imballate le carte?', 'cardsrift'),                'risposta' => __('Secondo il valore: sleeve, top loader o rinforzo, poi busta o scatola rigida. Ogni ordine parte protetto, come lo vorremmo ricevere noi.', 'cardsrift')],
			['domanda' => __('Posso rendere un ordine?', 'cardsrift'),               'risposta' => __('Sì: hai 14 giorni di diritto di recesso. Ci scrivi, ci rimandi le carte protette come le hai ricevute, e procediamo col rimborso. Tutti i dettagli nella pagina <a href="/spedizioni-e-resi">Spedizioni e resi</a>.', 'cardsrift')],
			['domanda' => __('Posso vendervi le mie carte?', 'cardsrift'),           'risposta' => __('Sì. Valutiamo singole, sealed e bulk sui prezzi di Cardmarket e ti facciamo un’offerta. Trovi tutto nella pagina <a href="/compriamo-le-tue-carte">Compriamo le tue carte</a>.', 'cardsrift')],
			['domanda' => __('Il sito è nuovo: posso fidarmi?', 'cardsrift'),         'risposta' => __('Il sito è nuovo, ma vendiamo da tempo: su Cardmarket dal 2025, con il 100% di valutazioni positive. Una reputazione che puoi verificare da solo.', 'cardsrift')],
			['domanda' => __('Chi c’è dietro CardsRift?', 'cardsrift'),              'risposta' => __('Un collezionista che segue ogni ordine dall’inizio alla fine: apre, controlla e imballa ogni pacco di persona.', 'cardsrift')],
		],
	],

	// 3 · Chiusura → contatti
	[
		'comp'      => 'claim-progetto',
		'tema'      => 'lilla2',
		'eyebrow'   => __('Ancora dubbi?', 'cardsrift'),
		'testo'     => __('<b>Hai un’altra domanda?</b> Scrivici: ti rispondiamo di solito entro un giorno.', 'cardsrift'),
		'cta_label' => __('Scrivici', 'cardsrift'),
		'cta_url'   => '/contatti',
	],
];
