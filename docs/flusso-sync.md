# Flusso Import & Sync — `cardsrift-sync`

> Documentazione ricavata **leggendo il codice** del plugin (`plugins/cardsrift-sync/`), non dalla memoria.
> Ordinata per fasi, con — per ogni fase — il **caso per caso** di cosa succede in ogni situazione possibile.
>
> Legenda direzione: **[IN]** = dati che entrano in WooCommerce · **[OUT]** = scritture verso CardTrader.

---

## 0. Quadro d'insieme

### 0.1 Le tre piattaforme e chi possiede cosa

| Piattaforma | È la verità di… | Direzione |
|---|---|---|
| **Cardmarket** (export CSV) | **stock delle SINGOLE** (fonte del restock) | → entra via import (delta) |
| **WooCommerce** (il sito) | stato prodotto, identità, catalogo, **vendite del sito** | perno centrale |
| **CardTrader** (API v2) | **prezzo** singole (autopricer) · **immagini** · **stock dei SEALED** · **vendite su CT** | ↔ push stock / pull prezzo+immagini+stock |

> **Il sito VENDE davvero.** Lo stock è quindi bidirezionale e riconciliato, non un semplice mirror:
> - **Singole** → master = **Cardmarket**. L'import applica il **delta** (`nuova_CM − ultima_CM vista`, ancora `_cm_stock`), non sovrascrive: così le vendite fatte su sito/CardTrader non vengono cancellate. Il pull da CardTrader può solo **abbassare** (vendite su CT), mai rialzare.
> - **Sealed** (e accessori) → master = **CardTrader** (non passano da Cardmarket). Il pull **allinea** lo stock WC a quello CT (su e giù), prezzo da CardTrader, esclusi dall'autopricer singole.
> - **Vendita sul sito** (qualsiasi tipo) → hook `woocommerce_product_set_stock` → **push immediato** della nuova quantità a CardTrader.

Il ciclo completo:

```
① CSV Cardmarket ──import (DELTA)──▶ WooCommerce     (restock singole)          [IN]
② WooCommerce ──push──▶ CardTrader                   (stock: batch + real-time) [OUT]
③/④ Autopricer CUSTOM ── prezzo singole per cond+lingua+foil → WC e CT          [OUT]
⑤ CardTrader ──pull──▶ WooCommerce  (prezzo, immagini, STOCK↓ singole / STOCK↕ sealed, import sealed)  [IN]
⑥ Vendita sul sito ──hook──▶ push singolo a CardTrader                          [OUT]
```

### 0.2 Identità di un prodotto (lo SKU è la chiave)

Lo SKU lega la stessa carta tra le piattaforme. Costruito da `crs_build_sku($cmid, $cond, $lang, $foil)`:

```
CM-{cardmarket_id}-{COND}[-LANG][-FOIL]
```

- `cardmarket_id` = `idProduct` del CSV Cardmarket (identità della carta a catalogo).
- `COND` = condizione (NM, EX, GD…).
- `LANG` = lingua (omessa se italiano/default).
- `FOIL` = `foil` / `reverse-holo` (omessa se `normale`).

**Perché il foil è nello SKU** (`parser.php:132-135`): senza, foil e non-foil della stessa carta si fonderebbero in **una** voce (qty sommata, prezzo al minimo); poi il pull — che ricostruisce lo SKU **con** il foil — creerebbe un doppione. Tenerlo nello SKU rende l'identità coerente in andata e ritorno.

### 0.3 Metadati chiave (post meta) — glossario

| Meta | Significato | Scritto da |
|---|---|---|
| `_cardmarket_id` | id catalogo Cardmarket — marca "prodotto importato da noi" | import |
| `_cm_stock` | **ultima qty Cardmarket vista** — ancora del delta stock (multi-canale) | import / finalize |
| `_cardmarket_article`, `_cardmarket_expansion`, `_cardmarket_url`, `_cardmarket_image` | dati Cardmarket | import |
| `_ct_blueprint_id` | blueprint CardTrader agganciato (necessario per push/autopricer) | matching |
| `_ct_product_id` | id dell'inserzione CardTrader (= "sono live su CT") | push (create) / import_listing |
| `_ct_price_synced` | ultimo prezzo **confermato** su CardTrader (anti-divergenza) | push prezzo / autopricer |
| `_ct_stock` | ultima qty CardTrader **nota** (scritta dal push o osservata dal pull) — ancora del delta pull | push / pull |
| `_ct_image` / `_ct_image_full` | URL immagine di catalogo CDN (miniatura / piena 672×936) | pull / import_listing |
| `_crs_price_pinned` = `yes` | prezzo **fissato a mano**: pull e autopricer non lo toccano | operatore |
| `_ct_absent` | contatore "assente dall'export CT" (scollega solo dopo 2 giri) | pull |
| `_ct_review` | motivo per cui va in lista "Da rivedere" | autopricer / import_listing |
| `_ct_match_method` | come è stato agganciato il blueprint (diagnostico) | matching |

### 0.4 Ciclo di vita dello stato prodotto

```
                       import crea in ─────────────▶  DRAFT (bozza, prezzo provvisorio Cardmarket)
                                                          │
        pull (senza autopricer): prezzo≠'' ───┐          │  autopricer: prezzo reale scritto
        prezzo fissato a mano (_crs_price_pinned)─┼─────────▶ PUBLISH
        autopricer custom: dopo aver scritto il prezzo ─┘
```

Regola d'oro (fix **H2**): una bozza si **pubblica solo con un prezzo DEFINITIVO**, mai al prezzo provvisorio dell'import.

**Visibilità a stock 0:**
- **Singola** esaurita → `catalog_visibility = hidden` (esce dal catalogo/ricerca, **non** cestinata: ricompare al restock).
- **Sealed** esaurito → resta **`visible`** e pubblicato, marcato **"Esaurito"** (prodotto noto, ri-stoccabile). Su CardTrader l'inserzione viene rimossa (non puoi listare 0), ma il prodotto WC resta.

---

## FASE 1 — Import Cardmarket → WooCommerce **[IN]**

File: `includes/parser.php`, `includes/importer.php`, trigger in `includes/admin.php`.

### 1.1 Upload + aggregazione del CSV (`crs_aggregate_csv`, `parser.php:96`)

Il CSV (delimitatore `;`, BOM gestito) viene letto riga per riga e **aggregato per SKU** in un file `.ndjson` (una riga JSON per SKU). Colonne lette: `idProduct`, `Name`, `Condition`, `Price_EUR`, `Amount`, `Language`, `ReverseHolo`, `Expansion`, `ExpansionCode`/`SetCode`, `ImageUrl`, `ProductUrl`, `ArticleID`.

**Casi riga per riga:**

| Caso | Cosa succede |
|---|---|
| `idProduct` o `Name` vuoti | **riga scartata** (`continue`) |
| Lingua colonna `Language` valorizzata e riconosciuta | usa quella (per-riga) |
| Lingua vuota/non riconosciuta, campo su **"Salta"** (default) | **riga SALTATA** — non importata. **Non si inventa la lingua**: eviterebbe di mettere in vendita una carta in una lingua non posseduta. Conteggiata (`stats.skipped`) e riportata all'operatore |
| Lingua vuota/non riconosciuta, campo su **"Forza lingua: X"** | applica X (scelta **esplicita** dell'operatore, a suo rischio) |
| `ReverseHolo` ∈ {Y,1,TRUE,YES} | `foil` (o `reverse-holo` se gioco = pokemon) |
| altrimenti | `normale` |
| **Doppione** stesso SKU (stessa carta+cond+lingua+foil) | **somma le quantità**; tiene il **prezzo più basso** |
| Prezzo non parsabile | resta `''` (vuoto) |

Output: `$total` = numero di SKU unici. Se `$total < 1` → errore "Nessuna riga valida", `.ndjson` cancellato.

### 1.2 Runner a batch (`admin.php`, AJAX `crs_batch`)

- **Mutex import (fix M6)**: se esiste `crs_import_active` e ha < 1h → *"Un import è già in corso"*, upload rifiutato. Impedisce che due upload concorrenti sullo stesso prodotto creino SKU duplicati.
- Batch size: **40 record** per giro (**8** se l'import scarica anche le immagini in libreria — più pesante).
- L'offset è tenuto **lato server** (byte-offset nel file `.ndjson` via `fseek`, `crs_ndjson_batch`): ripartibile, niente stato nel browser.
- A fine file (`$done`): se non è dry-run → `crs_finalize_sold()` + (se token CT configurato) `crs_ct_push_start()`; poi cleanup del file, dell'option job e del **mutex** `crs_import_active`.

### 1.3 Upsert di un record (`crs_import_row`, `importer.php:16`) — caso per caso

Ogni record del `.ndjson` passa di qui. `$opts.mode` ∈ {`full`, `add`}. Ritorna uno stato.

| # | Condizione | Esito | Dettaglio |
|---|---|---|---|
| 1 | `cardmarket_id` o `name` mancanti | **`error`** | "record senza id/nome" |
| 2 | SKU **esiste** già **e** `mode = add` | **`skipped`** | non tocca nulla (né stock né prezzo) |
| 3 | `dry_run` attivo | `created`/`updated` (secondo esistenza) | **nessuna scrittura** |
| 4 | SKU **non esiste** → creazione | **`created`** | vedi sotto (A) |
| 5 | SKU **esiste** e `mode = full` → update | **`updated`** | vedi sotto (B) |
| 6 | `save()` fallisce | **`error`** | "save fallito" |

**(A) In creazione:**
- Stato = **`draft`** (bozza): nasce senza prezzo definitivo.
- Prezzo regolare = prezzo Cardmarket **solo se non vuoto** (provvisorio, serve al primo push).
- Attributi impostati **prima** del `save()` (un solo save): `condizione`, `lingua`, `foil` (default `normale`), ed **`espansione`** se disponibile.
- Stock sempre impostato (`manage_stock` + quantità + instock/outofstock).
- Categorie: `gioco` + `tipo`. Termini attributo assegnati (alimentano i filtri del listato).
- Immagine in libreria **solo se** `$opts.images` attivo e c'è un `image_url` e non esiste già una featured (`crs_sideload_image`, con allowlist host Cardmarket).

**(B) In update — STOCK a DELTA (multi-canale, punto cruciale):**
- Il **PREZZO NON viene toccato** (lo possiede CardTrader via autopricer→pull, oppure è fissato a mano). Un re-import **non** sovrascrive il prezzo di mercato.
- Lo **STOCK NON viene sovrascritto**: si applica il **delta** `stock_WC += (nuova_CM − _cm_stock)`, poi `_cm_stock = nuova_CM`. Così un restock su Cardmarket **si somma** e le vendite già fatte su sito/CardTrader **restano**. Idempotente: due import identici → delta 0 → nessun raddoppio.
- **Baseline (primo import dopo il delta)**: se il prodotto esistente non ha ancora `_cm_stock`, si **registra solo l'ancora** (`_cm_stock = nuova_CM`) **senza toccare lo stock WC** (assunto già corretto) → non può cancellare nessuna vendita.
- **Visibilità**: singola a 0 → `hidden`; sealed a 0 → `visible` (Esaurito).
- **Backfill espansione**: se il prodotto esistente non ha `pa_espansione`, gliela aggiunge.
- Se `image_url` è cambiato → invalida la cache immagine su disco (`crs_img_cache_clear`).

> **Attenzione al passaggio SET→DELTA**: la correttezza del delta dipende dal fatto che OGNI import full venga processato (i delta si accumulano, non si auto-riparano come faceva il vecchio SET). La modalità **`add`** salta gli esistenti: il restock va fatto in **`full`**.
>
> **Dubbio "un re-import mi sovrascrive prezzi/stock?"** → **No.** Il prezzo solo alla creazione; lo stock a delta, mai in sovrascrittura.

### 1.4 Finalize "venduti" (`crs_finalize_sold`, `importer.php:193`) — solo `mode = full`

Azzera lo stock dei prodotti dello stesso **gioco+tipo**, importati da noi (`_cardmarket_id`), il cui SKU **non è più** nell'export corrente (⇒ venduti/spariti). Riceve `crs_ndjson_present()` = `{skus, tuples}`. Caso per caso:

| Caso | Esito |
|---|---|
| `mode != full` | **non fa nulla** (ritorna 0) |
| Elenco "presenti" **vuoto** (es. `.ndjson` cancellato dalla GC perché import > 24h) | **ABORT** (fix **H6c**): non azzera niente, logga |
| Prodotto ancora nell'export (SKU presente) | saltato (non toccato) |
| **Variante non coperta dall'export** — la tupla `(condizione\|lingua\|foil)` del prodotto **non** è tra quelle presenti | **saltato** (fix **#2**): un export filtrato (es. solo italiano, o solo NM) **non** tocca le altre varianti |
| Prodotto già a 0 e outofstock | saltato (già a zero) |
| Azzererebbe **> 60%** di un catalogo con > 20 prodotti | **ABORT** (fix **H6b**): file **parziale** caricato come full → logga, non tocca nulla |
| Altrimenti | stock → 0, `outofstock`, **`_cm_stock = 0`** (ancora), **singola → `hidden`** / sealed resta visibile, `save()` |

Ritorna il numero di prodotti azzerati (mostrato nel runner come "azzerati (venduti): N").

### 1.5 Trigger del push (opt-in)

A fine import (non dry-run) parte **`crs_ct_push_start()`** in **background** **solo se** l'operatore ha spuntato *"spingi lo stock su CardTrader"* nel form (`crs_push`). **Default: NO** → l'import è un'operazione **locale** finché non decidi di pubblicare (il push crea inserzioni pubbliche reali di *tutto* il catalogo pronto, non solo le carte del CSV). Se spuntato: enqueue in background (l'import non aspetta migliaia di scritture); esiti mostrati: N in coda / `-1` già in corso / 0 niente da fare. Se non spuntato: "import applicato solo in locale".

---

## FASE 2 — Matching WooCommerce → blueprint CardTrader

File: `includes/sync.php` (`crs_ct_match_blueprint`, `crs_ct_match_one`), `includes/cardtrader.php` (`crs_ct_blueprint_map`, `crs_ct_expansion_map`).

Il blueprint è la "carta a catalogo" di CardTrader: senza `_ct_blueprint_id` un prodotto **non può** essere pushato né prezzato. Il matching lo aggancia.

### `crs_ct_match_blueprint($product)` — caso per caso (`sync.php:84`)

| `method` restituito | Quando | Conseguenza |
|---|---|---|
| `no-product` | id prodotto non risolve | niente |
| `no-game` | gioco del prodotto non mappato su CardTrader | niente aggancio |
| `no-expansion` | nessuna espansione CardTrader risolta tra i **candidati** (codice + alias) né per **nome** | niente aggancio |
| **`api-error`** | `crs_ct_blueprint_map($eid)` ritorna **null** (errore rete/auth/429) | **NON** marca "non agganciabile": si **ritenta** (fix fresh-M-3) |
| **`card_market_id`** | match **forte**: `_cardmarket_id` presente in `card_market_ids` del blueprint | aggancio ✔ (metodo preferito) |
| `name` | fallback: nome normalizzato uguale (per promo/varianti senza card_market_ids) | aggancio ✔ |
| `unmatched` | blueprint map ok ma nessun match | resta senza blueprint → finisce in "Da rivedere" |

`crs_ct_match_one($pid, $persist=true)` salva `_ct_blueprint_id` + `_ct_match_method` **solo se** un blueprint è stato trovato. Chiamato "lazy" dal push/autopricer quando manca l'aggancio.

**Nota risoluzione espansione**: si costruisce una lista di espansioni CardTrader **candidate** — codice set + **alias** (`crs_ct_expansion_code_aliases`) + nome — e si cerca il `card_market_id` tra tutte, così la carta si aggancia ovunque CardTrader la archivi. Alias noti: Cardmarket **`x{code}` (Extras) → CardTrader `c{code}` (Collectors)**, e `{code}p` → `{code}` (es. `babp` → `bab`). Il match resta verificato dal `card_market_id`, quindi un alias errato non aggancia mai la carta sbagliata. Le mappe blueprint/espansione sono in cache (6h / 12h).

---

## FASE 3 — Push WooCommerce → CardTrader **[OUT]**

File: `includes/sync.php`. Manda su CardTrader **stock e presenza** delle inserzioni. È **outward-facing**: crea/modifica/cancella inserzioni reali e pubbliche.

### 3.1 Riconciliazione di UN prodotto (`crs_ct_push_one`, `sync.php:430`) — caso per caso

L'azione dipende **solo** dallo stato attuale (idempotente per costruzione):

| Stato prodotto | Azione | Chiamata | Esito |
|---|---|---|---|
| Ha `_ct_product_id` **e** stock > 0 | **UPDATE** | `crs_ct_update_product` → PUT `products/{id}` con **solo `quantity`** | `updated` |
| Ha `_ct_product_id` **e** stock = 0 | **DELETE** (venduto) | `crs_ct_delete_product` → DELETE, pulisce `_ct_product_id` + `_ct_price_synced` | `deleted` |
| **Nessun** `_ct_product_id`, ha blueprint, stock > 0 | **CREATE** | `crs_ct_create_product` → POST `products`, salva `_ct_product_id` | `created` |
| Nessun blueprint, o niente da fare | — | — | `skipped` |
| Errore API sulla scrittura | — | — | `errors` |

**Dettagli importanti:**
- **UPDATE manda SOLO la quantità** (`sync.php:320`): il prezzo su CardTrader lo governa l'autopricer, non lo ri-spingiamo dal sito (altrimenti litigheremmo con l'autopricer ad ogni push). Il prezzo torna col pull.
- **CREATE** usa `crs_ct_push_payload`: `blueprint_id`, `price` (Cardmarket, provvisorio), `quantity`, `properties` per gioco (`crs_ct_game_props`): **Magic** = `condition` + `mtg_language` + `mtg_foil`; **Pokémon** = `condition` + `pokemon_language` + `pokemon_reverse` (reverse holo). One Piece: TBD.
- **Idempotenza**: rilanciare il push non somma nulla — al massimo **SETTA** la quantità al valore corrente.

### 3.2 Push a blocchi in background (fix C2 — no timeout a scala)

- `crs_ct_push_start()` (`sync.php:459`): fotografa gli **id matchabili** (`crs_ct_matchable_ids` = publish+draft con `_cardmarket_id`) in `crs_ct_push_ids` (lista immutabile), crea il cursore `crs_ct_push_job`, accoda il blocco 0.
  - Se un job è **recente** (< 6h) → ritorna `-1` (già in corso).
  - Se un job è **vecchio/bloccato** → lo elimina e riparte.
  - Nessun id matchabile → 0.
- `crs_ct_do_push_batch($offset)` (`sync.php:499`): processa **50 prodotti** per blocco, poi accoda il successivo.

Caso per caso dentro il blocco:

| Caso | Comportamento |
|---|---|
| **Lock** `crs_ct_write` non ottenuto | esce subito (mai due push in parallelo — fix **C1**) |
| Job assente o `offset` non allineato | esce (invocazione duplicata/stantia — fix C3) |
| Prodotto senza blueprint | prova `crs_ct_match_one($pid, true)` per agganciarlo |
| Match dà **`api-error`** | conta `api_error`, **salta senza concludere "skip"** (ritenta al giro dopo) |
| Altrimenti | `crs_ct_push_one` → conta created/updated/deleted/skipped/errors |
| Blocco finito ma catalogo non finito | aggiorna `started` (**battito** anti-azzeramento, fix H-4), accoda il prossimo |
| Catalogo finito | salva `crs_ct_last_push`, pulisce job+ids |

**Pacing scritture** (`cardtrader.php:76`): ogni POST/PUT/DELETE ha `usleep(70000)` (~14/s) per stare sotto il limite globale 200/10s anche in raffica.

### 3.3 Prodotto WC cestinato/eliminato (`crs_ct_on_product_removed`, `sync.php:345`)

Hook su `wp_trash_post` e `before_delete_post`: se il prodotto è live su CardTrader (`_ct_product_id`), **DELETE anche l'inserzione**. Senza questo (fix **H-3**), il pull la troverebbe "sconosciuta" e la **ricreerebbe** come nuovo prodotto pubblicato ogni notte.

### 3.4 Push in tempo reale su vendita del sito (`crs_ct_on_stock_change`)

Hook `woocommerce_product_set_stock` / `woocommerce_variation_set_stock`: quando lo stock di un **nostro** prodotto cambia (ordine sul sito, reso, modifica manuale), accoda un **push SINGOLO async** (`crs_ct_push_single`) della nuova quantità a CardTrader. Non blocca il checkout. Caso per caso:

| Caso | Comportamento |
|---|---|
| Push **soppresso** (`crs_ct_push_suppress`) — siamo dentro import/pull | **ignora**: sono le NOSTRE scritture, non vendite reali; ci pensa il push a blocchi / il pull |
| Prodotto non nostro (né `_ct_product_id`, né `_ct_blueprint_id`, né `_cardmarket_id`) | ignora |
| Push per lo stesso prodotto **già in coda** | dedup: non accoda un doppione (`as_has_scheduled_action`) |
| Esecuzione: **lock `crs_ct_write` occupato** (push/autopricer di massa in corso) | ri-pianifica con **+60s** (mai bounce stretto) |
| Esecuzione normale | aggancia il blueprint se manca, poi `crs_ct_push_one` (UPDATE/DELETE/CREATE secondo stock) |

Non innesca loop: `crs_ct_push_one` scrive su CardTrader ma **non** modifica lo stock WC → l'hook non si ri-attiva.

---

## FASE 4 — Autopricer custom (prezzo per condizione+lingua+foil) **[OUT]**

File: `includes/sync.php`. Sostituisce l'autopricer di CardTrader (che non tiene conto di lingua+condizione). Calcola il prezzo dal mercato CardTrader e lo scrive **su WC e su CT**.

### 4.1 Come si costruisce il prezzo

1. **Raggruppa per espansione** (`crs_ct_autoprice_groups`, `sync.php:1173`): una fetch di mercato per set copre **tutte le carte e tutte le lingue** di quel set → poche chiamate anche con migliaia di carte (es. 773 carte ≈ 101 chiamate). Solo prodotti con `_ct_blueprint_id`, gioco mappato, espansione risolta.
2. **Scarica il mercato del set** (`crs_ct_market_for`, `sync.php:1017`): `GET marketplace/products?expansion_id=`. Compatta ogni inserzione a `{cents, uid, vac, ph}` per non esplodere la memoria (un set può pesare ~50MB → OOM a 128MB, fix H5). Ritorna `null` su errore (≠ `[]` = mercato vuoto).
3. **Prezza la singola carta** (`crs_ct_price_from_market`) — vedi tabella. Costruisce **due** campioni dalla stessa **condizione+foil** (escludendo le mie inserzioni e i venditori in vacanza): `exact` = stessa **lingua**; `broad` = **qualsiasi lingua** (campione ampio = valore reale della carta).
4. **Prezzo di riferimento** (`crs_ct_reference_price`): robusto agli outlier, **filtro SIMMETRICO**.
   - mediana M e MAD; scarta come outlier ogni prezzo **fuori da `[M − 3σ, M + 3σ]`** (σ ≈ 1.4826·MAD; MAD≈0 → banda ±15% da M) → via **le civette (basse) E i placeholder-spazzatura (alti)**;
   - **prezzo = il più basso dei sopravvissuti** (il più economico *legittimo* → competitivo);
   - < 4 dati → mediana semplice (la sanità la fa l'ancora, sotto).
5. **Ancora di sanità** (contro i placeholder quando il mercato-lingua è sottile): `anchor` = riferimento robusto del campione **`broad`**.
   - `exact` ≥ 4 → prezzo da `exact`; se però `> anchor × 4` (cap) = dati-lingua inquinati → usa `anchor` (segnala in "Da rivedere");
   - `exact` 1-3 (sottile) → mediana di `exact` **solo se** dentro `[anchor/4, anchor×4]` (coerente in alto **e** in basso), altrimenti `anchor`;
   - `exact` = 0 → `anchor` (mercato generale, fallback normale, non segnalato);
   - `exact`+`broad` vuoti → nessun prezzo (`skipped_nodata`, in "Da rivedere").
   - Backstop: `broad < 4` (mercato minuscolo) → prezzo comunque scritto ma **segnalato** "pochi dati: verifica".
6. **Prezzo finale** = `base × (1 + markup%)` (markup default **5%**), con **floor** (default **€0,20**).

### 4.2 `crs_ct_price_from_market` — caso per caso (`sync.php:1054`)

| Caso | `status` | Effetti collaterali |
|---|---|---|
| Prodotto senza `_ct_blueprint_id` | **null** (non prezzabile) | — |
| **Prodotto SEALED** | **null** (escluso: `crs_ct_autoprice_groups` lo salta e `crs_ct_price_from_market` ritorna null) | il prezzo del sealed lo dà **CardTrader** via pull, non l'autopricer per condizione/lingua |
| `_crs_price_pinned = yes` (prezzo a mano) | `skipped_pinned` | **pubblica** se ancora in bozza (ha un prezzo definitivo) |
| Gioco non mappato (`crs_ct_game_props` = null, es. One Piece) | `unsupported_game` | scrive `_ct_review = "gioco non mappato"` → lista "Da rivedere". Magic e **Pokémon** sono mappati |
| Nessun dato di mercato per quella condizione/lingua/foil | `skipped_nodata` | scrive `_ct_review = "nessun dato di mercato…"` → "Da rivedere" |
| Prezzo calcolato, **cambia** rispetto a WC/CT | `priced` | scrive prezzo su WC, **pubblica** se in bozza, PUT su CT se ≠ `_ct_price_synced` |
| Prezzo calcolato, **invariato** (né WC né CT cambiano) | `unchanged` | rimuove `_ct_review`; nessuna scrittura |
| Prezzo < floor | *(status sopra)* + `floored=true` | prezzo = floor |
| Prezzo trovato dopo un precedente errore | — | **rimuove** `_ct_review` (non è più da rivedere) |
| PUT su CardTrader **fallisce** | `errors` | il save WC è già avvenuto; `_ct_price_synced` **non** aggiornato → **ritenta** al giro dopo (fix **H1**, niente divergenza permanente) |

**Filtro per carta** (`sync.php:1079-1099`): esclude `uid == mio user`, `on_vacation`, condizione ≠, foil ≠, **lingua ≠** (l'endpoint marketplace **ignora** il parametro `language` → il filtro lingua si fa **qui, lato client**).

**Pubblicazione** (fix H2): la bozza si pubblica **solo qui**, quando c'è un prezzo reale — mai al provvisorio dell'import.

### 4.3 Batch in background (fix C3)

- `crs_ct_autoprice_start()` (`sync.php:1239`): richiede token **e** `crs_ct_user_id()` risolto (`/info`). Se `myuser = 0` → non prezza (fix M4, altrimenti escluderebbe male le mie inserzioni). Job recente (< 6h) → `-1`. Job vecchio → recupera. Fotografa i gruppi in `crs_ct_ap_groups` (immutabile), cursore in `crs_ct_ap_job`.
- `crs_ct_do_autoprice_batch($offset)` (`sync.php:1281`): budget **150 carte** per blocco (≥ 1 gruppo intero). Caso per caso:

| Caso | Comportamento |
|---|---|
| **Lock** `crs_ct_write` non ottenuto | esce (serializza; niente 429/duplicati) |
| Job assente / offset non allineato | esce (invocazione duplicata/stantia) |
| `myuser` = 0 (token morto) | esce; il TTL recupera il job al prossimo start |
| `crs_ct_market_for` ritorna **null** (fetch fallita) | **NON** prezza a "no data": conta `fetch_error`, il gruppo tiene il prezzo vecchio, **si ritenta domani** (fix M5) |
| Mercato ok | prezza ogni carta del gruppo, poi libera subito il decode (picco memoria, H5) |
| Blocco finito ma catalogo no | aggiorna `started` (battito H-4), accoda il prossimo |
| Catalogo finito | salva `crs_ct_last_autoprice`, pulisce job+gruppi |

> **Nota**: le carte sono prezzate **singolarmente** (per condizione+lingua+foil). Il raggruppamento per espansione riguarda **solo** la fetch del mercato (una chiamata per set), non il prezzo — carte diverse dello stesso set hanno prezzi diversi.

---

## FASE 5 — Pull CardTrader → WooCommerce **[IN]**

File: `includes/sync.php` (`crs_ct_pull`, `sync.php:558`). Riprende da CardTrader **prezzo** (dall'autopricer), **immagini** di catalogo e info. **Scrive solo in locale.** Idempotente.

Prima legge `crs_ct_products()` (`GET products/export` = **le mie** inserzioni). Se la chiamata fallisce → esce senza toccare nulla. Nota: `$autoprice_on = get_option('crs_ct_autoprice_on')` — **se l'autopricer custom è attivo il prezzo lo possiede lui**, quindi il pull **non** prende il prezzo da CardTrader.

### 5.1 Fase UPDATE — prodotti WC già collegati (`crs_ct_synced_ids` = publish+draft con `_ct_product_id`)

Per ogni prodotto collegato, caso per caso:

| Caso | Comportamento |
|---|---|
| L'inserzione **non è** nell'export (`not_on_ct`) | Contatore `_ct_absent` +1. Dopo **2 assenze consecutive** scollega (`_ct_product_id`/`_ct_price_synced`) (fix **M7**). In più, se il prodotto è **sealed** (master = CardTrader), sparire = **esaurito** → stock 0 + outofstock (resta visibile). Le **singole** restano intatte (le ri-listerà il push) |
| L'inserzione **è** presente | azzera `_ct_absent`, procede sotto |
| **Prezzo fissato a mano** (`_crs_price_pinned = yes`) | non tocca il prezzo locale; se WC ≠ `_ct_price_synced` lo **propaga su CardTrader** con una PUT (fix M8) |
| **Prezzo**: autopricer spento **oppure prodotto sealed** | prende il prezzo dall'inserzione CardTrader e lo scrive su WC (i **sealed** prendono sempre il prezzo da CT, non si autoprezzano) |
| **Prezzo**: autopricer attivo e singola | **salta** il prezzo (lo possiede l'autopricer) |
| **STOCK — modello DELTA su `_ct_stock`** | applica la **variazione** di CardTrader dall'ultima volta (`ctq − _ct_stock`), non il valore assoluto. Prima volta senza ancora → **baseline** (registra, non tocca WC). L'ancora è aggiornata **anche dal push** (qty che spingiamo noi) → una vendita che passa da sito **e** CT non viene contata due volte, e un restock ancora "in volo" verso CT non azzera WC |
| **Cooldown anti-cache** (`_ct_stock_ts`) | l'endpoint `/products/export` di CardTrader è in **cache (~pochi secondi, misurato)**: se un push ha aggiornato l'ancora da **< 2 min**, il pull **salta** la riconciliazione stock di quel prodotto (l'export potrebbe essere stantìo → delta spurio). La fa il pull successivo, su dati freschi |
| → **singola**: applico solo le **riduzioni** (`min(0, delta)`) — vendite su CT; i rialzi vengono da Cardmarket. A 0 → `hidden` |
| → **sealed**: applico **su e giù** (CT è la verità del sealed). A 0 resta visibile "Esaurito" |
| Immagine preview/piena cambiata | aggiorna `_ct_image` / `_ct_image_full` |
| Bozza + prezzo definitivo, **autopricer spento oppure sealed** | **pubblica** (fix H2). Con autopricer attivo le singole le pubblica lui dopo il prezzo reale; i sealed li pubblica qui |
| Prodotto WC non caricabile | salta |

### 5.2 Fase IMPORT — inserzioni CT senza prodotto WC (`crs_ct_import_listing`, `sync.php:706`)

Sono carte aggiunte **a mano direttamente su CardTrader**. Caso per caso:

| Caso | Esito |
|---|---|
| Inserzione senza `id` o gioco non gestito | **saltata** (0) |
| SKU (ricostruito) **già esistente** con **stesso** `_ct_product_id` (o nessuno) | aggancia solo il collegamento CT (`_ct_product_id`, `_ct_blueprint_id`) — **no duplicato** |
| SKU esistente ma con **altro** `_ct_product_id` | **NON** sovrascrive (orfanerebbe l'altra inserzione): scrive `_ct_review = "doppia inserzione CardTrader"` → "Da rivedere" (fix fresh-M-2) |
| SKU nuovo | **crea** un `WC_Product_Simple` **pubblicato**, instradato per **tipo** (dalla categoria del blueprint, `crs_ct_category_kind`): **singola** → SKU `CM-…`, attributi cond/lingua/foil, categoria `gioco+singole`; **sealed/accessorio** → SKU `CTB-{blueprint}[-LANG]`, niente condizione/foil, categoria `gioco+sealed`/`+accessori`, prezzo da CardTrader, **escluso dall'autopricer**. Immagini dal blueprint |
| Blueprint map = null **oppure** `/categories` in errore (API) | **SALTA** l'import di questa inserzione (`return 0`) → ritenta al prossimo pull, **niente mis-classificazione** (review #1). Distingue "errore API" da "categoria ignota" come il resto del codice |
| Categoria assente/ignota ma fetch riuscito | fallback prudente a **singola** (SKU `CT-{id}` se manca il cardmarket_id) |

> **Risposta al dubbio "il pull ignora le carte che il push non ha portato su CT?"** → Se **tu** aggiungi una carta a mano **su CardTrader**, il pull la **importa** (fase 5.2). Se invece una carta è solo su WC e il push non l'ha creata (es. senza blueprint), non è su CardTrader e il pull non ha nulla da importare per lei — resta in "Da rivedere".

---

## FASE 6 — Orchestrazione notturna (cron 02:00)

File: `includes/sync.php`.

- **Scheduling** (`crs_ct_schedule_pull`, hook `init`): evento `crs_ct_nightly_pull` ogni giorno alle **02:00** nel fuso del sito (`crs_ct_next_2am`), idempotente.
- **`crs_ct_nightly()`** (`sync.php:1353`), caso per caso:

| Passo | Cosa fa | Perché |
|---|---|---|
| 1 | Se `crs_ct_autoprice_on`, **accoda PRIMA** l'evento **separato** `crs_ct_nightly_autoprice` (→ `crs_ct_autoprice_start`) | gira in una **propria** richiesta: anche se il pull va in timeout su un catalogo grande, **l'autopricer parte comunque** (fix **H-1**) |
| 2 | Esegue `crs_ct_pull()` | prezzi/immagini/pubblicazione/import |

Sequenza logica completa di una notte: **autopricer** (scrive i prezzi reali su WC+CT e pubblica) → **pull** (riprende immagini, importa inserzioni manuali, riconcilia). Poiché con autopricer attivo il pull non tocca il prezzo, i due non si pestano i piedi.

---

## FASE 7 — Supporto: immagini, "Da rivedere", lock/job, rate limit

### 7.1 Immagini (`includes/display.php`)

Precedenza dell'immagine di un prodotto (`crs_fallback_image_src`, `display.php:58`):

| Priorità | Fonte | Note |
|---|---|---|
| 1 | Featured manuale (`get_image_id`) | vince sempre |
| 2 | `_ct_image_full` (se `$full`) / `_ct_image` | CDN CardTrader, **hotlink diretto** (no proxy). `$full` = scheda prodotto (672×936); miniatura = listato |
| 3 | Immagine Cardmarket via **proxy** (`crs_cm_src`) | Cardmarket blocca l'hotlinking → proxy server-side con referer + cache su disco |
| 4 | nessuna | placeholder WooCommerce |

**Proxy** (`crs_img_proxy`, endpoint pubblico nopriv): allowlist host `*.cardmarket.com` con confine di dominio; **throttle** 60 fetch cold / finestra 60s per IP (429 oltre, fix M2); timeout 6s; lock anti-thundering-herd; negative-cache 1h; scrittura atomica; validazione "è davvero un'immagine" sui byte.

> **Perché la PDP non è più sgranata**: si usa `_ct_image_full` (piena 672×936) per la scheda, tenendo `_ct_image` (preview) per il listato — mappate in `crs_ct_blueprint_map` (`cardtrader.php:248-254`).

### 7.2 Lista "Da rivedere" (`crs_ct_orphan_drafts`, `sync.php:845`)

Rende **visibili gli skip silenziosi** del flusso automatico. Prende i prodotti importati (`_cardmarket_id`, publish o draft) che il flusso **non** riesce a gestire da solo:

| `stato` mostrato | Causa |
|---|---|
| `non agganciata: <metodo>` | un push **ha provato** ad agganciare il blueprint e ha **fallito** (`unmatched` / `no-expansion` / `no-game`) → va agganciata a mano o pubblicata col prezzo. **NON** compaiono i prodotti appena importati e mai spinti (senza `_ct_match_method`) → niente falsi allarmi |
| *(testo di `_ct_review`)* | l'autopricer/import l'ha marcato: "nessun dato di mercato…", "gioco non mappato", "doppia inserzione CardTrader" |

La query fa OR tra "importato senza blueprint" e "`_ct_review` presente" → così i flag di review compaiono **per qualsiasi tipo, sealed/accessori inclusi** (che non hanno `_cardmarket_id`) — review #2. Così **niente sparisce nel nulla**: un umano lo aggancia/prezza/pubblica.

### 7.3 Concorrenza: lock, job, mutex

| Meccanismo | Chiave | Protegge da |
|---|---|---|
| **Lock MySQL** `GET_LOCK` (`crs_lock`/`crs_unlock`) | `crs_ct_write` | due **scritture** verso CardTrader in parallelo (push + autopricer + push singolo su vendita) → 429/duplicati (fix C1) |
| **Lock MySQL** `GET_LOCK` — **dedicato** | `crs_ct_pull` | due **pull** in parallelo (cron notturno + "Pull ora" manuale) → doppia applicazione dello stesso delta di stock. Separato da `crs_ct_write` per non affamare i batch push/autopricer. Se occupato: il nightly ri-pianifica il pull (evento `crs_ct_pull_retry`), il pulsante mostra "busy" |
| **Mutex import** | option `crs_import_active` (TTL 1h) | due import concorrenti → SKU duplicati (fix M6) |
| **Job + cursore** (offset lato server, lista immutabile separata) | `crs_ct_push_job`/`_ids`, `crs_ct_ap_job`/`_groups` | timeout a scala; riscrittura di blob giganti (fix H4); job zombie via TTL 6h + battito `started` (fix H-4/C3) |
| **Action Scheduler** (fallback WP-Cron) | eventi `crs_ct_*_batch` | esecuzione batch in background senza bloccare la richiesta |

### 7.4 Rate limiting & robustezza API (`includes/cardtrader.php`)

- Limiti CardTrader: 200 req/10s globale, 10 req/s marketplace, 1 req/s jobs.
- Pacing: `usleep(70000)` sulle scritture (`crs_ct_send`), `usleep(110000)` sul marketplace (`crs_ct_market_for`).
- `crs_ct_parse` gestisce **401/403** (auth rifiutata), **429** (rate limit), non-2xx (include il corpo d'errore), risposta non-JSON.
- **Distinzione critica** ovunque: **`null` = errore API** (rete/auth/429) ≠ **`[]` = vuoto legittimo**. In caso di errore il chiamante **ritenta** e non conclude "unmatched"/"no data" (blueprint_map, market_for, expansion_map).
- URL immagini validati (`crs_ct_image_url`): solo `https?://` puliti (niente virgolette/spazi/angoli) → chiude alla fonte il sink XSS (fix M1).
- Token in opzione DB (`crs_ct_token`), **mai** nel repo.

### 7.5 Garbage collection (`crs_gc_jobs`, `admin.php:738`)

CSV/`.ndjson` > 24h, residui cache immagini (`.tmp`/`.lock`/`.fail`) > 1h, option `crs_job_*` orfane (file mancante) → rimossi.

---

## Appendice A — Matrice riassuntiva "stato → cosa fa il flusso"

| Stato del prodotto WC | Import (full) | Push | Autopricer | Pull |
|---|---|---|---|---|
| Nuovo (nel CSV) | crea **bozza**, prezzo provvisorio | CREATE su CT (se blueprint) | scrive prezzo reale → **pubblica** | riprende immagini |
| Esistente (nel CSV) | **delta** stock (`+= nuova_CM − _cm_stock`), prezzo intatto | UPDATE quantità | ri-prezza se cambia | immagini + stock↓ su vendite CT |
| Esistente (NON nel CSV, variante coperta) | stock → **0**, `_cm_stock`=0, singola `hidden` | DELETE su CT | — | scollega dopo 2 assenze |
| Esistente (variante NON coperta dall'export) | **non toccato** (#2) | — | — | — |
| **Venduto sul SITO** | — | **push singolo** immediato (hook) → UPDATE/DELETE su CT | — | — |
| Prezzo fissato a mano | delta stock, prezzo intatto | UPDATE quantità | `skipped_pinned` (+ pubblica) | propaga prezzo su CT se cambia |
| **SEALED** | (di norma non da CSV) | UPDATE/DELETE quantità | **escluso** (prezzo da CT) | prezzo da CT + **stock↕** (CT è master) + pubblica |
| Senza blueprint | creato/aggiornato | `skipped` | non prezzabile (null) | — → **"Da rivedere"** |
| Gioco non mappato (One Piece) | idem | CREATE senza properties | `unsupported_game` → "Da rivedere" | — |
| Aggiunto a mano su CardTrader | — | — | prezza (singola) / escluso (sealed) | **IMPORT** instradato per tipo (singola/sealed/accessorio) |

## Appendice B — Opzioni di lavoro (WordPress options)

| Option | Contenuto |
|---|---|
| `crs_ct_token` | token API CardTrader |
| `crs_ct_autoprice_on` | autopricer custom attivo (sì/no) |
| `crs_ct_floor` / `crs_ct_markup` | floor prezzo (0,20) / ricarico % (5) |
| `crs_import_active` | mutex import in corso |
| `crs_job_{token}` | job import (offset/counts) |
| `crs_ct_push_job` + `crs_ct_push_ids` | job push a blocchi |
| `crs_ct_ap_job` + `crs_ct_ap_groups` | job autopricer a blocchi |
| `crs_ct_last_push` / `crs_ct_last_autoprice` | esito ultimo push / autopricer (feedback admin) |
| transient `crs_ct_uid`, `crs_ct_exp_v2`, `crs_ct_bpm2_{eid}`, `crs_ct_cats_v1` | cache user id / espansioni / blueprint / categorie |

**Eventi (Action Scheduler / WP-Cron):** `crs_ct_nightly_pull` (02:00) → pull+autopricer · `crs_ct_nightly_autoprice` → autopricer · `crs_ct_pull_retry` → ritenta il pull se il lock era occupato · `crs_ct_push_single` → push singolo su vendita del sito · `crs_ct_push_batch` / `crs_ct_autoprice_batch` → blocchi in background.

**Ancore di stock (delta multi-canale):** `_cm_stock` (singole, master Cardmarket) · `_ct_stock` (delta pull, aggiornata da push+pull) · `_ct_stock_ts` (quando il push ha toccato l'ancora — cooldown anti-cache dell'export). Le vendite sul sito riducono WC nativamente e vengono propagate a CardTrader dall'hook `woocommerce_product_set_stock`.
