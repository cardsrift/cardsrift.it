# CardsRift · Copy — contenuti da incollare

> Testi pronti per i campi ACF del page builder (rework 2026). Struttura: **un blocco per
> componente**, nell'ordine di assemblaggio della homepage, poi le pagine. Ogni riga è
> `nome_campo ACF → valore da incollare`.
>
> **Voce del brand** (da `docs/design-system.md` §1): prima persona plurale, da collezionisti
> — «controlliamo una per una», «come se restasse nella nostra collezione». Tono **quieto**:
> niente vendita aggressiva, niente maiuscolo urlato. Motto: *«Il tuo portale per il collezionismo»*.
> Il logo è un *rift*/portale: usalo come concetto.
>
> **Convenzione segnaposto:** `〈…〉` = dato reale da inserire tu (vedi checklist qui sotto).
> Dove do due varianti, scegline una — la prima è la mia preferita.

---

## ⚠️ Dati reali da inserire (sostituisci ogni `〈…〉`)

| Dato | Dove serve | Note |
|---|---|---|
| Voto + n° recensioni + piattaforma | Hero trust, Claim stats | es. `4,9/5` su `Trustpilot`/`Google`/`Feedaty` |
| N° ordini spediti | Claim stats | numero onesto, anche arrotondato (`3.000+`) |
| Anno di nascita del negozio | Claim stats | es. `dal 2019` |
| % credito bulk | Bulk banner | confermare il `+10%` (default già impostato) |
| Link canale Telegram | Banner community, Footer | URL `https://t.me/…` |
| Shortcode newsletter | Newsletter | solo quando c'è il provider (Brevo?) — altrimenti lascia vuoto |
| Nomi prodotti / set reali | Vetrina hero, Ticker, Drop | le etichette qui sotto sono esempi |

---

# PARTE 1 — HOMEPAGE

Regia consigliata **“Notte”** (dark dominante, due soli respiri). Ordine e tema per sezione:

| # | Componente | Layout ACF | Tema (`data-th`) |
|---|---|---|---|
| 1 | Hero Vetrina *(o Drop se c'è un preordine)* | `hero_vetrina` | **dark** |
| 2 | Ticker | `ticker_info` | **dark** |
| 3 | Griglia — aggiunti di recente | `griglia_prodotti` | **dark** |
| 4 | Griglia — in offerta | `griglia_prodotti` | **light** *(respiro)* |
| 5 | Raccoglitore — singole | `raccoglitore_singole` | **dark** |
| 6 | Preordini e uscite | `preordini_uscite` | **dark** |
| 7 | Bulk — Compriamo le tue carte | `bulk_banner` | **lilla** *(il “momento”)* |
| 8 | Claim — Il progetto | `claim_progetto` | **dark** |
| 9 | Banner Telegram | `banner_telegram` | **dark** |
| 10 | Newsletter | `newsletter_box` | **lilla2** *(respiro)* |

Regola: mai due sezioni lilla di fila → qui lilla (7) e lilla2 (10) sono distanti. ✔

---

## 1 · Hero — Vetrina  `hero_vetrina`

- **tema** → `dark`
- **eyebrow** → `Il tuo portale per il collezionismo` *(già default)*
- **titolo** *(2 righe, a capo = riga)* →
  ```
  Le carte che cerchi,
  scelte una per una.
  ```
  *Alternativa (concetto portale):* `Quello che cerchi,` / `è già dentro il portale.`
- **sottotitolo** → `Sealed, singole e accessori per Pokémon, One Piece e Magic. Le controlliamo una per una e le imballiamo come se restassero da noi.`
- **cta_label** → `Esplora il catalogo`
- **cta_url** → `/shop`
- **cta2_label** → `Come lavoriamo`
- **cta2_url** → `/chi-siamo`
- **vetrina** *(3 prodotti — scegli 3 pezzi belli; etichetta pill corta)*:
  1. prodotto 〈…〉 · etichetta → `151 · IT`
  2. prodotto 〈…〉 · etichetta → `OP-10 · JP`
  3. prodotto 〈…〉 · etichetta → `MTG · EN`
- **trust** *(riga fiducia, evidenzia + testo)*:
  1. evidenzia `〈4,9/5〉` · testo `recensioni verificate`
  2. evidenzia `24/48h` · testo `spedizione tracciata`
  3. evidenzia `Foto reali` · testo `di ogni singola oltre i 50€`

### 1-bis · Hero — Drop *(alternativa, quando c'è un preordine in arrivo)*  `hero_drop`

- **tema** → `dark`
- **eyebrow** → `Il tuo portale per il collezionismo`
- **titolo_thin** → `Prossimo drop — 〈One Piece〉`
- **titolo** → `〈OP-10 Royal Blood〉`
- **data_drop** → 〈imposta data e ora reali — alimenta il countdown〉
- **chip_testo** → `Drop tra` *(già default)*
- **cta_label** → `Preordina ora`
- **cta_url** → `/preordini`
- **link_label** → `Vedi tutte le uscite`
- **link_url** → `/preordini`
- **ventaglio** → 〈3 prodotti del drop〉

---

## 2 · Ticker  `ticker_info`

- **tema** → `dark`
- **voci** *(movimenti reali del negozio — aggiornali ogni settimana; testo + evidenza)*:
  1. testo `Restock` · evidenzia `〈Shiny Treasure ex〉`
  2. testo `Nuovi arrivi` · evidenzia `〈One Piece OP-09〉`
  3. testo `In arrivo` · evidenzia `〈Pokémon 151 · display〉`
  4. testo `Appena aggiunte` · evidenzia `〈12 singole Magic〉`
  5. testo `Compriamo` · evidenzia `le tue singole, +10% in credito`
  6. testo `Spedito oggi` · evidenzia `〈tracciato in 24/48h〉`

---

## 3 · Griglia — Aggiunti di recente  `griglia_prodotti`

- **tema** → `dark`
- **eyebrow** → `Appena arrivati`
- **titolo** → `Aggiunti di recente`
- **link_label** → `Tutte le novità`
- **link_url** → `/shop?orderby=date`
- **sorgente** → `recenti`
- **colonne** → `4`
- **stile_card** → `solido`
- **pattern** → off

## 4 · Griglia — In offerta  `griglia_prodotti`

- **tema** → `light` *(respiro chiaro)*
- **eyebrow** → `Occasioni`
- **titolo** → `In offerta questa settimana`
- **link_label** → `Tutte le offerte`
- **link_url** → `/shop`
- **sorgente** → `offerte`
- **colonne** → `4`
- **prodotto_top** → 〈scegli 1 prodotto per il badge oro “Top deal” — massimo uno〉

## 5 · Raccoglitore — Carte singole  `raccoglitore_singole`

- **tema** → `dark`
- **eyebrow** → `Carte singole`
- **titolo** → `Sfoglia il raccoglitore`
- **link_label** → `Apri il raccoglitore`
- **link_url** → `/carte-singole`
- **sorgente** → `manuale` *(scegli 6 pezzi forti)* **oppure** `categoria`
- **prodotti** / **categoria** → 〈selezione〉

## 6 · Preordini e uscite  `preordini_uscite`

- **tema** → `dark`
- **eyebrow** → `Calendario`
- **titolo** → `In arrivo prossimamente`
- **link_label** → `Tutti i preordini`
- **link_url** → `/preordini`
- **prodotti** → 〈prodotti con “Data di uscita” compilata; ordine = calendario〉
- **mostra_calendario** → on

---

## 7 · Bulk — Compriamo le tue carte *(Opzione A)*  `bulk_banner`

- **tema** → `lilla` *(il momento della pagina)*
- **percentuale** → `+10%` *(già default — 〈conferma la %〉)*
- **percentuale_label** → `in credito negozio` *(già default)*
- **eyebrow** → `Compriamo le tue carte` *(già default)*
- **titolo** → `Le tue doppie valgono. Da noi diventano credito.`
- **testo** → `È così che ci riforniamo di singole: dalle collezioni di chi, come te, ne ha qualcuna di troppo. Valutazione trasparente sui prezzi di Cardmarket, pagamento tracciato in 24/48h.`
- **cta_label** → `Valuta le tue carte`
- **cta_url** → `/compriamo-le-tue-carte`
- **microtrust** *(garanzie con spunta)*:
  1. `Conteggio in foto e video, sempre`
  2. `Reso gratis se rifiuti l'offerta`
  3. `Pagamento tracciato, oppure +10% in credito`
- **pattern** → on

---

## 8 · Claim — Il progetto  `claim_progetto`

- **tema** → `dark`
- **eyebrow** → `Il progetto` *(già default)*
- **testo** *(WYSIWYG — il **grassetto** diventa accent colorato)*:
  > Siamo collezionisti prima che negozianti. **CardsRift nasce per essere il portale che avremmo voluto trovare noi:** carte controllate una per una, condizioni oneste, e qualcuno che risponde davvero dall'altra parte. Niente foto finte, niente sorprese all'apertura — **solo le carte che cerchi, trattate come se restassero nella nostra collezione.**
- **stats** *(numeri veri)*:
  1. valore `〈4,9★〉` · etichetta `su 〈120〉 recensioni`
  2. valore `〈3.000+〉` · etichetta `ordini spediti`
  3. valore `〈2019〉` · etichetta `dal primo pacco`
- **cta_label** → `Chi siamo`
- **cta_url** → `/chi-siamo`
- **pattern** → on

---

## 9 · Banner — Community Telegram  `banner_telegram`

- **tema** → `dark`
- **eyebrow** → `Community` *(già default)*
- **titolo** → `Il canale dove succede prima`
- **testo** → `Restock, drop e chicche li annunciamo lì prima che altrove — con qualche prezzo che sul sito non trovi. Si entra gratis, e senza spam.`
- **cta_label** → `Entra nel canale`
- **cta_url** → `〈https://t.me/…〉`

## 10 · Newsletter  `newsletter_box`

- **tema** → `lilla2`
- **eyebrow** → `Newsletter` *(già default)*
- **titolo** → `−5% sul tuo primo ordine` *(già default)*
- **testo** → `Una mail solo quando arriva qualcosa che vale: le uscite, i restock, e lo sconto di benvenuto appena ti iscrivi.`
- **micro** → `Niente spam. Ti cancelli quando vuoi, con un clic.`
- **form_shortcode** → 〈vuoto finché non c'è il provider; poi incolla lo shortcode〉
- **pattern** → on

---

# PARTE 2 — Pagina “Compriamo le tue carte”  `/compriamo-le-tue-carte`

> Riusa il **bulk_banner** in testa (stesso copy del punto 7, con `cta_url` → `#form`).
> Le sezioni B (processo 3 step), C (tassi) e il form non sono ancora componenti ACF
> (fase 6 della roadmap): sotto trovi il copy pronto per quando li costruiamo.

### B · Come funziona *(3 step)*
- Eyebrow → `Come funziona`
- Titolo → `Tre passi, zero sorprese`
1. **Mandaci la lista** — `Fai una foto alle carte o mandaci un file. Ci bastano set e condizioni per una prima stima.`
2. **Ricevi l'offerta** — `La valutiamo sui prezzi correnti di Cardmarket e ti scriviamo su WhatsApp. Nessun impegno.`
3. **Scegli come incassare** — `Bonifico tracciato in 24/48h, oppure +10% se prendi credito negozio. Reso gratis se rifiuti.`

### C · Tassi trasparenti
- Eyebrow → `Quanto paghiamo`
- Titolo → `Percentuali chiare, sul valore reale`
- Testo → `Lavoriamo in percentuale sul prezzo di Cardmarket, mai su cifre inventate. La percentuale dipende da gioco, domanda e condizione — te la diciamo prima, per iscritto.`
- Tabella 〈tassi reali da confermare〉:
  | Tipo | In contanti | In credito (+10%) |
  |---|---|---|
  | Singole richieste (NM) | `〈…%〉` | `〈…%〉` |
  | Sealed recente | `〈…%〉` | `〈…%〉` |
  | Bulk comune (/1000) | `〈… €〉` | `〈… €〉` |
- Nota piccola → `Acquistiamo da privati con regolare ricevuta (regime del margine). I prezzi sono in percentuale su Cardmarket, aggiornati al giorno della valutazione.`

### Form
- Titolo → `Raccontaci cosa hai`
- Campi: `Nome`, `Email o WhatsApp`, `Cosa vuoi vendere` (textarea), upload `Foto delle carte`, checkbox privacy.
- Testo bottone → `Invia per una stima`
- Micro sotto → `Ti rispondiamo entro un giorno lavorativo. Le foto ci aiutano a essere precisi da subito.`

---

# PARTE 3 — Pagina “Chi siamo / Il progetto”  `/chi-siamo`

> Componibile ora con **claim_progetto** (riutilizzabile) + testo libero. I moduli “volti”
> e “come imballiamo” (FID-4/FID-5) arriveranno come componenti; il copy è pronto sotto.

### Intestazione
- Eyebrow → `Chi c'è dietro`
- Titolo → `Un portale tenuto da collezionisti`
- Sottotitolo → `Non un magazzino anonimo: qualcuno che le carte le apre, le controlla e le imballa a mano — perché lo fa anche per sé.`

### Blocco “Perché CardsRift”  *(usa `claim_progetto`)*
- Stesso testo del punto 8, oppure versione lunga:
  > **Le tre cose che ci facevano diffidare degli altri shop** — carte gonfiate di condizione, foto che non erano quelle del pezzo vero, pacchi arrivati piegati — **sono le tre cose che qui non succedono.** Controlliamo ogni carta una per una, fotografiamo davvero le singole di valore, e imballiamo ogni ordine come se dovesse arrivare a noi.

### Blocco “Come imballiamo” *(FID-5, futuro — copy pronto)*
- Titolo → `Come imballiamo`
- Testo → `Sleeve, top loader o team bag secondo il valore, cartoncino di rinforzo, busta o scatola rigida. Foto del contenuto prima di chiudere, su richiesta. Se qualcosa arriva rovinato, lo risolviamo — punto.`

### Blocco valori *(3 punti)*
1. `Condizioni oneste` — `Se una carta è LP la chiamiamo LP. Meglio una vendita in meno che un cliente deluso.`
2. `Persone, non ticket` — `Ci scrivi e ti risponde chi il negozio lo tiene davvero.`
3. `Un portale, non un supermercato` — `Catalogo curato e piccolo: quello che teniamo, lo teniamo perché ci crediamo.`

---

# PARTE 4 — Footer & microcopy trasversale

> Il footer ha campi propri (componente `footer/` + Theme Options): incolla dove pertinente.

- **Tagline sotto il logo** → `Il tuo portale per il collezionismo.`
- **Riga fiducia** → `Spedizione tracciata 24/48h · Reso facile · Pagamenti sicuri`
- **Colonne menu** *(titoli)* → `Catalogo` · `Il negozio` · `Aiuto`
  - Catalogo: `Novità`, `In offerta`, `Carte singole`, `Preordini`, `Sealed`, `Accessori`
  - Il negozio: `Chi siamo`, `Compriamo le tue carte`, `Community Telegram`, `Recensioni`
  - Aiuto: `Spedizioni e resi`, `Condizioni delle carte`, `Contatti`, `FAQ`
- **Newsletter mini** → titolo `Resta aggiornato` · placeholder `La tua email` · bottone `Iscriviti`
- **Riga legale** → `CardsRift · 〈Ragione sociale〉 · P.IVA 〈…〉 — Il tuo portale per il collezionismo`
- **Social** → etichette `Telegram`, `Instagram`, `TikTok` 〈link reali〉

### Microcopy ricorrente *(bottoni/stati — già usati dalle primitive)*
- Aggiungi al carrello → `Aggiungi` · variabili → `Scegli condizione` · esaurito → `Avvisami quando torna`
- Scorte → `Disponibile` / `Ultimi 〈N〉` / `Esaurito`
- Carrello vuoto → `Il carrello è vuoto. C'è tutto un portale da sfogliare.`
- Ricerca a vuoto → `Nessun risultato. Prova con il nome del set o della carta.`
- Badge → `Sconto` · `Preordine` · `Top deal`

---

*Fonte campi: `acf/acf-export-rework.json`. Regole voce/tono: `docs/design-system.md` §1. Stato lavori: `docs/rework-fase-1.md`.*
