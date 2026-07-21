# Catalogo — Import & Mapping dati (Cardmarket ↔ WooCommerce ↔ CardTrader)

> **Stato: DRAFT.** Le celle marcate ⚠️ vanno confermate con un **export reale** (vedi §7).
> WooCommerce è il perno: Cardmarket è il *seme*, CardTrader è *pricing engine + secondo canale*.
> Contesto e decisioni: vedi la discovery in memory ([[rework-fase-1]]) e `docs/go-live.md`.

## 1. Cosa dà / cosa vuole ogni sistema

| Sistema | Ruolo | Formato | Chiave d'identità |
|---|---|---|---|
| **Cardmarket** | seme iniziale (+ canale vivo in transizione) | CSV **piatto**, 1 riga per *articolo* (via estensione: niente export nativo) | `idProduct` (la stampa/carta) + attributi articolo |
| **WooCommerce** | perno / verità dello stock | prodotto **variabile** per carta, 1 variazione per combo | SKU nostro + meta `_cardmarket_id`, `_ct_blueprint_id` |
| **CardTrader** | motore prezzi (autopricer) + 2° canale | API v2 JSON, 1 *product* per listing su un *blueprint* | `blueprint_id` + `properties` |

Punto chiave: **Cardmarket è piatto** (una riga = una condizione/lingua/foil), **WooCommerce è gerarchico** (una carta = un prodotto variabile con N variazioni). Il grouping è il cuore del lavoro (§6.1).

## 2. Crosswalk campo per campo

| Concetto | Cardmarket (colonna CSV) | → WooCommerce | → CardTrader |
|---|---|---|---|
| Identità carta | `idProduct` | meta padre `_cardmarket_id` + **chiave di grouping** | `blueprint_id` (via match set+numero, §6.3) |
| Nome | `Name` / `ProductName` | `post_title` (padre) | dal blueprint |
| Gioco | colonna game / dedotto | `product_cat` (Pokémon/Magic/One Piece) | `game_id` (dal blueprint) |
| Set | `Expansion` / `SetCode` | meta / attributo | `expansion_id` (chiave match) |
| Numero | `CollectorNumber` | meta | chiave match blueprint |
| **Condizione** | `condition` (MT/NM/EX/GD/LP/PL/PO) | attr **`pa_condizione`** (variazione) | `properties.condition` (§3) |
| **Lingua** | `idLanguage` (1..11) | attr **`pa_lingua`** (variazione) | `properties.<game>_language` (§4) |
| **Foil** | `isFoil` (+ `isReverseHolo`) | attr **`pa_foil`** (variazione) ⚠️ | `properties.<game>_foil` ⚠️ |
| Prezzo | `price` (EUR decimale) | `_regular_price` (**seme**) | create: `price` decimale · read: `price_cents` int + `price_currency` ⚠️ |
| Quantità | `Amount` / `groupCount` | `_stock` (variazione, `_manage_stock=yes`) | `quantity` |
| **Immagine** | ❌ **assente nel CSV** | featured image (§6.2) | il blueprint ha immagine |
| Note | `Comments` | — | `description` (opz.) |
| Altri flag | `isSigned`,`isAltered`,`isPlayset`,`isFirstEd`,`isFullArt` | — (per ora ignorati) | property se il blueprint le espone |

## 3. Mappa condizioni (quasi 1:1 — NON lossy)

Cardmarket 7 gradi → CardTrader (`Mint, Near Mint, Slightly Played, Moderately Played, Played, Heavily Played, Poor`):

| Cardmarket | Sigla | → CardTrader |
|---|---|---|
| Mint | MT | Mint |
| Near Mint | NM | Near Mint |
| Excellent | EX | Slightly Played |
| Good | GD | Moderately Played |
| Light Played | LP | Played |
| Played | PL | Heavily Played |
| Poor | PO | Poor |

⚠️ I valori accettati dipendono dal blueprint (`editable_properties`): confermare che il gioco esponga `Mint` e `Heavily Played`. In WooCommerce i termini `pa_condizione` restano le **7 sigle Cardmarket** (coerenti con `guida-alle-condizioni`); la conversione avviene solo verso CardTrader.

## 4. Mappa lingue (idLanguage Cardmarket → codice CardTrader)

⚠️ Da confermare con export reale.

| idLanguage | Lingua | → CardTrader code |
|---|---|---|
| 1 | English | `en` |
| 2 | French | `fr` |
| 3 | German | `de` |
| 4 | Spanish | `es` |
| 5 | Italian | `it` |
| 6 | S-Chinese | `zh-CN` |
| 7 | Japanese | `jp` |
| 8 | Portuguese | `pt` |
| 9 | Russian | `ru` |
| 10 | Korean | `kr` |
| 11 | T-Chinese | `zh-TW` |

Property name lato CardTrader è **per gioco**: `mtg_language`, `pokemon_language`, ecc.

## 5. Prezzo — unità

- **Cardmarket**: EUR decimale (es. `2,50`).
- **CardTrader**: create via `POST /products` con `price` **decimale**; letto via `GET /products/export` come `price_cents` **intero** + `price_currency`. ⚠️ confermare.
- **WooCommerce**: decimale. `WC price = price_cents / 100`, poi regola di **rounding** esplicita (§ price-sync).

## 6. Le 4 sfide strutturali

### 6.1 Piatto → variabile (grouping)
Raggruppa le righe Cardmarket per `idProduct` → **prodotto padre variabile**; ogni riga → **una variazione** (condizione × lingua × foil) con proprio `_regular_price` e `_stock`. `idProduct` è la chiave naturale (una carta/stampa; lingua/condizione/foil sono a livello articolo).

### 6.2 Immagini — RISOLTO (via proxy)
L'export **porta `ImageUrl`** (immagine catalogo Cardmarket, popolata 801/801 nel test). L'URL si salva in meta `_cardmarket_image` (nessun allegato → zero righe DB). Il path contiene anche il **set code** (`/1/EOE/835026/835026.jpg` → EOE), recuperabile quando `SetCode` è vuota.

⚠️ **Cardmarket blocca l'hotlinking** (403 senza `Referer: cardmarket.com`): l'URL diretto nell'`<img>` non carica. Soluzione nel plugin (`includes/display.php`): **proxy con cache su disco** — il server scarica col referer giusto, salva un file in `uploads/cardsrift-sync/img-cache/{pid}.jpg` (file, non allegato) e lo serve; dalla 2ª vista è statico. Precedenze: **foto in evidenza manuale > immagine Cardmarket > placeholder**. Per gli hero/chase resta la **foto reale del pezzo** (pitch del brand).

**Sorgente più pulita per Magic (futuro, verificato 20/07 su API):** Scryfall — hotlinkabile (`cards.scryfall.io`, niente 403/referer), qualità alta. **NON serve il match blueprint:** Scryfall espone `cardmarket_id`, lo **stesso** ID che salviamo in `_cardmarket_id` → match **1:1 sull'ID** (ogni stampa ha il suo cardmarket_id). Non esiste operatore di ricerca live per quell'ID (HTTP 400) → si usa il **bulk data** (`default_cards`, ~532 MB, una riga per stampa con `cardmarket_id`+`image_uris`): scaricato una volta, si costruisce la mappa `cardmarket_id → URL` e si assegna `_scryfall_image` (precedenza sopra Cardmarket). Immagini EN (coerenti con i nomi EN scelti per Magic); Scryfall è **solo Magic** (Pokémon/One Piece = altre fonti). Deciso 20/07: **non ora** — conviene alla migrazione del bulk Cardmarket, non al lancio (Magic curato a mano → foto reali sulle chase).

### 6.3 Match blueprint (nessun ID condiviso)
Cardmarket e CardTrader hanno cataloghi separati: nessuna chiave comune. Join su **(gioco, set code, collector number [+ nome, + foil])**. `SetCode` + `CollectorNumber` (che le estensioni includono) sono la chiave affidabile; promo/alt-art richiedono revisione manuale. Salvare l'esito in `_ct_blueprint_id` sul padre.

### 6.4 Foil (3° attributo) + reverse holo
Le singole possono variare per foil/reverse holo → variazione = condizione × lingua × **foil**. Il tema oggi legge solo `condizione`+`lingua` (`raccoglitore-singole`): aggiungere `pa_foil` come 3° attributo di variazione è una decisione di setup. ⚠️

## 7. Cosa esportare per validare (prossimo passo)

**Cardmarket** (niente export nativo → estensione, es. [cardmarket-stock-exporter](https://github.com/lupzn/cardmarket-stock-exporter)):
- campione ~30–50 articoli che copra: singole in **condizioni diverse**, **lingue diverse**, almeno **un foil** e (se Pokémon) **un reverse holo**, più **2 sealed**.
- serve l'**header esatto** (le colonne variano per estensione) + una decina di righe.

**CardTrader** (serve piano venditore + token):
- se già vendi: `GET /products/export` → alcuni product in JSON (per vedere `properties_hash` per gioco + `price_cents`).
- in ogni caso: `GET /blueprints/export?expansion_id=<un set che hai>` → struttura blueprint + `editable_properties` (valori esatti di condizione/lingua/foil accettati dal gioco).
- **NON serve il token in chiaro**: mandami solo l'output JSON di esempio; ometti eventuali dati personali/ordini.

Giochi in scope (le property CardTrader sono per-gioco): ⚠️ da confermare (Pokémon / Magic / One Piece?).

## 8. Findings dal primo export reale (18/07 · Magic · 801 articoli)

File: `cardmarket-stock-2026-07-18-it-Magic-v2.2.7.csv` (estensione lupzn, UI IT).

**Numeri:** 801 articoli · **767 `idProduct` distinti** (grouping quasi assente: solo 5 carte con più righe) · 115 espansioni.
- **Condition:** NM 598 · EX 181 · GD 19 · (PO/PL/LP 1 ciascuno) → in pratica **NM/EX/GD**.
- **Prezzi:** tantissimi `0,20` (bulk); formato **virgola** decimale.
- **Amount:** 1 nel 87% (697), qualche 2–4, pochi 8/12.
- **Nessun sealed:** è tutto singole.

**Presenti e affidabili:** `idProduct`, `Name` (IT localizzato), `Expansion` (IT), `Rarity`, `Condition`/`ConditionFull`, `Price_EUR`, `Amount`, **`ImageUrl`** (801/801, con set code nel path), `ProductUrl` (slug EN).

**ASSENTI / inaffidabili in questo export** (la colonna può esistere nel tracciato ma restare vuota — diagnosi in §8.1):
- **`Language`: 0/801** ⚠️ — colonna presente ma **vuota** (bug di localizzazione dell'estensione, §8.1), non un dato assente su Cardmarket.
- **Foil: non esportato** ⚠️ — nessuna colonna `isFoil`; c'è solo `ReverseHolo` (tutto `N`), che è reverse-holo (Pokémon), **non** il foil Magic (§8.1).
- **`CollectorNumber`: 0/801** ⚠️ — chiave di match blueprint mancante.
- `SetCode`: solo 32/801 (ma recuperabile da `ImageUrl`).

**Parsing:** ID come formula Excel `="835026"` (va ripulito); prezzi con virgola.

**Implicazione sul MODELLO:** le singole reali sono quasi tutte **mono-condizione, qty 1** → un **prodotto SEMPLICE per articolo** è più adatto del variabile-a-matrice per il grosso; il variabile serve solo per le poche carte stoccate in più condizioni. → Rivedere il gate `type=variable` di `cr_singole_products()` (identificare le singole via **categoria**, non via tipo prodotto).

**Implicazione sulla STRATEGIA:** questo file **è** il bulk Cardmarket (767 carte da pochi centesimi) → **resta su Cardmarket**. Il sito parte con **sealed + singole chase curate** (lingua/foil/foto noti a mano), non con questo bulk. Lingua/foil/collector# vanno risolti solo per la **migrazione futura** del bulk (path d'export alternativo o API), non bloccano il lancio.

### 8.1 · Perché lingua e foil mancano — diagnosi estensione lupzn (v2.2.7)

Letto il sorgente (`popup.js`: tutta la logica sta lì, nessun content script). Lo **stock export raschia solo la tabella della lista** stock — non apre mai la pagina di dettaglio dell'articolo. Da lì:

- **Lingua** — dedotta dal **tooltip della bandierina** nella riga, confrontato con una regex che conosce **solo i nomi in tedesco/inglese** (`Deutsch|Englisch|Französisch|… | English|German|French|…`, **niente italiano**). L'export è stato fatto con **UI Cardmarket in italiano** → i tooltip dicevano "Inglese"/"Tedesco"… non riconosciuti → `Language` vuota su tutte le righe. **È un bug di localizzazione dell'estensione, non un dato assente su Cardmarket** (l'estensione ha UI solo DE/EN).
- **Foil (Magic)** — **non rilevato affatto** nell'export: nessuna colonna `isFoil`, nessun toggle. C'è solo `ReverseHolo` (euristica su commenti/tooltip). Il flag `isFoil` esiste solo nella fase di *bulk-update* (round-trip di flag già presenti nel CSV, dalla v2.2.5), **non** nell'export.

**Opzioni dell'estensione** (nessuna popola la lingua o aggiunge il foil): max pagine · delay · "itera per espansione" · `sortBy=name_asc` (serve alla paginazione) · **"Load my sets"** (esporta solo espansioni scelte, v2.2.7) · **filtro lingua** (multi-select su `idLanguage`: filtra *quali* lingue raschiare) · Fast Mode · Bulk price update (dry-run/verify) · Want-Lists export.

**Workaround per la lingua (nessuno richiede codice):**
- **A — UI in inglese:** imposta Cardmarket su English/Deutsch e ri-esporta → la regex riconosce i tooltip → `Language` si popola per-riga. **Confermato** (test 19/07: export EN di Alara Reborn → `Language=Italian` 3/3). L'importer ora **legge la colonna** (`crs_cm_langname_to_slug`, EN+DE→slug) e la usa nello SKU/attributo, con **fallback** al default del form quando vuota. ⚠️ L'export EN dà anche i **nomi in inglese** (`Filigree Angel` invece di `Angelo della Filigrana`): scelta di merchandising → **per Magic teniamo i nomi EN** (standard tra i giocatori e portano la lingua). Pokémon/One Piece da valutare.
- **B — un file per lingua (consigliato):** usa il **filtro lingua** dell'estensione per esportare EN, poi IT, ecc.; ogni file è mono-lingua → si imposta la **"lingua predefinita"** nel form d'import (già usata nello SKU e negli attributi). Zero ambiguità, zero codice.

**Foil:** per il lancio, **a mano** sulle poche chase (lo sai già). Per la migrazione futura del bulk serve l'**API Cardmarket** (`isFoil` affidabile) o una patch all'estensione che parsi l'icona foil nella lista.

⚠️ **Compatibilità parser:** se ri-esporti (es. in inglese) e la prima riga diventasse una riga meta `# CMSE-META …` (introdotta in v2.1.0), il nostro parser — che legge la riga 1 come header — va adattato (2 minuti). Nel file attuale la meta-line **non** c'è.

## 9. Motore d'import (plugin `cardsrift-sync`) — comportamento

- **Aggregazione per SKU** all'upload: i doppioni (stessa carta+condizione) **sommano le quantità** (prezzo = il più basso). Scrive un file `.ndjson` letto a batch con `fseek` (costo lineare, non quadratico).
- **Batch AJAX con offset lato server** (resiliente ai cali di rete); batch più piccolo se si scaricano immagini in libreria.
- **Modalità Full**: sovrascrive prezzo/stock **e azzera** (outofstock, qty 0) le carte dello stesso *gioco+tipo*, importate da noi, **non presenti** nel file → riflette i venduti. ⚠️ Il file Full dev'essere lo stock **completo** di quel gioco+tipo.
- **Modalità Additivo**: solo nuovi, non tocca gli esistenti (per quando WooCommerce sarà master e lo stock lo governerà il sync).
- **Validazione header**: se mancano colonne obbligatorie l'import si ferma (niente corruzione silenziosa). Prezzo: rilevamento robusto del separatore decimale.
- **Immagini**: default via proxy Cardmarket (§6.2) — host allowlist con confine dominio, `wp_safe_remote_get`, lock anti-thundering-herd, negative-cache, scrittura atomica.
- **Anti-bloat** verificato: re-import = **+0 righe DB**; prodotti semplici; niente allegati di default.
- **Versioni della stessa carta**: SKU = `CM-{idProduct}-{COND}[-LANG][-FOIL]` → **condizioni/lingue diverse = prodotti semplici distinti** (fratelli per `_cardmarket_id`, mostrati dal blocco "altre versioni" della PDP). Righe con SKU identico si **sommano**. La **lingua è letta per-riga** dalla colonna `Language` (`crs_cm_langname_to_slug`), con **fallback** alla lingua di default del form quando la colonna è vuota (export IT). Il **foil** non è ancora nei dati (§8.1) → resta `normale` finché non arriverà da API/altro export.
- **Espansione → `pa_espansione` (cablata 20/07)**: l'import assegna un termine attributo con **slug = codice set** (ricavato dall'`ImageUrl`, `/1/{CODE}/` → `eoe`; 801/801 affidabile, stabile tra le lingue) e **label = nome espansione** (`Expansion`, es. "Alara Reborn"). Serve **sia** il fatto "Espansione" in PDP **sia** il filtro del listato (`cr_listing_facets`). Attributo **visibile** + termine oggetto assegnati; **backfill** sui prodotti già importati al re-import in modalità `full`. Prima l'espansione era solo meta `_cardmarket_expansion` → filtro e fatto restavano vuoti. Helper: `crs_expansion_code()` (parser). Verificato con import di prova su locale (listato + PDP + filtro `?pa_espansione=arb`).
- **Rinviato**: cache (transient) delle query di home → pass performance pre-go-live.
