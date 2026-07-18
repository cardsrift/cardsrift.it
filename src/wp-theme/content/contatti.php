<?php

/**
 * MANIFEST CONTATTI — pagina istituzionale, raggiungibile dal footer (link già presente).
 * SCELTA DEL TITOLARE: niente form. È una pagina "come raggiungerci" = canali diretti + tempi
 * di risposta, coerente col posizionamento "parli con una persona vera".
 * Voce: prima persona plurale (voce del negozio). Copy in positivo (mai «cosa non facciamo»).
 * Canali da config: DM Telegram (cr_tg_dm), email footer, Instagram, gruppo (CR_TELEGRAM_URL),
 * profilo Cardmarket (CR_CM_URL). Vedi [[brand-positioning]].
 * Regia temi: dark → light → lilla2 (mai due lilla di fila).
 *
 * ⚠️ Richiede una Pagina WordPress con slug "contatti" (la carica page.php).
 */

return [

	// 1 · Hero
	[
		'comp'        => 'intro-pagina',
		'tema'        => 'dark',
		'pattern'     => true,
		'eyebrow'     => __('Contatti', 'cardsrift'),
		'titolo'      => __('Contattaci', 'cardsrift'),
		'sottotitolo' => __('Scegli il canale che preferisci: di solito rispondiamo entro un giorno.', 'cardsrift'),
	],

	// 2 · I canali — card linkate (niente form)
	[
		'comp'    => 'canali-contatto',
		'tema'    => 'light',
		'eyebrow' => __('I canali', 'cardsrift'),
		'titolo'  => __('Come raggiungerci', 'cardsrift'),
		'voci'    => [
			[
				'icona'   => 'telegram',
				'titolo'  => __('Telegram', 'cardsrift'),
				'testo'   => __('Il modo più veloce: ordini, vendite e domande, in chat diretta.', 'cardsrift'),
				'azione'  => __('Scrivici in chat', 'cardsrift'),
				'url'     => cr_tg_dm(__('Ciao! Ti scrivo dal sito CardsRift.', 'cardsrift')),
				'esterno' => true,
			],
			[
				'icona'   => 'mail',
				'titolo'  => __('Email', 'cardsrift'),
				'testo'   => __('Per le richieste con più calma o con allegati.', 'cardsrift'),
				'azione'  => 'cardsrift@gmail.com',
				'url'     => 'mailto:cardsrift@gmail.com',
				'esterno' => false,
			],
			[
				'icona'   => 'instagram',
				'titolo'  => __('Instagram', 'cardsrift'),
				'testo'   => __('Novità, anteprime e messaggi diretti.', 'cardsrift'),
				'azione'  => '@cardsrift',
				'url'     => 'https://www.instagram.com/cardsrift/',
				'esterno' => true,
			],
			[
				'icona'   => 'users',
				'titolo'  => __('Gruppo Telegram', 'cardsrift'),
				'testo'   => __('La community: restock, drop e chicche, prima che altrove.', 'cardsrift'),
				'azione'  => __('Unisciti al gruppo', 'cardsrift'),
				'url'     => CR_TELEGRAM_URL,
				'esterno' => true,
			],
			[
				'icona'   => 'star',
				'titolo'  => __('Cardmarket', 'cardsrift'),
				'testo'   => __('La nostra reputazione, verificabile: 100% di valutazioni positive.', 'cardsrift'),
				'azione'  => __('Vedi il profilo', 'cardsrift'),
				'url'     => CR_CM_URL,
				'esterno' => true,
			],
		],
		'nota'    => __('CardsRift · P.IVA 13934020960', 'cardsrift'),
	],

	// 3 · Chiusura
	[
		'comp'      => 'claim-progetto',
		'tema'      => 'lilla2',
		'eyebrow'   => __('A presto', 'cardsrift'),
		'testo'     => __('<b>Scrivici come ti è più comodo.</b> Dall’altra parte c’è sempre qualcuno pronto a darti una mano.', 'cardsrift'),
		'cta_label' => __('Scrivici su Telegram', 'cardsrift'),
		'cta_url'   => cr_tg_dm(__('Ciao! Ti scrivo dal sito CardsRift.', 'cardsrift')),
	],
];
