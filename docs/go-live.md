# CardsRift — Checklist Go-Live

> ⚠️ **Il deploy porta SOLO il tema (i file).** Tutto il resto — Pagine, prodotti, impostazioni
> WooCommerce, ACF, pagamenti, spedizioni — va configurato nel wp-admin di **produzione**:
> il database locale non si sincronizza. Alcune voci risultano ✅ in locale ma vanno **rifatte in prod**.
>
> Legenda: 🔴 blocco (senza, il negozio non vende/apre) · 🟠 importante · 🟡 rifinitura.

## 1 · Pagamenti & WooCommerce
- [ ] 🔴 **Attivare un metodo di pagamento** (PayPal e/o carte via Stripe). Ora in WC **nessun gateway è attivo** → il checkout non incassa. Allineare poi i badge Visa/MC/PayPal del footer ai metodi reali.
- [ ] 🔴 **Spedizione Italia = 10,90 €** (Poste Italiane) in WooCommerce → Spedizione. *(In locale è già a 10,90; in prod va rifatta.)*
- [ ] 🟠 **Europa**: la pagina dice "spediamo anche in Europa (su richiesta)", ma WC oggi vende/spedisce **solo Italia**. Per venderci davvero: aggiungere una **zona di spedizione UE con tariffa** + estendere i Paesi in WC → Generale → "Vendita a". *(Altrimenti la pagina resta valida col contatto per l'Europa.)*
- [ ] 🟡 Confermare che **"gratis sopra 99 €" resti disattivato** (scelta attuale).
- [ ] 🟠 **Coupon di benvenuto**: creare in WooCommerce il coupon **`BENVENUTO5`** (percentuale **5%**, **1 uso per cliente**, **esclude i prodotti in saldo**). Il tema lo mostra già nell'email "Nuovo account" e in home (`CR_WELCOME_COUPON`).
- [ ] 🟠 **Abilitare la registrazione**: WooCommerce → Impostazioni → Account e privacy → "Consenti ai clienti di creare un account nella pagina 'Il mio account'".
- [ ] 🟡 **WooCommerce in italiano**: in locale My Account/checkout sono in **inglese** → installare/aggiornare il pacchetto lingua IT (Bacheca → Aggiornamenti, con lingua sito = Italiano).
- [ ] 🟠 **Provare tutto il flusso d'acquisto** (carrello → checkout → pagamento → email d'ordine + email nuovo account col codice) con un gateway attivo.

## 2 · Legale
- [ ] 🔴 **Condizioni di vendita / Termini & Condizioni** — generarle su **iubenda** (come Privacy/Cookie). Poi:
  - [ ] mettere l'URL in **`CR_TERMS_URL`** (`config.php`) → compare in automatico il link "Condizioni di vendita" nella pagina *Spedizioni e resi*;
  - [ ] **assegnarla in WooCommerce → Avanzate → Pagina Termini e condizioni** (casella di consenso al checkout — ora **non impostata**).
- [ ] 🟠 Verificare che **Privacy e Cookie Policy iubenda** siano corrette e attive (link nel footer).

## 3 · Pagine WordPress
Il contenuto è nel codice; servono le Pagine WP **vuote** con lo slug giusto (le carica `page.php`).
- [ ] 🔴 Creare le Pagine con slug: `chi-siamo`, `compriamo-le-tue-carte`, `guida-alle-condizioni`, `spedizioni-e-resi`, `contatti`, `faq` — **contenuto vuoto**.
- [ ] 🟠 Impostazioni → Lettura → home page statica = **Homepage**. *(In locale ok.)*
- [ ] 🟡 Permalink = **Nome articolo** (`/%postname%/`), necessari per gli slug. *(In locale ok.)*

## 4 · Config del tema (`includes/config.php`) & dati
- [ ] 🔴 **`CR_PLACEHOLDER` → `false`** prima del go-live (ora `true`: riempie le sezioni vuote con prodotti finti). Con `false` mostra il catalogo reale.
- [ ] 🟠 **`CR_TERMS_URL`** → URL iubenda delle Condizioni di vendita (vedi §2).
- [ ] 🟡 **Newsletter**: per ora **sostituita** dall'invito a registrarsi + sconto di benvenuto. La spunta consenso alla registrazione raccoglie già una lista in user meta `cr_marketing_consent` (esportabile). `CR_NEWSLETTER_SHORTCODE` servirà solo se/quando aggiungi una newsletter vera (Brevo).
- [ ] 🟡 **`CR_SHIP_IT_COST`** (10,90 €) — tenere allineato alla tariffa WooCommerce reale.
- [x] Telegram: `CR_TELEGRAM_URL` (gruppo) + `CR_TELEGRAM_DM` (@khewro) reali. → verificare che il **link d'invito al gruppo non scada** (Telegram: "nessuna scadenza").

## 5 · Catalogo & prodotti
- [ ] 🔴 Inserire il **catalogo reale** (prodotti, prezzi, immagini, giacenze). Le sezioni home (recenti, offerte, singole, preordini) si auto-riempiono dai prodotti.
- [ ] 🟠 **Attributi prodotto**: attributo "condizione" con i **7 termini Cardmarket** (MT/NM/EX/GD/LP/PL/PO) + attributo "lingua". *(Coerente con la Guida alle condizioni.)*
- [ ] 🟡 Campo **`data_uscita`** sui preordini (alimenta "In arrivo" / countdown).
- [ ] 🟠 **ACF Opzioni tema**: caricare il/i **logo reali** (header/footer li prendono dalle option).
- [ ] 🟠 Verificare che i **campi ACF generati dai manifest** compaiano in prod e che i pochi campi "edit" (es. 3 prodotti in vetrina hero) siano popolati.

## 6 · SEO & indicizzazione
- [ ] 🔴 Impostazioni → Lettura → **"Scoraggia i motori di ricerca" DISATTIVATO** in produzione (in dev è spesso attivo → niente indicizzazione).
- [ ] 🟠 Verificare in prod: `<title>` + meta description per pagina, **canonical unico**, schema **Organization** + **FAQPage**.
- [ ] 🟡 Sitemap (`/wp-sitemap.xml`) + invio a **Google Search Console**.
- [x] Favicon (site icon) impostata in locale → verificare in prod.

## 7 · Deploy del tema  *(solo con OK esplicito)*
- [ ] 🔴 **Build + deploy FTP** su Aruba (`npm run deploy` o skill `/deploy-theme`, che fa prima un dry-run). ⚠️ **Non deployo mai da solo**: serve tuo via libera esplicito.
- [ ] 🟠 Ricordare: il deploy porta **solo il tema**; il resto di questa checklist si fa nel wp-admin di **produzione**.
- [ ] 🟠 Dopo il deploy: la prod oggi usa la vecchia home con componenti eliminati → **verificare che home e pagine si vedano bene** col nuovo tema (ricomporre la home se serve).

## 8 · Prova finale
- [ ] Giro completo **desktop + mobile**: home, tutte le istituzionali, scheda prodotto, carrello, checkout.
- [ ] Link: CTA Telegram (DM precompilato + gruppo), email, Instagram, Cardmarket, link interni nelle FAQ.
- [ ] **Ordine di prova reale** (con gateway live) + verifica email.
