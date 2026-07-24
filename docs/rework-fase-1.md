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
- [x] **Flusso d'acquisto ridisegnato** (24/07/2026): carrello, checkout, conferma d'ordine e area
      account come **override WooCommerce** in `src/wp-theme/woocommerce/` (31 template), logica in
      `includes/shop.php`, primitive in `tailwind/components/shop.css`, comportamenti in
      `js/components/shop.js`. Verificato con screenshot reali desktop+mobile e test d'interazione
      (auto-update quantità, rimozione con annulla, coupon, ricalcolo AJAX del checkout,
      validazione inline). Vedi § 3f.
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

#### ✅ Stato implementazione (21/07/2026) — motore GSAP

Motore unico in `src/js/utils/effects.js` su **GSAP 3 + ScrollTrigger** (decisione 21/07: GSAP è gratis
e più robusto del vanilla per timeline/spring/scroll — vedi CLAUDE.md). Attivato da `app.js` su documentReady;
CSS di supporto (glare/holo/orbs/pre-hide) nel blocco "FASE EFFETTI" di `design-system.css`.
**Implementati e verificati** (build + Playwright):
- **Hero-vetrina — entrance "blur-to-focus + fan"** (`heroEntrance`, `power3.out`, stagger 0.13): le 3 carte
  emergono dal buio a fuoco. GSAP legge il fan (rotate/scale da Tailwind) e lo conserva; pre-hide via `.cr-fx`.
- **Hover tilt 3D + glare specular** (`cardTilt`, `gsap.quickTo`): su hero e su TUTTE le card prodotto
  (`[data-cr-tilt]` da `cr_card_fx_attrs()`); glare = highlight che segue il puntatore (`.cr-glare` /
  `.cr-well::before`, var `--cr-mx/--cr-my/--cr-active`). Le foil (`[data-cr-holo]`) hanno anche il riflesso holo.
- **Reveal on scroll** (`sectionReveals`, ScrollTrigger + stagger 0.09) su `[data-fx-stagger]`/`[data-fx="rise"]`.
- **Orbs + parallasse** (`orbsParallax`, ScrollTrigger scrub): wrapper `.cr-orbs` clippato + **maschera radiale
  morbida** (bordi mai tagliati netti — bug fixato), layer interno `.cr-orbs__layer` che scorre; orb fluttuano (keyframe).
- **Micro**: cart badge **pop** (GSAP su evento WooCommerce `added_to_cart` + fragment `cr_cart_badge()`); ticker/countdown già CSS.

**Scelte cliente (21/07):** hover tilt su tutte le card, foil solo sulle singole foil, reveal + orbs; scartati:
portale animato hero, griglia prospettica, "Il Rift", bottone magnetico. Tutto dietro i gate `(hover:hover)` +
`prefers-reduced-motion`. Esplorazione mostrata (artifact): https://claude.ai/code/artifact/6c3f4104-6d58-4ae3-a28b-d7b903ce54e0
Ricerca motion premium (3 agenti): spring `linear()`, tilt+glare stile pokemon-cards-css, gerarchia timing/easing — applicata via GSAP.

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

**Note implementative:** un unico modulo `src/js/utils/effects.js` su **GSAP 3 + ScrollTrigger**
(tilt/glare, reveal, parallax, entrance), attivato da data-attribute + `app.js`; ⚠️ aggiornamento 21/07:
GSAP è ora la libreria motion (prima si diceva "no GSAP" → superato, vedi § Stato implementazione).
Ogni effetto dietro i due gate `(hover: hover)` e `prefers-reduced-motion`; lo scroll passa da
ScrollTrigger (niente listener `scroll` diretti). ⚠️ Il bundle supera il warning webpack di 244KB per
via di GSAP: valutare code-split se il peso diventa un problema.

### 3e · Fase 2/3 (non ora)
- Listato/PLP con filtri (set, rarità, condizione, lingua, prezzo, disponibilità), PDP custom.
- Newsletter provider (Brevo?) + coupon −5%; back-in-stock plugin; recensioni (Trustpilot/Google);
  structured data (Product, Organization, BreadcrumbList); title/H1 SEO col motto.

### 3f · Flusso d'acquisto — carrello → checkout → account (fatto il 24/07/2026)

**Decisione architetturale.** Carrello e Checkout erano costruiti coi **blocchi** WooCommerce, che
non sono sovrascrivibili da template PHP (si possono solo “skinnare” via CSS sulle classi
`wc-block-*`, fragili e non ristrutturabili). Sono stati riportati agli **shortcode classici**
(`[woocommerce_cart]`, `[woocommerce_checkout]`), il che sblocca l'override completo dei template.
Costo accettato: niente slot “express payment” dei blocchi — con Stripe/PayPal classici i pulsanti
rapidi restano comunque disponibili, e nessun plugin installato dipendeva dai blocchi.
⚠️ È una configurazione di **pagina**, non di codice: va rifatta in produzione (vedi `go-live.md` §1b).

**Dove vive cosa**

| File | Contenuto |
|---|---|
| `src/wp-theme/woocommerce/**` | 31 override: `cart/`, `checkout/`, `myaccount/`, `order/`, `notices/`, `global/` |
| `src/wp-theme/includes/shop.php` | chrome di sezione, passi del flusso, chip carta, stato ordine, obiettivo spedizione gratuita, campi checkout, dequeue asset WC |
| `src/tailwind/components/shop.css` | primitive del flusso (`.cr-panel`, `.cr-line`, `.cr-thumb`, `.cr-qty`, `.cr-sumrow`, `.cr-optlist`, `.cr-ostat`, `.cr-steps`, `.cr-accnav`, `.cr-notice`) + skin del markup generato da WooCommerce |
| `src/js/components/shop.js` | stepper quantità, auto-update del carrello, riepilogo checkout ripiegato su mobile |
| `src/wp-theme/my-account.php` | cornice dell'area account (avvolge sia il login sia le sezioni) |

**Scelte di UX (e il perché)**

- **Chip condizione · lingua · foil su ogni riga.** Per una singola quegli attributi *sono* il
  prodotto: il carrello è l'ultimo punto in cui il cliente può verificare di aver preso la copia
  giusta. Vengono anche salvati come meta di riga all'acquisto, così l'ordine resta leggibile
  anche se poi il prodotto cambia o sparisce dal catalogo.
- **Scorte vere nello stepper.** Le singole sono spesso pezzi unici: il “+” si spegne al massimo
  disponibile e la riga dice “Ultimo pezzo disponibile”. La delusione non deve arrivare al checkout.
- **Il carrello si aggiorna da solo.** Il pulsante “Aggiorna carrello” resta solo come ripiego
  senza JavaScript. ⚠️ L'auto-update simula il click su quel pulsante: l'evento `wc_update_cart`
  serializza il form **senza** il parametro `update_cart` che il gestore di WooCommerce pretende,
  quindi le quantità andrebbero perse in silenzio.
- **Costi completi già nel carrello** (spedizione inclusa): i costi a sorpresa sono la prima causa
  di abbandono. Il campo coupon resta ripiegato — se è in evidenza la gente esce a cercare codici.
- **Checkout a due colonne**, pagamento in fondo alla colonna sinistra (è un'azione, non un
  accessorio), riepilogo sticky a destra; su mobile il riepilogo sale in cima **ripiegato**.
- **Header ridotto nel checkout** (solo logo + “pagamento sicuro”): la navigazione, lì, è una via
  d'uscita dall'ordine.
- **Conferma d'ordine = l'unico momento “lilla” del flusso**, seguito dai fatti su fondo chiaro e
  da “cosa succede adesso” in tre passi.
- **Registrazione giustificata** (chiudeva il TODO “Registration CTA”): segui l'ordine, indirizzi
  salvati, codice di benvenuto — i vantaggi *prima* del modulo, sia in account sia al checkout.
- **Niente placeholder che ripetono l'etichetta**; `address_2` ha finalmente un'etichetta visibile
  (“Interno, scala, presso”) invece di affidarsi al solo placeholder.

**Trappole trovate (da ricordare)**

1. **Tailwind pota anche `@layer components`.** Le regole che agganciano markup generato da
   WooCommerce sparivano dal CSS perché quelle classi non compaiono in `src/`. Risolto col
   **`safelist`** in `tailwind.config.js`: ogni nuovo aggancio va aggiunto lì.
2. **daisyUI `.checkbox` collideva con WooCommerce**, che usa quella classe sulle *label*: il testo
   delle spunte veniva schiacciato a 24px. Verificato che daisyUI **non fosse usata da nessun
   componente** (confronto fra le 325 classi del pacchetto e tutte quelle presenti nei sorgenti:
   zero corrispondenze reali) e **rimossa** — plugin, blocco di config, dipendenza npm.
   Veniva dal boilerplate iniziale (primo commit), non da una scelta di progetto.
   **−104 KB di CSS** (206 → 100 KB). Togliendola sono emerse due dipendenze nascoste:

   **2a · `theme.colors` SOSTITUISCE la palette di Tailwind**, non la estende: mancando le parole
   chiave di default spariva `stroke-current`, usata da tutte le icone SVG del sito (header,
   footer, carrello, checkout). Finora le teneva in piedi daisyUI. Aggiunte `current` e `inherit`
   alla palette in `tailwind.config.js`. ⚠️ Nei template il fallimento sarebbe stato **silenzioso**
   (utility inesistente = nessuna regola, nessun errore): l'ha fatto emergere solo un `@apply` in
   `shop.css`, che invece rompe il build.

   **2b · `css-mqpacker` raggruppava le media query nell'ordine in cui le incontrava.** Senza
   daisyUI a "seminare" l'ordine, il breakpoint `sm` (640px) finiva **dopo** `lg` (1024px) e lo
   sovrascriveva: le griglie della home restavano a 2 colonne su desktop. Risolto con
   `require('css-mqpacker')({ sort: true })` in `postcss.config.js` — ordine mobile-first
   ripristinato (640 → 768 → 1024 → 1280). **Era un bug latente**, mascherato per caso: chiunque
   avesse toccato l'ordine degli import CSS lo avrebbe fatto esplodere.

   Verifica: confronto **pixel-per-pixel** prima/dopo su home, prodotto, listato, istituzionale,
   404 e tutte le pagine del flusso → identiche, tranne la banda del ticker (animazione catturata
   a un offset diverso).
3. **I placeholder degli indirizzi li riscrive il JS** (`address-i18n.js`) dal locale “default”, che
   non passa da `woocommerce_get_country_locale`: la leva giusta è `woocommerce_default_address_fields`.
4. **Gli avvisi del checkout uscivano fuori dal layout**, perché stampati prima del template
   (`woocommerce_before_checkout_form_cart_notices`). Spostati sull'hook interno; sul ramo
   “carrello con errori” li stampa `cart-errors.php`.

**Rimosso**: `src/scss/_cart.scss` e `_my-account.scss` (skin legacy ormai sostituite).

### 3g · Aggiunta al carrello: drawer, card senza overlay, disponibilità reale (25/07/2026)

**Il problema di partenza.** Aggiungere al carrello non dava alcun riscontro sul punto in cui si
cliccava, e il pulsante "Aggiungi al carrello" appariva **sopra la foto** della card, coprendone un
terzo. Peggio: essendo `opacity: 0` fuori hover ma con i puntatori attivi, su touch era un bersaglio
invisibile — un tocco sulla carta la metteva nel carrello invece di aprirne la scheda.

**Card e tasche — niente copre più niente.**
- Via `.cr-qadd` (copriva l'artwork) e `.cr-pocket__pick` (copriva il prezzo).
- Il comando `.cr-add` sta nella riga del prezzo, **sempre visibile**: funziona anche dove `:hover`
  non esiste. Glifo = la stessa icona carrello dell'header ("finisce lì").
- La card non è più un `<a>` che avvolge tutto: è un `<div>`; il link vero è il titolo e si
  distende sulla card con `::after` (`.cr-card__link`), il comando gli sta sopra. HTML valido
  (niente controlli dentro un link), tastiera in ordine sensato, nessuna zona cliccabile invisibile.

**Drawer del carrello** (`woocommerce/cart/mini-cart.php` + `cr_cart_drawer()`): entra da destra
all'aggiunta con il recap di cosa è appena entrato (riga evidenziata "Appena aggiunto"), il resto
del carrello, subtotale e due strade avanti. Si apre anche dall'icona in header (che resta un link
vero: senza JS porta al carrello). Focus trap, Esc, blocco dello scroll, `aria-modal`.
Il contenuto è un **frammento AJAX di WooCommerce**: si rigenera da solo a ogni aggiunta o rimozione.

Da qui si **modificano le quantità e si rimuove**, senza uscire dalla pagina. WooCommerce non ha un
endpoint per la quantità del mini-carrello (sul carrello vero passa da un POST con reload), quindi
c'è il nostro `wc-ajax=cr_set_qty`: nonce, limite di scorte **riapplicato lato server** (l'attributo
`max` del campo è comodità, non garanzia), quantità 0 = rimozione, e in risposta i frammenti
standard. La posizione di scorrimento della lista viene conservata, altrimenti a ogni "+" si
tornerebbe in cima. Il carrello vero resta il posto per codici sconto e spedizione.
⚠️ I tasti − / + dello stepper vanno registrati **globalmente**: erano legati all'inizializzazione
della sola pagina carrello e nel drawer non facevano nulla.

**Scheda prodotto in AJAX**: niente più ricaricamento (si perdeva la posizione proprio dove sotto
ci sono le "altre versioni" della carta). Il `<form>` resta e funziona senza JavaScript.

**Errore in aggiunta: mai più sbattuti sulla scheda prodotto.**
Di suo WooCommerce risponde `{error, product_url}` e `add-to-cart.js` fa `window.location = product_url`.
Ora `woocommerce_cart_redirect_after_error` torna vuoto e il **motivo** viene mostrato nel drawer,
dove si è. ⚠️ Due trappole trovate: (a) la risposta d'errore contiene solo un flag, non il motivo —
serve un secondo giro sull'endpoint `wc-ajax=cr_notices`; (b) a carrello vuoto WooCommerce **non
persiste la sessione**, quindi l'avviso andava perso e il rifiuto restava muto: si forza il cookie
di sessione nel momento dell'errore. L'endpoint restituisce **solo** gli errori (altrimenti nel
drawer finivano anche messaggi di successo residui).

**Doppio click.** WooCommerce ascolta su `document.body`; per fermare un secondo click prima che
parta una seconda richiesta serve un listener in **fase di cattura** su `document`. Senza, tre click
rapidi facevano tre chiamate e le risposte successive cancellavano il messaggio appena mostrato
(la coda avvisi si svuota alla prima lettura). `pointer-events: none` non è un'alternativa: sulle
card il click passerebbe al link sotto e finirebbe sulla scheda prodotto.

**Disponibilità reale** (`cr_stock_left()`): scorte **meno** ciò che è già nel carrello di chi
guarda. Card, tasche e scheda prodotto mostrano la quantità vera ("2 disponibili", "Ultimo pezzo");
quando arriva a zero il comando si spegne e la riga dice **"Tutto nel carrello"** — non "Esaurito",
che farebbe temere di aver perso il pezzo. Dopo un'aggiunta il contatore si aggiorna senza
ricaricare (`updateStock`), e il server conferma lo stesso stato al caricamento successivo.

### 3h · Immagini prodotto e rimozione di One Piece (25/07/2026)

**⚠️ Le foto del catalogo NON stanno nella libreria media.** Arrivano dal plugin di sync come URL
remoti (`_ct_image` anteprima, `_ct_image_full` piena) e vengono iniettate filtrando
`woocommerce_product_get_image`. Conseguenza pratica: `has_post_thumbnail()` e
`get_the_post_thumbnail_url()` **restituiscono vuoto su quasi tutti i prodotti**. Usare sempre
`$product->get_image()`; per sapere se una foto esiste c'è `cr_product_has_image()`.

Due bug nati esattamente da lì:

- **Hero con tre carte bianche.** Il template usava `get_the_post_thumbnail_url()` e, in più, si
  fidava ciecamente dei 3 prodotti scelti in ACF — che dopo una re-importazione del catalogo **non
  esistevano più** (il campo conserva gli ID anche quando i post spariscono). Ora la vetrina tiene
  solo prodotti pubblicati e con foto, e i posti scoperti si riempiono da soli con i più recenti:
  l'hero è la prima cosa che si vede, non può dipendere da chi si ricorda di aggiornarlo.
  Stesso trattamento a `hero-drop`, che aveva lo stesso difetto.

- **Immagini sgranate nei listati.** Il plugin serve la piena solo alle size della scheda prodotto
  (`woocommerce_single`, `large`, `full`); griglie e tasche ricevevano l'**anteprima 180×180**
  mostrata a ~286 CSS px, cioè 572 px reali su retina: ingrandimento oltre 3×. `cr_full_res_image()`
  (filtro a priorità 20, dopo il plugin) preferisce `_ct_image_full` (**960×960**). Costa ~134 KB
  per foto invece di 10, ma è lo stesso file su tutto il sito — una sola copia in cache — e
  l'anteprima non basterebbe comunque a nessuna densità di schermo.

**One Piece rimosso** (non ancora in vendita). Tolto da `CR_GAMES` (`includes/routing.php`) — da cui
dipendono rotte, menu, landing e i campi ACF dell'hero di gioco — dalla riga della homepage, dai
fallback dei template, dal copy SEO e istituzionale, e dal dataset dei placeholder.
⚠️ `CR_ROUTING_VER` è stato portato a `'2'`: le rewrite restano in database finché non si
rigenerano, quindi senza bump `/one-piece/` avrebbe continuato a rispondere. Verificato: ora 404.
Per riattivarlo basta rimetterlo in `CR_GAMES` e ripristinare la sezione nel manifest (i punti sono
segnati da commenti nel codice). Nessun prodotto usava quella categoria.
Rinominato in locale il termine `pokemon` da "Pokemon" a "**Pokémon**" (il chip mostra il nome del
termine): ⚠️ da rifare in produzione.

## 4 · Vincoli operativi
- **MAI** commit/push/deploy senza ok esplicito del cliente (vedi CLAUDE.md).
- Prezzi/tassi/numeri nelle preview sono segnaposto: chiedere i valori reali.
- Tailwind scansiona solo `src/wp-theme`, `src/global-components`, `src/js`:
  le classi generate dinamicamente in PHP vanno scritte per esteso (mai concatenare suffissi).
