# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

CardsRift is a custom WordPress/WooCommerce theme for a trading card e-commerce site. Built with Webpack 5, Tailwind CSS 3.3 + SCSS, and vanilla JavaScript.

The repo contains only the theme source. The WordPress install lives in `public/` (gitignored); core and plugins (ACF Pro, WooCommerce) are updated via wp-admin — local first, verify, then production. Production is on Aruba shared hosting (FTP only); deploys ship only the built theme folder.

## Build Commands

```bash
npm run dev           # Dev mode: compiles Tailwind first, then watches Tailwind + webpack together
npm run wp-build      # Production build (Tailwind minified + webpack)
npm run deploy        # wp-build + FTP upload of the whole theme to Aruba (creds in gitignored .env.deploy)
```

- `dev` pre-compiles Tailwind before starting the watchers, so it's safe on a fresh clone (`src/tailwind/build/` is gitignored). `tailwind:*`/`webpack:watch` are internal helpers — don't run them directly.
- ESLint runs inside webpack builds via `eslint-webpack-plugin`; there is no standalone `npm run lint`.

## Architecture

**Source → Build pipeline:** `src/` is compiled by Webpack into `public/wp-content/themes/cardsrift/` (paths in `settings/environment.js`).

### Source Structure (`src/`)

- `wp-theme/` — PHP theme templates; helpers in `wp-theme/includes/`
- `global-components/` — Reusable components, each with its own PHP template + JS + SCSS
- `js/app.js` — Main JS entry point; components initialize via `documentReady` and `pageLoad` hooks (other hooks in `js/utils/` exist but are unused)
- `scss/main_global.scss` — Main SCSS entry
- `tailwind/` — Tailwind entry CSS and component files; compiled output in `tailwind/build/` (gitignored)
- `images/icons/sprite_icons/` — SVGs compiled into an SVG sprite; consumed in PHP via `<use xlink:href="...assets/images/sprite/sprite.svg#icon-name">`

### Homepage / component rendering pattern (manifest in codice — dal 17/07/2026)

**Struttura + copy = codice; backend ACF = solo dati DB, generato dal codice.** Ogni pagina ha un *manifest* PHP in `src/wp-theme/content/{slug}.php` che `return`a un array ordinato di sezioni; ciascuna ha `comp` (cartella in `global-components/`), `tema`, il copy in `__()` (traducibile), e opzionalmente `edit` (i pochi campi legati al DB: prodotti/immagini).

L'engine è `includes/page-builder.php`:
- `cr_render_page($slug)` — carica il manifest, per ogni sezione fonde il copy (dal codice) con i valori dei campi `edit` (letti da ACF sulla pagina), poi `set_query_var('component_data', $data)` → `get_template_part("global-components/$comp/template")`. I template leggono `$component_data` direttamente (NON esiste `variables.php`).
- `cr_register_builder_fields()` (hook `acf/init`) — genera i field group ACF **dal manifest** (solo sezioni con `edit`), così wp-admin mostra la pagina "già apparecchiata" e i campi sono versionati nel repo. Registra anche `data_uscita` (date_picker) sui prodotti.

`template_homepage.php` = `cr_render_page('home')`. `page.php` cerca `content/{slug}.php` (fallback su `the_content()`). Le sezioni dinamiche si auto-alimentano da helper in `rework.php` (`cr_grid_products`, `cr_preorder_products`, `cr_singole_products`, `cr_ticker_voci`) — niente selezione a mano. Valori scalari a bassa frequenza (%, numeri, URL) in `includes/config.php`. **Copy sempre in `__()/esc_html__()` con text domain `cardsrift`** (`.pot` in `languages/`, vedi setup i18n in `functions.php`).

### Component wiring is manual

Webpack copies only the **PHP** from `global-components/`. For a component's JS to ship, register it in `src/js/global-components.js`. Styles: **Tailwind-first** — utilities nel template + primitive condivise in `src/tailwind/components/design-system.css` (e `shop.css` per il flusso d'acquisto); SCSS (`src/scss/`) è SOLO legacy (header, PDP e listato vanilla, fino a Fase 2) — non aggiungere SCSS nuovi.

### Styling

Hybrid system: **Tailwind CSS** for utilities/layout + **SCSS** for legacy component styles.
*(DaisyUI era nel boilerplate iniziale ma non veniva usata da nessun componente: rimossa il 24/07/2026 — vedi `docs/rework-fase-1.md` §3f.)*

- Tailwind content globs scan only `src/wp-theme`, `src/global-components`, and `src/js` — Tailwind classes anywhere else get purged.
- Custom breakpoints: xs(375), sm(640), tb(768), md(960), lg(1024), xl(1280), 2xl(1600), 3xl(1800). `tb` and `md` are **not** aliases.
- `js/utils/constants.js` `GLOBAL_VARS` breakpoints are aligned to Tailwind's (sm 640 / md 960 / lg 1024 / xl 1280) — keep them in sync when touching either.
- Colors: black `#1d2125`, white `#F3F4F5` (+ `white-70`), purple `#8877b2`, `purple-light` `#b6a9d9`
- Fonts: Bylon (accent) and Metropolis (headings) self-hosted in `src/fonts/`; Adelle Sans (body) loads from a hardcoded Typekit link in `header.php`
- Custom Tailwind plugins: `.tw-container` (+ `-sm/-md/-full`), `.tw-section`, and `.tw-h1`–`.tw-h6` typography utilities that **also style bare `h1`–`h6` tags**

### Design system (rework 2026) — BASE OBBLIGATORIA per ogni lavorazione

- **Regole e principi: `docs/design-system.md` · variabili: `docs/design-system-tokens.md` · roadmap/stato: `docs/rework-fase-1.md`.** Leggili prima di costruire o modificare componenti.
- 4 temi per sezione via `data-th="dark|light|lilla|lilla2"` (campo ACF radio `tema`, letto da `cr_theme()`); token in `src/tailwind/components/themes.css`, primitive `.cr-*` in `design-system.css`, colori Tailwind `th-*` in config. **Mai colori hardcoded nei template**: solo `th-*`/`cr-*`.
- Card prodotto SOLO via `cr_product_card()` (`includes/rework.php`). Page builder: **manifest in codice** (`content/{slug}.php`) + engine `includes/page-builder.php` — vedi "Homepage / component rendering pattern". I componenti restano riutilizzabili su ogni pagina; il backend ACF tiene solo i dati DB (generati dal manifest), il copy è in codice e tradotto.
- Pagina vivente dei primitivi: template **“Design System”** (`template_styleguide.php`) — ogni componente nuovo si verifica lì.

### JS Libraries

jQuery (WordPress's copy — webpack `externals` maps `jquery` to the global `jQuery`; the `jquery` dep is declared in `wp_register_script`), Swiper 9.4 (installato ma al momento senza import — riservato agli slider futuri; importare `swiper/css` core only, mai `swiper-bundle.css`), ismobilejs. **GSAP 3 + ScrollTrigger** sono la libreria di motion del sito (dal 21/07/2026 — GSAP è gratis, tutti i plugin inclusi): tutti gli effetti vivono in `src/js/utils/effects.js` (`import gsap from 'gsap'` + `gsap/ScrollTrigger`, bundle-ati da webpack). Regole invariate: GPU-only (transform/opacity/filter), pointer solo con `(hover: hover)`, tutto spento con `prefers-reduced-motion`. Lottie, Plyr, Fancybox e Select2 restano **non** installati — don't import them.

### WordPress/WooCommerce

- ACF Pro for custom fields; "Theme Options" options page (header logos etc. come from option fields). ACF JSON save/load is redirected to `public/acf-json` (untracked); manual exports live in `/acf/`.
- WooCommerce standard product tabs and gallery zoom/lightbox are removed via `functions.php` filters; the product page is the theme's own `single-product.php`.
- Cache-busting: asset version = `filemtime()` of the built file (`script_and_style.php`) — updates automatically on every build/deploy
- PHP→JS bridge: `phpVars` object (baseUrl, permalink, templateDir, lang) localized in `script_and_style.php`

### Header, navigazione e ricerca (dal 25/07/2026)

Header a **due righe** (`header.php`): riga servizio 34px (solo desktop) + riga negozio 76px.
Il menu **non viene dai menu di WordPress** — quelli sono stati rimossi, `register_nav_menu` incluso:
si genera da `CR_GAMES × CR_TIPI_CARTE` via `cr_nav_games()` (`includes/nav.php`), così un gioco nuovo
compare da solo in barra, nel menu mobile e nei filtri di ricerca. In barra si usa
`cr_game_label_short()`. Le tendine desktop sono **CSS puro** (`group-hover` / `group-focus-within`);
il JS in `header.js` fa solo ritrazione allo scroll, pannello mobile e altezza dell'accordion.
Menu mobile sempre **dark** (`data-th="dark"` annidato) anche con barra chiara.

- ⚠️ `--header-h-desktop` è **110px** (34+76), mobile 75 (`tailwind/components/layout.css`): da lì
  dipendono il padding del `.wrapper`, gli `scroll-pt` e ogni colonna `sticky` di carrello,
  checkout, account e scheda prodotto. Cambiare l'altezza dell'header significa cambiare quella.
- ⚠️ **`.base` non ha gutter** (tolto da `_utils.scss`): il gutter lo mette chi sta dentro, con
  `tw-container tw-section`. Rimetterlo lì fa 24+24 e disallinea di nuovo l'header dalla pagina.
  Il fallback di `page.php` ha un contenitore proprio **perché da lì passano carrello, checkout e
  conferma d'ordine**, che sono pagine con shortcode: togliendolo perdono il margine.
- **Ricerca**: solo prodotti (`cr_scope_search`), ristretta al gioco corrente col parametro
  ⚠️ **`cr_g`** — mai `cr_game`, che è una query var del routing e la trasformerebbe in un archivio.
  Pagina risultati `search.php`; tendina di suggerimenti su `wc-ajax=cr_suggest`, che risponde con
  **HTML già pronto** (come i frammenti del mini-carrello), cerca **solo nel titolo** e legge le
  miniature da `_ct_image` — non da `$product->get_image()`, che `cr_full_res_image()` porterebbe
  a 960px.
- ⚠️ **`.cr-chip` nasce già con fondo `accsoft` e testo `acc`**: uno stato "attivo" fatto con quegli
  stessi token è invisibile, e `hover:border-*` colora un bordo che la primitiva non ha. Per il
  selezionato usare la pill piena (`bg-th-acc text-th-pg`), per l'hover un `ring-inset`. Le utility
  battono la primitiva **senza `!important`** (`@layer utilities` viene dopo `@layer components`).

### Flusso d'acquisto: carrello → checkout → account (dal 24/07/2026)

Le tre pagine transazionali sono **override PHP di WooCommerce** in `src/wp-theme/woocommerce/`
(niente livello `templates/` in mezzo, o WooCommerce non li carica). Logica condivisa in
`includes/shop.php`, vestito in `src/tailwind/components/shop.css`, comportamenti in
`src/js/components/shop.js`.

- **Carrello e Checkout usano gli SHORTCODE classici** (`[woocommerce_cart]`, `[woocommerce_checkout]`),
  non i blocchi: i blocchi non sono sovrascrivibili da template PHP. Se qualcuno rimette i blocchi
  in quelle pagine, tutti gli override smettono di applicarsi (la pagina non dà errore: torna
  semplicemente al look di WooCommerce).
- **Classi da NON rimuovere** dagli override: `woocommerce-cart-form`, `woocommerce-cart-form__contents`,
  `cart_item`, `product-remove`, `cart_totals`, `wc-empty-cart-message`, `woocommerce-checkout`,
  `woocommerce-checkout-review-order-table`, `woocommerce-checkout-payment`, `#place_order`,
  `#shipping_method`, `woocommerce-error/-message/-info`. Sono i selettori con cui `cart.js` e
  `checkout.js` sostituiscono i frammenti in AJAX: toglierle spegne gli aggiornamenti in silenzio.
- I CSS di WooCommerce sono **dequeue-ati** (`woocommerce_enqueue_styles` → array vuoto), insieme a
  select2/selectWoo (tendine native). Le utility che il suo JS dà per scontate (`.screen-reader-text`,
  overlay blockUI) sono in `shop.css`.
- **Identità della carta**: condizione/lingua/foil compaiono come chip in ogni riga (carrello,
  riepilogo, ordine) via `cr_item_chips_html()`, e vengono salvati come meta di riga all'acquisto
  (`cr_order_item_card_meta`) — così l'ordine resta leggibile anche se il prodotto cambia.
- **Aggiunta al carrello** (§3g del doc di fase): il comando `.cr-add` sta nella riga del prezzo e
  **non copre mai la foto**; la card è un `<div>` con link "disteso" sul titolo (`.cr-card__link`),
  mai un `<a>` che avvolge controlli. All'aggiunta si apre il **drawer** (`cr_cart_drawer()` +
  override `cart/mini-cart.php`, aggiornato come frammento AJAX), da cui si cambiano quantità e si
  rimuove: endpoint nostro `wc-ajax=cr_set_qty`, perché WooCommerce non ne ha uno per il mini-carrello.
  ⚠️ I tasti − / + dello stepper vanno registrati globalmente, non solo sulla pagina carrello.
  ⚠️ In caso di errore WooCommerce reindirizzerebbe alla scheda prodotto: il redirect è disinnescato
  (`woocommerce_cart_redirect_after_error`) e il motivo arriva dall'endpoint `wc-ajax=cr_notices`.
  ⚠️ Il click è protetto da un listener in **fase di cattura**: WooCommerce ascolta su `document.body`,
  quindi è l'unico punto da cui si può fermare un doppio invio.
- **Disponibilità**: usare `cr_stock_left()` (scorte − quantità già nel carrello), non
  `get_stock_quantity()`, ovunque si mostri o si limiti la quantità acquistabile.
- ⚠️ **Foto prodotto**: NON stanno nella libreria media, arrivano dal plugin di sync come URL remoti
  (`_ct_image` / `_ct_image_full`) iniettati via `woocommerce_product_get_image`. Quindi
  `has_post_thumbnail()` e `get_the_post_thumbnail_url()` risultano **vuoti**: usare sempre
  `$product->get_image()`, e `cr_product_has_image()` per sapere se una foto esiste.
  `cr_full_res_image()` forza la versione 960px anche nei listati (il plugin lì darebbe 180px).
- La barra "spedizione gratuita" (`cr_free_shipping_bar()`) si accende **da sola** se in
  WooCommerce → Spedizione esiste un metodo *free_shipping* attivo con importo minimo.

### Gateway di pagamento: WooPayments + PayPal (dal 26/07/2026)

Attivi `woocommerce-payments` (carta, Stripe Elements in un iframe) e `woocommerce-paypal-payments`
(PayPal + "paga a rate"). Portano CSS e markup propri, e il loro foglio si carica **dopo** il nostro.

- **La riga del metodo è una griglia**, non una label con il radio in `position: absolute`
  (`.cr-optlist` in `shop.css`): WooPayments rimette la label in `display: inline` e con
  l'absolute la riga collassava a 26px col pallino a penzoloni fuori dal bordo. Le poche regole
  che rispondono ai plugin stanno in fondo a `shop.css`, **fuori da `@layer components`** —
  dentro, Tailwind le poterebbe (`.payment-methods--logos`, `.wc-payment-form`, `.ppc-button-wrapper`
  non compaiono in `src/`).
- ⚠️ **`.cr-form fieldset` è il pannello "cambio password" dell'account**: un `<fieldset>` messo per
  accessibilità (gruppo di radio) se lo prende addosso e disegna un riquadro dentro il riquadro.
  Per quelli usare `cr-fieldset-bare`.
- **I bottoni PayPal nascono dentro il riquadro di PayPal**: il filtro
  `woocommerce_paypal_payments_checkout_button_renderer_hook` (in `cr_ppcp_buttons_hook()`) sposta il
  punto d'aggancio su un'azione nostra, che `payment-method.php` esegue dentro il `payment_box`.
  ⚠️ Spostarli via JS non è un'alternativa: sono iframe zoid, e ri-appenderli altrove li ricarica
  e li rompe. Il `payment_box` va aperto anche senza campi né descrizione, o non hanno dove nascere.
- **Nel carrello i bottoni PayPal non ci sono: si passa sempre dal checkout.**
  `cr_ppcp_cart_buttons_hook()` manda il loro punto d'aggancio su un'azione che non eseguiamo mai.
  ⚠️ Il messaggio "paga in 3 rate" del carrello aveva come default lo stesso hook: `cr_ppcp_cart_message_hook()`
  lo rimette su `woocommerce_proceed_to_checkout`, altrimenti sparisce anche quello. Nel drawer non
  compaiono perché `cart/mini-cart.php` non esegue `woocommerce_widget_shopping_cart_after_buttons`.
- ⚠️ Gli hook `woocommerce_review_order_before/after_payment` restano dove li mette WooCommerce,
  **fuori da `#payment`** (lì esce il messaggio "paga in 3 rate"). Per lo stesso motivo la fascia
  "connessione cifrata / 24-48h / imballo" sta in coda a `payment.php` **fuori dal div e solo se
  `!wp_doing_ajax()`**: durante `update_order_review` WooCommerce rimpiazza
  `.woocommerce-checkout-payment` con tutto l'output del file, e ciò che sta fuori dal div si
  duplicherebbe a ogni aggiornamento dei totali.
- ⚠️ **Niente `gap` nella colonna sinistra del checkout**: i gateway ci infilano contenitori vuoti
  (express checkout, Apple/Google Pay) e ogni div invisibile valeva 32px di buco.
- Titoli e testi dei gateway arrivano in inglese e non passano dai nostri `.po`: li traduce
  `cr_gateway_copy()` in `includes/shop.php`. ⚠️ La descrizione di PayPal **non** si cambia con
  `$gateway->description` — il suo `get_description()` legge dalle opzioni del plugin: serve il
  filtro `woocommerce_paypal_payments_gateway_description`.
- Aspetto e posizione dei bottoni PayPal (uno o due, colore, forma) e delle scritte "paga a rate"
  si regolano in **WooCommerce → PayPal**, non da CSS: sono iframe di PayPal.

⚠️ **Tailwind pota anche `@layer components`**: una regola che aggancia markup generato da
WooCommerce (`.woocommerce-privacy-policy-text`, `.wc-item-meta`, `.blockUI`…) sparisce dal CSS
perché quella classe non compare in `src/`. Ogni nuovo aggancio va aggiunto al **`safelist`** in
`tailwind.config.js`, altrimenti si perde silenziosamente al build.

⚠️ **Attenzione ai nomi di classe generici**: WooCommerce marca il suo markup con classi comuni
(`checkbox`, `input-text`, `button`, `form-row`…). È così che daisyUI — presente nel boilerplate ma
mai usata — rompeva le spunte del checkout: il suo componente `.checkbox` (24×24px) colpiva le
*label* di WooCommerce. daisyUI è stata rimossa; se in futuro si aggiunge una libreria di componenti,
verificare le collisioni proprio su queste pagine.

### Consenso cookie: iubenda (dal 27/07/2026)

Tutto passa da `includes/iubenda.php`; gli ID stanno in `includes/config.php`
(`CR_IUBENDA_SITE_ID`, `CR_IUBENDA_WIDGET_ID`). Il banner è il **primo script del `<head>`**
(`header.php`), **sincrono di proposito**: è da lì che iubenda ferma ciò che installa
tracciatori. Spostarlo in fondo o metterlo `async` non dà errori — semplicemente il blocco
preventivo arriva dopo, quando i tracciatori sono già partiti. `iubenda.js` (le informative
che si aprono in sovrapposizione) è invece enqueue-ato **una volta sola**, async, in coda:
non ripetere lo snippet inline accanto a ogni link, come suggerisce iubenda.

- **La configurazione non è nel repo**: testi, colori, posizione e servizi bloccati vivono
  dentro `widgets/<id>.js`, generato dal pannello iubenda, e arrivano al sito da soli senza
  build né deploy. Si guarda con `curl` a quell'URL. Il banner nasce **già scuro** (fondo
  nero, testo bianco, in alto al centro): il tema non lo veste.
- **Il blocco preventivo NON tocca PayPal, e non c'è niente da configurare** (verificato il
  27/07/2026 leggendo il widget). `window.cmp_iub_vendors_purposes` mappa ogni servizio a una
  finalità e il blocker esenta in partenza chi ha solo la 1 — "Necessari" — prima di guardare
  il consenso: `function h(e){return !e || "1"===e}`. PayPal (`"40":"1"`, gestione pagamenti)
  sta lì, con Tag Manager e Cloudflare. Stripe/WooPayments e Typekit non sono proprio in lista.
  ⚠️ La lista di domini nel widget (174 voci, 11 servizi) è il **catalogo standard** di iubenda,
  non i servizi del sito: contiene YouTube, Vimeo, Facebook e AdSense, che qui non esistono.
  Dice cosa il blocker sa riconoscere, non cosa blocca. Gli unici con consenso obbligato sono
  Analytics (finalità 4), Google Fonts/FontAwesome (3) e social/adv (4 e 5): **aggiungendo un
  giorno Analytics, verrebbe bloccato da solo fino al consenso** — che è il comportamento voluto.
- ⚠️ **Il bottone flottante "preferenze" non si può spegnere** con il piano attuale
  (`full_customization: false`): sta fisso in basso a destra, cioè sopra `.cr-stickybar`.
  Lo scarto è in `tailwind/components/iubenda.css`, **fuori da `@layer components`** — e
  quel file, non il pannello, è l'unico posto da cui si sistema.
- Nel footer i link usano `cr_iubenda_link()` con `iubenda-nostyle`: senza, iubenda sostituisce
  il testo con il suo badge bianco. `Preferenze cookie` è un `<button>` con
  `iubenda-cs-preferences-link` (la classe funziona su qualsiasi elemento) ed è l'unico modo,
  per legge, di revocare il consenso già dato.
- Al checkout WooCommerce cercherebbe una **pagina WordPress** per l'informativa e, non
  trovandola, lascia "informativa sulla privacy" come testo morto: il link a iubenda lo
  riattacca `cr_iubenda_wc_privacy_text()`, che si fa da parte se un giorno quella pagina
  esistesse davvero.

### Mobile e tablet (audit 25/07/2026)

Il negozio si usa soprattutto dal telefono. Quello che segue è la roba che *non* si deduce
guardando il codice, e che si rompe di nuovo se la si tocca senza sapere perché c'è.

- ⚠️ **La taglia dei campi e dei bersagli la decide il PUNTATORE, non la larghezza.** In fondo a
  `tailwind/components/shop.css` c'è un unico blocco `@media (pointer: coarse)` che porta i campi
  a **16px** e i comandi a **44px**. È **fuori da `@layer components`** di proposito (Tailwind lo
  poterebbe: aggancia anche markup WooCommerce). Non riportarlo a un breakpoint di larghezza:
  **un iPad in orizzontale è 1024px CSS — cioè `lg`, cioè "desktop" — ma resta touch**, e lì
  tornerebbero il campo a 14px (Safari iOS zooma da solo sotto i 16 e non torna più indietro) e
  i bersagli a 32-40px. `:not([type="hidden"])` nel selettore non filtra niente: pareggia la
  specificità delle regole `.cr-form input[type="…"]` scritte sopra.
- ⚠️ **`!w-auto` su una `<select>` allarga la pagina.** Sulle espansioni Magic l'opzione più lunga
  è di 60 caratteri: la select misurava 427px, il viewport diventava 451 e il browser
  **rimpiccioliva tutto il listato all'86%** — senza errori, senza scroll orizzontale, solo tutto
  più piccolo. Nei filtri (`partials/listing.php`) la larghezza è vincolata fino a `tb`.
- ⚠️ **Lo spazio per la barra fissa lo riserva il FOOTER**, non uno spaziatore in pagina:
  `body.cr-has-stickybar` (da `cr_body_stickybar_class()` in `includes/shop.php`) →
  `.cr-has-stickybar footer { padding-bottom }` in `shop.css`. Con lo spaziatore dentro `cart.php`
  la barra copriva comunque le icone di pagamento, che stanno *dopo*, nel footer.
- ⚠️ **`viewport-fit=cover` nel meta viewport serve**: senza, `env(safe-area-inset-*)` vale 0 e
  tutto il CSS già scritto per la home-indicator degli iPhone non fa niente.
- ⚠️ **Il menu mobile sta FUORI da `<header>`**. L'header si ritrae con `transform`, e un antenato
  trasformato diventa il blocco contenitore dei discendenti `position: fixed`: rimettendo il
  pannello dentro, si ancorerebbe alla barra da 76px invece che allo schermo.
- **Griglie prodotto: 2 colonne già sul telefono** (design-system §4). I componenti della home
  erano a `grid-cols-1` e mostravano un prodotto per schermata. Scala: `2 → tb:3 → lg:4` (tasche
  singole `2 → sm:3 → tb:4 → lg:6`).
- **`tb`(768) è il livello tablet**: griglie, footer a 2 colonne, blocchi a due colonne, PDP a due
  colonne. Carrello e checkout restano a colonna singola fino a `lg`: a 768 il riepilogo da 380px
  lascerebbe 316px alle righe.
- **Filtri del listato**: `<details open>` + `listingFilters.js` che lo richiude **solo** su
  schermo stretto e **solo** se nessun filtro è attivo. Parte aperto nel markup perché senza
  JavaScript deve restare com'era.
- **Barra d'acquisto della PDP** (`.cr-buybar`, `lg:hidden`): non ricrea l'aggiunta, inoltra il
  tocco al pulsante vero dentro `#cr-buybox`. Senza JS resta un'ancora che porta alla buy-box.
- `hoverOnlyWhenSupported: true` in `tailwind.config.js` → ogni `hover:*` esce dentro
  `@media (hover: hover)`; le primitive `.cr-*` con `:hover` sono avvolte a mano. Su touch il
  `:hover` restava "appiccicato" dopo il tap.
- `html { overflow-x: clip }` (in `themes.css`) è una rete di sicurezza: **`clip`, non `hidden`** —
  `hidden` su `html`/`body` crea un contenitore di scroll e spegne i `position: sticky` di
  carrello, checkout e scheda prodotto.
- **Limiti accettati, non dimenticanze**: le foto restano quelle piene (960px, ~1,5 MB per listato
  di singole) con solo il lazy-load reso uniforme — scelta esplicita per la qualità; le tendine
  desktop del catalogo si attivano a `lg`, quindi su un tablet touch a 1024px il tap sul gioco
  porta alla landing invece di aprire il pannello (nessun vicolo cieco: la landing ha le due porte).

### ⚠️ Sync CardTrader: le scritture partono solo dalla produzione

`cardsrift-sync` è una sincronizzazione **a due vie**: `add_action('woocommerce_product_set_stock', …)`
accoda un push che fa `PUT products/{id}` su CardTrader con la quantità locale. Le inserzioni
sono reali, e il token API vive nel database — quindi anche l'installazione locale ce l'ha.
Un ordine di prova in locale riscrive quello che hai davvero in vendita (successo il 25/07/2026).

Dal 25/07/2026 c'è `crs_ct_writes_allowed()` (`includes/cardtrader.php`): fuori dalla produzione
**le letture restano libere** (servono a import e aggancio blueprint, che si lavorano in locale)
e **POST/PUT/DELETE vengono respinti** — nel transport `crs_ct_send()`, che è il punto obbligato,
più un'uscita anticipata su hook stock, push a blocchi, autopricer e cancellazione da cestino.
La pagina CardTrader in wp-admin mostra un avviso, così il push non sembra rotto.

⚠️ **`wp_get_environment_type()` da solo non basta**: senza `WP_ENVIRONMENT_TYPE` in wp-config
il core risponde `production` anche su localhost (verificato qui). Il controllo che regge è
l'host del sito. Per forzare un push da locale: `define('CRS_CT_ALLOW_WRITES', true)`.

## Naming Conventions

| Type | Convention | Example |
|------|-----------|---------|
| JS files (components) | camelCase | `inputInit.js` |
| Global components | folder per component, dash-named | `global-components/griglia-prodotti/` |
| PHP templates | mixed — match nearby files | `template_homepage.php`, `single-product.php` |

## Git Workflow

Never commit, push, or create PRs — the user handles all git operations. Code review happens in VS Code.

**Il database locale sale UNA VOLTA SOLA, al lancio — poi mai più.** Dopo la prima messa online la
produzione è l'unica fonte di verità del DB: ci vivono ordini, clienti e giacenze reali, e un import
dal locale li cancellerebbe. Se servono dati veri in sviluppo si porta una copia **giù** (prod →
locale), mai su. `scripts/deploy.sh` è già sicuro (mirror del solo tema, niente DB): il rischio è
solo manuale — `wp db export` + import, o un plugin di migrazione. Non si fa. Vedi `docs/go-live.md`.

**Never write to production, ever, without an explicit go-ahead in the user's own words** — no `npm run deploy`, `scripts/deploy.sh`, or any FTP write (`mirror`, `put`, `rm`) toward the Aruba server. This holds even during incidents: if production is broken, diagnose read-only, propose the exact command, and WAIT for the user to approve or run it themselves. Read-only FTP operations (`cls`, listings) are fine. The `/deploy-theme` skill wraps deploys with a dry-run-first flow.

## Config Files

- `tailwind.config.js` — Custom breakpoints, colors, container/typography plugins, `safelist` per il markup generato da WooCommerce.
  ⚠️ `theme.colors` **sostituisce** la palette di Tailwind: `transparent`/`current`/`inherit` sono riportate a mano.
  Toglierle fa sparire `stroke-current` (tutte le icone SVG) **senza alcun errore di build**.
- `postcss.config.js` — ⚠️ `css-mqpacker` gira con `{ sort: true }`, e non è opzionale: raggruppa le media
  query nell'ordine in cui le incontra, quindi senza `sort` il breakpoint `sm` può finire dopo `lg` e
  sovrascriverlo (sintomo: `lg:grid-cols-4` ignorato, griglie a 2 colonne su desktop).
- `.eslintrc` — Airbnb config; `no-console` and `no-unused-vars` are off; tab indent, single quotes
- All builds run with `NODE_ENV=wp` — webpack's `isProduction` check and `GLOBAL_VARS.projectDevStatus` are effectively dead code
