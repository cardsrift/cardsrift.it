# Rework CardsRift — Fase 1 · Documento operativo

> Ultimo aggiornamento: 17/07/2026. Questo file è la memoria del progetto: cosa è stato deciso,
> cosa è stato costruito, cosa manca e in che ordine. **Va tenuto aggiornato a ogni step.**

## 🔄 Svolta architetturale (17/07/2026) — struttura in codice, backend minimo

Deciso col cliente: abbandonato il page builder ACF copy-heavy (il gruppo flexible-content
`components` con ~89 campi, **mai importato** → nessuna migrazione). Nuovo modello:

- **Struttura + ordine + tema + COPY = codice.** Un *manifest* per pagina in `content/{slug}.php`
  (oggi `content/home.php`), copy in `__()` (traducibile). Engine: `includes/page-builder.php`.
- **Backend "già apparecchiato" AUTO-GENERATO dal codice:** il manifest dichiara i pochi campi DB
  (`edit`) → `cr_register_builder_fields()` li registra via `acf_add_local_field_group` (versionati
  nel repo). Sull'homepage l'unico campo è **i 3 prodotti dell'hero**.
- **Sezioni automatiche pure:** griglie recenti/offerte, "In arrivo" (query `data_uscita`), ticker
  (ibrido 2 brand fissi + 2 dai movimenti negozio) — helper in `rework.php`. Nessun campo backend.
- **Preordini → "In arrivo":** prodotti NON preordinabili (sola vista), ordinati per data di uscita.
  `data_uscita` ora è un **date_picker** (Ymd) registrato da codice.
- **Valori scalari** (% bulk, numeri claim, URL Telegram, shortcode newsletter) → `includes/config.php`.
- **i18n:** completato il setup (`load_theme_textdomain` in `functions.php`, `languages/cardsrift.pot`).
- **Rimossi:** `theme-acf-fields.php` (stub), `acf/acf-export-rework.json` (obsoleto),
  `pagina_carrello`/`pagina_account` (→ `wc_get_cart_url()`/`wc_get_page_permalink('myaccount')`).
- **§3b invertito:** il footer resta **hardcoded** (niente copy footer in ACF Options).

I punti ① (campi ACF a mano) e la parte "prodotti a mano" delle sezioni auto della checklist qui
sotto sono **superati** da questo modello. Le altre pagine (Chi siamo, Bulk) restano da comporre
(basta aggiungere un `content/{slug}.php`).

## ⏱ Stato avanzamento (in ordine di esecuzione)

- [x] Ricerca di mercato (fiducia/TCG) + ricerca UX dark + ricerca buylist/bulk
- [x] Scelta direzione (dark, temi per sezione) · scelta componenti · mood e motto
- [x] Preview approvate (vedi § Link) — bulk: A in homepage, B+C+form nella pagina dedicata
- [x] Fondamenta codice: token 4 temi (`themes.css`), primitive (`design-system.css`), colori `th-*`,
      helper PHP (`rework.php`), `page.php` builder, fix body → Adelle Sans
- [x] 10 componenti page builder in HTML plain (elenco in § 2) — build ok, lint ok
- [x] **Design system importato nel repo**: regole (`docs/design-system.md`), foglio variabili
      (`docs/design-system-tokens.md`), primitive form (`.cr-input/select/textarea/label`),
      pagina vivente “Design System” (`template_styleguide.php`) e aggancio in CLAUDE.md.
      → **È la base obbligatoria di ogni lavorazione futura.**
- [x] **Pulizia “sito nuovo”** (16/07): eliminati i componenti legacy `hero` e `highlight-slider`,
      `selectInit.js` (Gravity Forms mai installato), `image_size.php` (sizes mai usate), filtro
      `custom_cat` morto, i CSS placeholder vuoti (buttons/typography/animations/header.css),
      gli export ACF 2024 stantii e il CSS PhotoSwipe morto. Bylon: tolti i pesi finti,
      aggiunto **Bylon Italic**. Rinominato `rework.css` → **`design-system.css`**.
      Struttura: Tailwind = pipeline principale · `src/scss/` = solo skin legacy WC+header ·
      JS = entry `app.js` → registry `global-components.js` + `utils/effects.js` (scaffold inerte).
      ⚠️ **Da ora il tema NON è deploy-safe finché la homepage non è ricomposta**: la homepage
      di produzione usa i componenti eliminati — un deploy prematuro la svuoterebbe.
      (Regola invariata: mai deploy senza ok esplicito, quindi rischio controllato.)
- [ ] **① Campi ACF in wp-admin** (10 layout + `tema` radio + `data_uscita` prodotti + location su page) — § 3a.1
- [ ] **② Setup prodotti** (attributi condizione/lingua, singles variabili, offerte, preordini) — § 3a.2
- [ ] **③ Comporre homepage + creare pagine** (regia base “Notte”) — § 3a.3
- [ ] **④ QA visivo**: confronto col mockup (artifact vista 1) su desktop/mobile
- [ ] **⑤ Header/footer restyle** (ricerca, contatore carrello, footer ACF options) — § 3b
- [ ] **⑥ Componenti pagina Bulk** (processo, tassi, form) — § 3c
- [ ] **⑦ Fase effetti**, sezione per sezione — § 3d
- [ ] **⑧ Fase 2/3**: PLP/PDP, newsletter provider, back-in-stock, recensioni, SEO — § 3e
- [ ] Aggiornare CLAUDE.md con le novità strutturali (page.php, themes.css, convenzione cr-/th-)
- [ ] Go-live: build + deploy **solo su ok esplicito del cliente**

## 🔗 Link preview (artifact)

1. Direzioni chiaro/ibrido/scuro → https://claude.ai/code/artifact/eeaf1bbf-9182-4e66-b074-2d5068cf5505
2. Cinque template dark → https://claude.ai/code/artifact/2b6e9a34-f723-4e27-9689-cbec92cabd4c
3. Galleria 47 componenti con ID → https://claude.ai/code/artifact/3e029992-8703-410f-b474-9fb488972f98
4. **Riferimento ufficiale**: homepage assemblata + pagina Bulk + design system →
   https://claude.ai/code/artifact/a2a0df61-9150-43ef-a103-80fdf03c28b7

## ❓ Domande aperte (aspettano il cliente)

- % bonus credito negozio per il bulk (minimo consigliato +10%; benchmark USA +25/30%)
- Tassi bulk reali per la tabella (o si parte “su valutazione” e la tabella arriva dopo)
- Numeri veri per il claim (anno di nascita, ordini spediti, dove sono le recensioni)
- Provider newsletter (proposta: Brevo) ed entità sconto primo ordine (−5%?)
- Testi definitivi Chi siamo / Come imballiamo (bozze mie + revisione, o li scrive lui)
- Foto reali (team, banco di lavoro, processo di imballo) per claim-progetto e pagina Chi siamo

## 0 · Decisioni prese (con il cliente)

- **Mood**: «se stai cercando qualcosa, qua lo trovi» — detto in modo **silenzioso**, mai venditore.
  Catalogo piccolo → la ricerca NON è centrale (icona nell'header basta, sfogliare batte cercare).
- **Motto ufficiale**: “Il tuo portale per il collezionismo”. Variante SEO per `<title>`/H1:
  “il portale per le carte collezionabili”. Il logo è letteralmente un *rift*/portale.
- **Base scura** + **tema per sezione selezionabile da backend** (radio ACF su ogni layout).
- **Tailwind è lo standard CSS**. Variabili centralizzate, niente SCSS nuovo per i componenti.
- **Page builder**: i componenti devono essere riutilizzabili su qualsiasi pagina.
- **Bulk** (“Compriamo le tue carte” = rifornimento singole): banner doppia offerta in homepage,
  pagina dedicata con processo in 3 passi + tassi trasparenti + form (foto + WhatsApp).
- Prima **HTML plain**, poi **effetti sezione per sezione** (foil, parallasse, tilt col mouse).
- Preview di riferimento (artifact): homepage assemblata + design system →
  https://claude.ai/code/artifact/a2a0df61-9150-43ef-a103-80fdf03c28b7
  (viste: Home Notte / Home Giorno / Pagina Bulk / Design System — Regia = anteprima del campo tema).

## 1 · Architettura

### File del design system (centralizzati)
| File | Contenuto |
|---|---|
| `src/tailwind/components/themes.css` | **Unico punto di verità dei token**: 4 blocchi `[data-th="dark\|light\|lilla\|lilla2"]` con le custom property `--cr-*`; fix body → Adelle Sans; icone brand inline (data URI) per i pattern |
| `src/tailwind/components/design-system.css` | Primitive riutilizzabili: `.cr-sec`, `.cr-patt`, `.cr-eyebrow`, `.cr-btn(-solid/-ghost/-glass)`, `.cr-glass`, `.cr-card` (+stati), `.cr-well`, `.cr-qadd`, `.cr-chip`, `.cr-badge--*`, `.cr-stock--*`, `.cr-price`, `.cr-ticker`, `.cr-pocket`, `.cr-dropchip` + keyframes |
| `tailwind.config.js` | Colori `th-*` mappati sulle variabili (`bg-th-pg`, `text-th-ink`, `text-th-acc`, `border-th-line`, `bg-th-surface`…), `white-pure`, `purple-deep`, `shadow-th` |
| `src/wp-theme/includes/rework.php` | Helper PHP: `cr_theme()` (radio tema → data-th), `cr_product_card()` (card unica con tutti gli stati WC), `cr_grid_products()` (sorgenti query) |

### Regole dei temi
- Il **dark è la base**; light per sezioni shop quando serve respiro; **lilla = momento**
  (max 1–2 sezioni per pagina); lilla chiaro per pop leggeri (es. raccoglitore singole).
- Contrasti verificati: su dark testo accent `#b6a9d9` (7.4:1); su chiaro CTA `#6b5a99` (AA);
  su lilla testo bianco e CTA nere; su lilla chiaro testo nero e accent `#4d3f73`.
- **Well foto prodotto sempre `white-pure`**, su ogni tema.
- Badge semantici FISSI su tutti i temi: sale `#d9536f`, preordine `#6b5a99`, esaurito nero,
  top-deal oro `#d9b45b` (massimo UNO per griglia).
- Glass **solo** sopra pattern/orbs/foto (serve qualcosa da sfocare); mai testo su glass senza scrim.
- Pattern logo: icona colorata su light/dark, bianca sui lilla (gestito dai token `--cr-patt`).

### Page builder
- Flexible content ACF `components` — già usato dalla homepage (`template_homepage.php`).
- **`page.php` (nuovo)**: stesso loop su tutte le Pagine, fallback su `the_content()`.
  → Estendere la location del field group ACF a `post_type == page` (oggi solo homepage).
- Naming: layout ACF `snake_case` → cartella `kebab-case` in `global-components/`
  (conversione automatica del loop).
- Ogni layout ha il campo **`tema`** (radio: `dark` default / `light` / `lilla` / `lilla2`)
  → stampato come `data-th` dalla helper `cr_theme()`.

## 2 · Componenti costruiti (HTML plain — fase effetti a parte)

| Layout ACF | Cartella | Campi ACF da creare |
|---|---|---|
| `hero_vetrina` | `hero-vetrina/` | tema · eyebrow (default motto) · titolo (textarea) · sottotitolo · cta_label/cta_url · cta2_label/cta2_url · vetrina (repeater ×3: prodotto post_object, etichetta) · trust (repeater: testo, evidenzia) |
| `hero_drop` | `hero-drop/` | tema · eyebrow · titolo_thin · titolo · data_drop (date_time_picker, formato `Y-m-d H:i:s`) · chip_testo · cta_label/cta_url · link_label/link_url · ventaglio (repeater ×3: prodotto) — *se il drop è passato, l'editor rimette hero_vetrina* |
| `ticker_info` | `ticker-info/` | tema · voci (repeater: testo, evidenzia) — *in futuro: feed automatico da restock/nuovi arrivi* |
| `griglia_prodotti` | `griglia-prodotti/` | tema · eyebrow · titolo · link_label/link_url · sorgente (radio: manuale/recenti/offerte) · prodotti (relationship, cond. se manuale) · stile_card (radio: solido/glass) · colonne (radio: 3/4) · prodotto_top (post_object, opz.) · pattern (true/false) |
| `raccoglitore_singole` | `raccoglitore-singole/` | tema · eyebrow · titolo · link_label/link_url · sorgente (radio: manuale/categoria) · prodotti (relationship) · categoria (taxonomy product_cat) |
| `preordini_uscite` | `preordini-uscite/` | tema · eyebrow · titolo · link_label/link_url · prodotti (relationship) · mostra_calendario (true/false) — *richiede campo `data_uscita` (testo) sui PRODOTTI* |
| `claim_progetto` | `claim-progetto/` | tema · eyebrow · testo (wysiwyg basic) · stats (repeater: valore, etichetta) · cta_label/cta_url · pattern |
| `bulk_banner` | `bulk-banner/` | tema · percentuale (es. “+10%”) · percentuale_label · eyebrow · titolo · testo · cta_label/cta_url · microtrust (repeater: testo) · pattern |
| `banner_telegram` | `banner-telegram/` | tema · eyebrow · titolo · testo · cta_label/cta_url |
| `newsletter_box` | `newsletter-box/` | tema · eyebrow · titolo · testo · micro · form_shortcode (opz., provider) · pattern |

### Integrazione WooCommerce (già nel codice)
- `cr_product_card()`: prezzo con `get_price_html()` (del/ins nativo in offerta), badge −% calcolato,
  scorte (Disponibile / “Ultimi N” se `stock_qty ≤ 3` / Esaurito), **quick-add AJAX** per i semplici
  (`add_to_cart_button ajax_add_to_cart` + `data-product_id`), “Scegli condizione” per i variabili,
  “Avvisami quando torna” sugli esauriti (aggancio per plugin back-in-stock, Fase 3).
- **Singole = prodotti variabili** con attributi `condizione` (NM/LP/MP/HP) e `lingua`;
  prezzo per variazione. I chip in tasca leggono `get_attribute()`.
- Preordini: categoria/relationship + campo prodotto `data_uscita`.
- Offerte: `wc_get_product_ids_on_sale()`.

## 3 · Da fare — checklist ordinata

### 3a · Setup contenuti (wp-admin, locale)
1. **ACF — import pronto**: `acf/acf-export-rework.json` (generato, validato: **10 layout, 89 chiavi**
   — SOLO componenti nuovi: i legacy sono stati eliminati dal tema, vedi “Pulizia sito nuovo”).
   Procedura: *ACF → Strumenti → Importa field group* → seleziona il file.
   Contiene: gruppo “Page builder — Componenti (rework)” (field `components`, flexible content,
   location `post_type == page`) e gruppo “Prodotto — Dati rework” (`data_uscita`).
   ⚠️ Dopo l'import: **disattivare/cestinare il vecchio field group** `components` (doppione in
   admin) e **ricomporre la homepage da zero coi componenti nuovi** — i template legacy non
   esistono più nel tema. Verificare su locale prima di pensare alla produzione.
2. Prodotti: attributi globali `condizione` e `lingua`; categoria `singles`; qualche prodotto
   variabile di prova; 1-2 prodotti in offerta; categoria/tag preordini.
3. Comporre la homepage con i componenti (regia scelta: base **Notte** — vedi artifact) e
   creare le pagine: **Compriamo le tue carte** (bulk_banner? no: processo+tassi+form → vedi 3c),
   Il progetto/Chi siamo, Come imballiamo, Spedizioni e resi, Guida alle condizioni, FAQ.

### 3b · Header & footer (tema, non ACF)
- Header: aggiungere **icona ricerca** (product search), **contatore carrello** via cart fragments
  (`wc_get_cart_url()`, `.cart-contents-count`), tema scuro coerente. Restyle di `header.php`/`nav-menu.php`.
- Footer: riscrivere `global-components/footer/template.php` sul design FOOT-2
  (4 colonne, motto, social, pagamenti, P.IVA) con contenuti da **ACF Options** (oggi hardcoded).

### 3c · Pagina Bulk (componenti aggiuntivi)
- `bulk_processo` (3 step + badge fiducia + CTA WhatsApp), `bulk_tassi` (tabella Contanti/Credito
  ancorata a Cardmarket — MAI prezzi assoluti), `bulk_form` (nome, email, gioco, tipo, quantità,
  pagamento preferito, upload 3-5 foto, note + link WhatsApp). Mockup di riferimento: vista 3 dell'artifact.
- Business: bonus credito da confermare (+10% minimo; benchmark +25/30% USA); ricevuta acquisto
  da privato + pagamenti tracciati + regime del margine (verificare art. 128 TULPS col commercialista).

### 3d · Fase effetti — CATALOGO COMPLETO (dopo l'ok sull'HTML plain)

**Regole non negoziabili** (dalla ricerca UX): effetti solo su `transform`/`opacity`/gradient-position
(GPU); interazioni puntatore solo con `(hover: hover)`; tutto spento con `prefers-reduced-motion`
(media query già presente in `design-system.css`); **un solo elemento “vivo” per viewport** — se tutto
brilla, niente brilla; micro-interazioni 200–500ms; il mood resta *silenzioso*: gli effetti
sottolineano, non urlano.

#### Inventario — effetti già usati nelle preview (artifact)
| Effetto | Dove nelle preview | Tecnica |
|---|---|---|
| Tilt 3D che segue il mouse | Ventaglio hero-drop (T3/vista Giorno) | `pointermove` → `--rx/--ry` → `rotateX/rotateY` su container con `perspective`, transition 180ms |
| Sheen “foil” dinamico | Carte del ventaglio | `--px/--py` da pointermove → `background-position` di un gradiente lineare in `mix-blend-mode: overlay` (+ conic rainbow `color-dodge` a bassa opacità) — poke-holo semplificato |
| Sheen foil su hover | Glass card “chase” (T3) | gradiente che attraversa il well via `background-position` transition 600ms |
| Orbs ambientali | Sfondo hero T3 | blob `blur(90px)` con keyframe float 13–17s alternate |
| Griglia prospettica | Fondo hero T3 | linear-gradient ripetuti + `rotateX(38deg)` + mask verso l'alto |
| Chase/deal pulsante | 1 card per griglia | keyframe `box-shadow` oro 2.6s (`.cr-card--deal`, già nel CSS) |
| Badge LIVE / dropchip blink | Header T3, chip drop | keyframe opacity 1.6s (`.cr-dropchip`, già nel CSS) |
| Countdown live | Chip drop | JS (già implementato in `heroDrop.js`) |
| Ticker marquee | BAR-2/BAR-3 | keyframe `translateX(-50%)`, pausa su hover (`.cr-ticker`, già nel CSS) |
| Hover lift card | Tutte le card/tasche | `translateY(-4px)` + shadow + border accent (già nel CSS) |
| Quick-add reveal | Card prodotto | opacity+translate su hover (già nel CSS) |
| Riflesso specchiato | Vetrina T1 “La Teca” | copia `scaleY(-1)` + mask-image gradient (CSS puro) |
| Riflesso “plastica” | Tasche raccoglitore | gradiente statico sul 40% alto (già nel CSS) |
| Spotlight radiale | Hero T1 | radial-gradient statico dietro il prodotto |
| Transizione cambio tema | Tutte le sezioni (Regia) | transition su background/color 350ms |
| Sticky column · outline text · tape/post-it | T2/T5 (non selezionati) | — restano in galleria come riferimento |

#### Roadmap effetti per il sito — in ordine di implementazione

**Tier 1 · Identità (subito dopo l'HTML plain):**
1. **Foil + tilt sul ventaglio hero-drop** — l'effetto firma del sito, il legame più diretto col
   mondo carte. Hook già nei template: `[data-cr-fan]` (tilt) e `[data-cr-holo]` (sheen).
2. **Reveal on-scroll globale** — fade+rise 300ms, stagger 60–80ms tra card della stessa griglia.
   Un solo IntersectionObserver in `src/js/utils/`, attivato da `data-fx="rise"` (+ `data-fx-delay`).
   Sotto soglia `lg`: ridurre la distanza di rise (evitare “salti” su mobile).
3. **Cart badge pop** — quando i fragments aggiornano il contatore: scale 1→1.25→1, 250ms.
   Feedback WooCommerce concreto, costo nullo.

**Tier 2 · Carattere (dopo il QA del Tier 1):**
4. **Card foil su hover** per: card in offerta col badge, top-deal, e tasche delle singole rare —
   lo sheen è “la carta che brilla quando la prendi in mano”. NON su tutte le card (regola: brilla
   solo ciò che è speciale).
5. **Parallasse leggero del pattern** `.cr-patt` — translateY su scroll via rAF, max 10–15%,
   solo desktop. Dà profondità agli hero e al claim senza toccare il layout.
6. **Orbs ambientali su hero-drop** — i blob viola sfocati in float lento dietro il ventaglio
   (solo in questo hero: è la sezione “viva” della pagina quando c'è un drop).
7. **Micro-float idle della vetrina** (hero-vetrina) — oscillazione 6–8s quasi impercettibile
   delle 3 card, sfasata; si ferma su hover. Il negozio “respira”.

**Tier 3 · Chicche (solo a sito stabile):**
8. **Riflesso specchiato** sotto la vetrina dell'hero (CSS puro, tema scuro only).
9. **Holo completo alla poke-holo sulle PDP delle singole rare** (Fase 2, quando ci saranno
   gli scan reali): glare + rainbow legati alla rarità della carta. Riferimento:
   https://github.com/simeydotme/pokemon-cards-css
10. **Add-to-cart “flick”**: al quick-add, la miniatura della carta “vola” verso il carrello
    (translate+scale 400ms, poi pop del badge). Da prototipare: se risulta giocattoloso, si taglia.
11. **Numeri del claim che contano** (count-up all'ingresso in viewport, una sola volta).

**Da NON fare (deciso, con motivazione dalla ricerca):**
- Scrolljacking / smooth-scroll library (Lenis ecc.) — disorienta, pesa, NN/g contrario
- Carousel autorotante in hero — Baymard: banner blindness, va peggio dello statico
- Cursore custom, page transitions SPA-like — fuori registro per un e-commerce MPA
- Foil su tutte le card / glow ovunque — uccide la gerarchia (e la GPU su mobile)
- Parallasse aggressivo multi-layer — il nostro è max 10–15%, oltre è mal di mare

**Note implementative:** un unico modulo `src/js/utils/effects.js` (tilt, sheen, reveal, parallax)
attivato da data-attribute, registrato una volta in `app.js`; niente librerie (no GSAP — non è
installato e non serve); ogni effetto dietro i due gate `(hover: hover)` e `prefers-reduced-motion`;
budget: nessun listener `scroll` diretto (solo rAF/IntersectionObserver).

### 3e · Fase 2/3 (non ora)
- Listato/PLP con filtri (set, rarità, condizione, lingua, prezzo, disponibilità), PDP custom.
- Newsletter provider (Brevo?) + coupon −5%; back-in-stock plugin; recensioni (Trustpilot/Google);
  structured data (Product, Organization, BreadcrumbList); title/H1 SEO col motto.

## 4 · Vincoli operativi
- **MAI** commit/push/deploy senza ok esplicito del cliente (vedi CLAUDE.md).
- Prezzi/tassi/numeri nelle preview sono segnaposto: chiedere i valori reali.
- Tailwind scansiona solo `src/wp-theme`, `src/global-components`, `src/js`:
  le classi generate dinamicamente in PHP vanno scritte per esteso (mai concatenare suffissi).
