# CardsRift — Checklist Go-Live

> ## Questo è un PRIMO go live, non una sostituzione
>
> In produzione oggi c'è un'installazione vuota: **4 pagine** (homepage, cart, checkout, my-account),
> **13 prodotti** di prova, **nessun ordine, nessun cliente**, pagamenti non funzionanti. Tutto il
> lavoro vero è in locale. Quindi il database locale **sale**, e con lui prodotti, pagine, media e
> impostazioni.
>
> **Da quel momento in poi la produzione è l'unica fonte di verità del database.** Lì vivranno
> ordini veri, clienti veri e giacenze vere: un secondo import dal locale li cancellerebbe. Se
> serve lavorare su dati reali, si porta una copia **giù** (prod → locale), mai **su**.
>
> **Il deploy porta SOLO il tema.** `scripts/deploy.sh` fa il mirror del solo
> `public/wp-content/themes/cardsrift/`: non tocca core, plugin, upload né database. Il database e
> gli upload salgono **una volta sola**, a mano, con i passaggi del §1.
>
> ⚠️ **Quello che l'import NON porta** (verificato il 25/07/2026):
> - **i file**: `wp-content/uploads` sono **73 MB in 284 file** e viaggiano separatamente via FTP;
>   senza, ogni immagine referenziata è un buco — a partire dai loghi che header e footer leggono
>   dalle opzioni ACF
> - **i plugin**: l'elenco `active_plugins` che sale contiene solo *advanced-custom-fields-pro,
>   cardsrift-sync, safe-svg, woocommerce*. WooPayments, PayPal Payments, Yoast e Site Kit
>   risulteranno **disattivati**, e i file di `cardsrift-sync` in produzione non esistono affatto
> - **le utenze restano quelle locali**: dopo l'import si entra in wp-admin con username e password
>   di questa installazione, non con quelli usati finora in produzione
>
> Legenda: 🔴 blocco (senza, il negozio non vende/apre) · 🟠 importante · 🟡 rifinitura.

## 0 · Prima di partire, qui in locale

- [ ] 🔴 **WooCommerce da 9.8.5 alla 10.7** (la versione di produzione). Così il database parte già
      allineato e non tocca a WooCommerce aggiornarlo sul server. Dopo l'aggiornamento **ricontrollare
      gli override** in `src/wp-theme/woocommerce/`: sono scritti sui template 9.8.
- [ ] 🔴 **`CR_PLACEHOLDER` → `false`** (`includes/config.php`). Con `true` le sezioni vuote si
      riempiono di prodotti finti: in produzione mostrerebbe merce inesistente.
- [ ] 🟠 **Le righe prodotto della home escludono le singole** per scelta, e il catalogo oggi è
      805 singole contro 4 sealed (3 Magic, 1 Pokémon). Con `CR_PLACEHOLDER` spento la riga Magic
      mostra 3 card, quella Pokémon 1, e "In arrivo" sparisce. Delle due: si carica il sealed prima
      del lancio, oppure si puntano quelle righe sul raccoglitore singole.
- [ ] 🟠 **`CR_TERMS_URL`** → l'indirizzo iubenda delle Condizioni di vendita (vedi §4).
- [x] **Vendita e spedizione solo Italia** — fatto in locale il 25/07/2026
      (`woocommerce_specific_allowed_countries` = `IT`).
- [x] Permalink = **Nome articolo** (`/%postname%/`) · home page statica = **Homepage** · pacchetto
      lingua italiano di WooCommerce.

## 1 · Trasferimento (database + file)

L'ordine conta: il database senza i file dà immagini rotte, i file senza i plugin danno un sito a metà.

- [ ] 🔴 **Export del database** locale.
- [ ] 🔴 **Search-replace degli URL**: `http://localhost/cardsrift/public` → `https://www.cardsrift.it`
      *(11 occorrenze nelle opzioni, nessuna nei contenuti)*.
- [ ] 🔴 **Import in produzione**.
- [ ] 🔴 **`wp-content/uploads` via FTP** — 73 MB, 284 file.
- [ ] 🔴 **`cardsrift-sync` via FTP** in `wp-content/plugins/` (in produzione non c'è).
      Senza, niente import né allineamento con CardTrader — e il guardiano scritto apposta perché
      *solo* la produzione scriva su CardTrader non serve a nulla se il plugin lì non esiste.
- [ ] 🔴 **Deploy del tema** — `npm run deploy` o la skill `/deploy-theme` (che fa prima un dry-run).
      ⚠️ **Mai senza un via libera esplicito.**

⚠️ **L'FTP di Aruba funziona solo con le impostazioni di `scripts/deploy.sh`**
(`ftp:ssl-protect-data false`): con le impostazioni lftp predefinite risponde "Connection refused",
che sembra un blocco del server e invece è la connessione sbagliata.

## 2 · Subito dopo l'import, nel wp-admin di produzione

- [ ] 🔴 **Riattivare i plugin**: WooPayments, PayPal Payments, Yoast SEO, Google Site Kit, cardsrift-sync.
- [ ] 🔴 **Disconnettere e riconnettere WooPayments.** Il database porta su un token Jetpack vecchio,
      riferito a un altro stato del sito (registra già errori `invalid_connection_owner`). Saltando
      questo passaggio il gateway **sembra configurato e non incassa**.
- [ ] 🟠 **Ricollegare Google Site Kit** (accesso Google) e verificare Yoast.
- [ ] 🔴 **Permalink → salvare di nuovo**: senza, le rotte per-gioco e gli slug non rispondono.
- [ ] 🔴 **Carrello e Checkout devono contenere solo gli shortcode** `[woocommerce_cart]` e
      `[woocommerce_checkout]`. Con i blocchi WooCommerce il tema non si applica: le pagine tornano
      al look di WooCommerce **senza dare errore**.
- [ ] 🟠 **Impostazioni → Visibilità del sito → "Prossimamente"** finché si collauda: il sito è
      pubblico e in modalità test un visitatore potrebbe fare un ordine che risulta pagato senza
      aver pagato.

## 3 · Pagamenti

Il negozio usa **WooPayments** (conto Stripe di tipo *Express* gestito da Automattic: **non esistono
chiavi API né modalità test nel dashboard Stripe**) e **PayPal Payments**. Divisione dei compiti,
decisa il 25/07/2026 — serve a non ritrovarsi due pulsanti Apple Pay e due modi di pagare con carta:

| Metodo | Chi lo fa |
|---|---|
| Carta di credito | WooPayments |
| Apple Pay · Google Pay | WooPayments |
| PayPal · Paga in 3 rate | PayPal Payments |
| Bonifico | WooCommerce (`bacs`) |

- [ ] 🔴 **IBAN del bonifico** (WooCommerce → Pagamenti → Bonifico → Conti bancari). Oggi i campi
      sono **vuoti**: chi sceglie il bonifico conclude l'ordine e non sa dove pagare.
- [ ] 🔴 **Contrassegno disattivato** — scelta definitiva, non si usa.
- [ ] 🔴 **Klarna disattivata** (WooPayments → Metodi di pagamento).
- [ ] 🔴 **PayPal: spegnere carte e wallet** dal suo pannello, lasciare **solo PayPal + Pay Later**.
      Pay Later senza soglie nostre: PayPal ammette da sé solo 20–3.000 €, quindi il banner "3 rate"
      non comparirà mai su una singola da 4 €. Il venditore incassa subito e per intero.
- [ ] 🔴 **Dominio Apple Pay = `www.cardsrift.it`.** Nelle impostazioni risulta verificato
      `www.cardsrift.com`: se resta così **il pulsante non compare**, senza errori e senza spiegazioni.
      Serve anche il file di verifica in `.well-known/` — il deploy tocca solo la cartella del tema,
      quindi va caricato a mano una volta.
- [ ] 🟠 **Collaudo**: WooPayments in **modalità test** + PayPal in **sandbox**, giro d'acquisto
      completo, poi si passa a live.
- [ ] 🟠 **Prove su Safari** (Apple Pay) **e Chrome** (Google Pay): compaiono solo lì, e solo con una
      carta nel portafoglio.
- [ ] 🟠 **Coupon di benvenuto `BENVENUTO5`**: percentuale 5%, 1 uso per cliente, esclude i prodotti
      in saldo. Il tema lo mostra già nell'email "Nuovo account" e in home.
- [ ] 🟡 Titoli visibili al cliente: metodo di spedizione → "Spedizione tracciata"; titoli e
      descrizioni dei gateway attivi.

## 4 · Legale (iubenda)

Stato verificato il 25/07/2026: i documenti esistono ma sono intestati a **`www.cardsrift.com`**, la
privacy policy **non dichiara nessun servizio terzo**, e i Termini e Condizioni sono **spenti** — la
pagina dice "non sono più attivi" ed è linkata nel footer del sito online.

- [ ] 🔴 **Portare il sito iubenda su `www.cardsrift.it`**. Se si crea un sito nuovo cambiano gli
      identificativi: allora vanno aggiornati i link nel footer del tema e `CR_TERMS_URL`.
- [ ] 🔴 **Rigenerare privacy e cookie policy dichiarando i servizi veri**: Adobe Fonts/Typekit
      (il font Adelle Sans arriva dai server Adobe a ogni visita, e la licenza non consente di
      ospitarlo da noi), iubenda, WooCommerce/Automattic con la connessione a WordPress.com,
      WooPayments/Stripe, PayPal, Google Analytics e Search Console via Site Kit, hosting Aruba,
      corriere, e il consenso marketing raccolto alla registrazione (`cr_marketing_consent`).
      *(Instagram, Telegram e Cardmarket sono solo collegamenti: non trattano dati.
      Il sync CardTrader manda solo quantità, nessun dato dei clienti.)*
- [ ] 🔴 **Rinnovare il modulo Termini e Condizioni** e compilarlo da e-commerce: venditore
      Beraldo Marco, P.IVA 13934020960, via Palestro 10, 20823 Lentate sul Seveso (MB),
      cardsrift@gmail.com · carte da collezione singole e sigillate + accessori · pagamenti: carta,
      Apple Pay, Google Pay, PayPal, Paga in 3 rate, bonifico (**niente contrassegno**) · spedizione
      tracciata 10,90 €, preparazione 24/48h, **solo Italia** · recesso 14 giorni · garanzia di
      conformità 2 anni · **prezzi finali, IVA inclusa ove dovuta** (in WooCommerce il calcolo
      imposte è spento: nessuna riga IVA da nessuna parte).
- [ ] 🔴 **Pagina WP "Condizioni di vendita" + assegnazione in WooCommerce → Avanzate.** Da lì
      compare la **casella di consenso al checkout**: `woocommerce/checkout/terms.php` è già pronto
      e la mostra nel momento esatto in cui la pagina è impostata.
- [ ] 🟠 **`CR_TERMS_URL`** in `config.php` → il link compare da solo nella pagina *Spedizioni e resi*.
- [ ] 🟠 **Rimettere il link "Termini e Condizioni" nel footer**: il tema nuovo ha solo Privacy e
      Cookie, quindi al deploy si perderebbe un link che oggi in produzione c'è.
- [ ] 🟠 **Il banner cookie deve bloccare gli script prima del consenso.** Il plugin iubenda non è
      installato (i documenti sono incollati a mano nel tema), quindi il blocco automatico non c'è:
      da configurare nel pannello, o Google Analytics parte al primo accesso.

## 5 · Spedizioni e paesi

- [x] **Solo Italia** — fatto in locale il 25/07/2026. Prima erano 36 paesi in vendita con una sola
      zona di spedizione: un cliente tedesco riempiva il carrello e al checkout non trovava
      spedizioni, senza spiegazione.
- [ ] 🔴 **Zona Italia con tariffa 10,90 €** (allineata a `CR_SHIP_IT_COST`).
- [ ] 🟡 **"Gratis sopra 99 €" resta disattivata** (scelta attuale). Se un giorno si accende, la
      barra di avanzamento nel carrello si illumina da sola: nessuna modifica al tema.
- [ ] 🟡 La pagina *Spedizioni e resi* dice "in Europa su richiesta": è il contatto manuale, non una
      zona attiva. Aprendo davvero una zona UE vanno mossi insieme configurazione, pagina,
      `config.php` e Condizioni di vendita — e va escluso il PayPal fuori Italia dove serve.

## 6 · Catalogo & prodotti

Prodotti, pagine e media salgono col database: qui restano solo le cose che il database non sa fare.

- [ ] 🟠 **Attributi prodotto**: "condizione" con i 7 termini Cardmarket (MT/NM/EX/GD/LP/PL/PO) e
      "lingua". *(Coerenti con la Guida alle condizioni.)*
- [ ] 🟠 **ACF Opzioni tema**: verificare che i loghi si vedano (dipendono dagli upload del §1).
- [ ] 🟠 Verificare che i **campi ACF generati dai manifest** compaiano e che i pochi campi "edit"
      (es. i 3 prodotti in vetrina dell'hero) siano popolati.
- [ ] 🟡 Campo **`data_uscita`** sui preordini: oggi nessun prodotto ce l'ha, quindi la sezione
      "In arrivo" non si vede.
- [ ] 🟡 **Foto prodotto**: non stanno nella libreria media, sono URL remoti dal plugin di sync.
      Non serve migrarle, ma serve che `cardsrift-sync` sia attivo.

## 7 · SEO & indicizzazione

- [ ] 🔴 Al lancio vero: Impostazioni → Lettura → **"Scoraggia i motori di ricerca" DISATTIVATO**,
      e **"Prossimamente" spento** (§2).
- [ ] 🟠 Verificare in produzione: `<title>` e meta description per pagina, canonical unico, schema
      **Organization** e **FAQPage**.
- [ ] 🟡 Sitemap (`/wp-sitemap.xml`) e invio a **Google Search Console**.
- [ ] 🟡 Favicon impostata in locale → verificare che sia arrivata.

## 8 · Prova finale

- [ ] Giro completo **desktop + mobile**: home, tutte le istituzionali, scheda prodotto, carrello,
      checkout, account.
- [ ] Link: CTA Telegram (DM precompilato + gruppo), email, Instagram, Cardmarket, link interni nelle FAQ.
- [ ] **Ordine di prova reale** con gateway live, su Safari e su Chrome, + verifica delle email
      (ordine, nuovo account col codice sconto).
- [ ] Un ordine di prova che usa il **bonifico**: la pagina di conferma deve mostrare l'IBAN.
