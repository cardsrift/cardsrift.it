<?php

/**
 * MANIFEST DICHIARAZIONE DI ACCESSIBILITÀ — pagina istituzionale, linkata dalla barra
 * in fondo al footer accanto a Privacy e Cookie.
 *
 * ⚠️ È un IMPEGNO VOLONTARIO, non un adempimento: CardsRift è una microimpresa, e
 * l'European Accessibility Act (dir. UE 2019/882, applicabile dal 28 giugno 2025)
 * esonera le microimprese che forniscono servizi. Il testo è scritto di conseguenza —
 * non dichiara obblighi che non ci riguardano, e soprattutto NON dichiara una
 * conformità piena che non abbiamo: lo stato è "parzialmente conforme" e le
 * limitazioni sono elencate una per una.
 *
 * ⚠️ Le limitazioni qui sotto vengono dall'audit tecnico del 25-26/07/2026. Se un
 * giorno si sistemano i contrasti dei temi lilla, la pausa del ticker o la dimensione
 * dei chip-filtro, questa pagina va aggiornata — una dichiarazione che elenca problemi
 * già risolti è imprecisa quanto una che li nasconde. Aggiornare anche la data.
 *
 * Voce: prima persona plurale, come le altre pagine istituzionali. Vedi [[brand-positioning]].
 * Regia temi: dark → light → dark → light → dark → lilla2 (mai due lilla di fila).
 *
 * ⚠️ Richiede una Pagina WordPress con slug "dichiarazione-accessibilita" (la carica page.php).
 */

return [

	// 1 · Hero
	[
		'comp'        => 'intro-pagina',
		'tema'        => 'dark',
		'pattern'     => true,
		'eyebrow'     => __('Accessibilità', 'cardsrift'),
		'titolo'      => __("Dichiarazione\ndi accessibilità", 'cardsrift'),
		'sottotitolo' => __('Vogliamo che il negozio si possa usare davvero da tutti. Qui trovi a che punto siamo, cosa non funziona ancora e come segnalarci un ostacolo.', 'cardsrift'),
	],

	// 2 · Impegno e stato di conformità
	[
		'comp'    => 'blocco-testo',
		'tema'    => 'light',
		'eyebrow' => __('Il nostro impegno', 'cardsrift'),
		'titolo'  => __('A che punto siamo', 'cardsrift'),
		'testo'   => __('CardsRift si impegna a rendere il proprio sito accessibile alle persone con disabilità. Prendiamo come riferimento le <b>Linee guida per l’accessibilità dei contenuti web (WCAG) versione 2.2, livello AA</b>: sono lo standard internazionale, e comprendono i requisiti richiamati dalla norma europea EN 301 549.

<b>Stato attuale: parzialmente conforme.</b> Significa che la maggior parte del sito rispetta quei requisiti, ma alcune parti no — le trovi elencate qui sotto, senza giri di parole.

CardsRift è una microimpresa. La normativa europea sull’accessibilità, applicabile dal 28 giugno 2025, esonera le realtà della nostra dimensione dagli obblighi previsti per i servizi: questa dichiarazione è quindi <b>un impegno che ci prendiamo noi</b>, non un adempimento. Ci sembrava comunque il modo giusto di lavorare.', 'cardsrift'),
	],

	// 3 · Cosa funziona già
	[
		'comp'    => 'blocco-metodo',
		'tema'    => 'dark',
		'eyebrow' => __('Cosa funziona', 'cardsrift'),
		'titolo'  => __('Quello che abbiamo curato', 'cardsrift'),
		'voci'    => [
			['label' => __('Navigazione da tastiera', 'cardsrift'), 'testo' => __('Tutto il sito si usa senza mouse. Il menu, il riepilogo del carrello e i suggerimenti di ricerca si chiudono con Esc e tengono il focus dove serve; un collegamento «Salta al contenuto» permette di scavalcare l’intestazione a ogni pagina.', 'cardsrift')],
			['label' => __('Lettori di schermo', 'cardsrift'),      'testo' => __('Ogni pagina ha una struttura dichiarata (intestazione, contenuto, piè di pagina) e un solo titolo principale. Comandi e icone hanno un nome che dice a quale carta si riferiscono, non un generico «Aggiungi».', 'cardsrift')],
			['label' => __('Moduli e ordini', 'cardsrift'),         'testo' => __('I campi di accesso, carrello e cassa hanno un’etichetta vera, il completamento automatico dell’indirizzo e gli errori annunciati appena compaiono. Condizione, lingua e foil di ogni carta sono scritti in chiaro in ogni riga d’ordine.', 'cardsrift')],
			['label' => __('Zoom e movimento', 'cardsrift'),        'testo' => __('Il testo si ingrandisce fino al 200% senza rompere il layout e lo zoom con le dita non è mai bloccato. Chi ha impostato la riduzione delle animazioni nel proprio dispositivo non ne vede nessuna.', 'cardsrift')],
		],
	],

	// 4 · Limitazioni note — l'elenco onesto
	[
		'comp'    => 'blocco-metodo',
		'tema'    => 'light',
		'eyebrow' => __('Limitazioni note', 'cardsrift'),
		'titolo'  => __('Quello che non va ancora bene', 'cardsrift'),
		'voci'    => [
			['label' => __('Contrasto di alcuni testi', 'cardsrift'),   'testo' => __('Il testo secondario — suggerimenti nei campi, prezzi barrati, note di servizio — ha un contrasto inferiore a quello richiesto. Sulle sezioni con sfondo viola pieno il problema riguarda anche parte del testo normale.', 'cardsrift')],
			['label' => __('La striscia scorrevole', 'cardsrift'),      'testo' => __('La barra con le novità in home page scorre di continuo e si ferma solo passandoci sopra con il mouse: da telefono non c’è modo di metterla in pausa. Le stesse informazioni sono comunque leggibili nel resto della pagina.', 'cardsrift')],
			['label' => __('Alcuni pulsanti piccoli', 'cardsrift'),     'testo' => __('Nei risultati di ricerca e nella pagina «non trovata», le etichette cliccabili per filtrare sono più piccole della misura minima consigliata: possono risultare difficili da centrare con il dito.', 'cardsrift')],
			['label' => __('Il campo di ricerca', 'cardsrift'),         'testo' => __('Quando lo si raggiunge con il tasto Tab, il campo di ricerca segnala di essere attivo in modo più debole rispetto al resto del sito.', 'cardsrift')],
		],
		'nota'    => __('Sono scelte rimandate, non dimenticanze: le abbiamo misurate e le teniamo in lista. Se una di queste ti blocca davvero scrivici, passa avanti a tutto il resto — e intanto l’informazione che ti serve te la diamo per email.', 'cardsrift'),
	],

	// 5 · Metodo e data della verifica
	[
		'comp'    => 'blocco-testo',
		'tema'    => 'dark',
		'eyebrow' => __('La verifica', 'cardsrift'),
		'titolo'  => __('Come l’abbiamo misurata', 'cardsrift'),
		'testo'   => __('Questa dichiarazione è stata redatta il <b>26 luglio 2026</b> ed è basata su un’<b>autovalutazione</b>: un esame tecnico del codice del sito — modelli di pagina, fogli di stile e script — con calcolo dei rapporti di contrasto e verifica manuale della navigazione da tastiera e della struttura delle pagine.

Non abbiamo commissionato un audit esterno né condotto test con persone con disabilità. È un limite di cui siamo consapevoli e che dichiariamo apertamente: le segnalazioni di chi usa il sito ogni giorno valgono più di qualsiasi controllo automatico.', 'cardsrift'),
	],

	// 6 · Chiusura — canale di segnalazione (il punto vero della pagina)
	[
		'comp'      => 'claim-progetto',
		'tema'      => 'lilla2',
		'eyebrow'   => __('Segnalaci un ostacolo', 'cardsrift'),
		// ⚠️ claim-progetto rende il testo in grande: due righe, non un paragrafo.
		// Il dettaglio della procedura sta nella nota delle limitazioni, qui sopra.
		'testo'     => __('<b>Hai trovato un ostacolo?</b> Scrivici che pagina era e cosa è successo: rispondiamo entro un giorno lavorativo, al massimo cinque.', 'cardsrift'),
		'cta_label' => __('Scrivici una email', 'cardsrift'),
		'cta_url'   => 'mailto:cardsrift@gmail.com?subject=' . rawurlencode(__('Accessibilità del sito', 'cardsrift')),
	],
];
