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

### Homepage / component rendering pattern

`template_homepage.php` loops the ACF flexible-content field `components`: each layout's `acf_fc_layout` name has underscores converted to dashes, then renders `get_template_part("global-components/$name/template")` with data passed via the `component_data` query var (read by each component's `variables.php`). ACF layout names (after underscore→dash conversion) must map to component folder names, so name folders with dashes (`highlight-slider`).

### Component wiring is manual

Webpack copies only the **PHP** from `global-components/`. For a component's JS to ship, register it in `src/js/global-components.js`. Styles: **Tailwind-first** — utilities nel template + primitive condivise in `src/tailwind/components/design-system.css`; SCSS (`src/scss/`) è SOLO legacy (skin WooCommerce + header, fino a Fase 2) — non aggiungere SCSS nuovi.

### Styling

Hybrid system: **Tailwind CSS** (with DaisyUI, `themes: false`) for utilities/layout + **SCSS** for complex component styles.

- Tailwind content globs scan only `src/wp-theme`, `src/global-components`, and `src/js` — Tailwind classes anywhere else get purged.
- Custom breakpoints: xs(375), sm(640), tb(768), md(960), lg(1024), xl(1280), 2xl(1600), 3xl(1800). `tb` and `md` are **not** aliases.
- `js/utils/constants.js` `GLOBAL_VARS` breakpoints are aligned to Tailwind's (sm 640 / md 960 / lg 1024 / xl 1280) — keep them in sync when touching either.
- Colors: black `#1d2125`, white `#F3F4F5` (+ `white-70`), purple `#8877b2`, `purple-light` `#b6a9d9`
- Fonts: Bylon (accent) and Metropolis (headings) self-hosted in `src/fonts/`; Adelle Sans (body) loads from a hardcoded Typekit link in `header.php`
- Custom Tailwind plugins: `.tw-container` (+ `-sm/-md/-full`), `.tw-section`, and `.tw-h1`–`.tw-h6` typography utilities that **also style bare `h1`–`h6` tags**

### Design system (rework 2026) — BASE OBBLIGATORIA per ogni lavorazione

- **Regole e principi: `docs/design-system.md` · variabili: `docs/design-system-tokens.md` · roadmap/stato: `docs/rework-fase-1.md`.** Leggili prima di costruire o modificare componenti.
- 4 temi per sezione via `data-th="dark|light|lilla|lilla2"` (campo ACF radio `tema`, letto da `cr_theme()`); token in `src/tailwind/components/themes.css`, primitive `.cr-*` in `design-system.css`, colori Tailwind `th-*` in config. **Mai colori hardcoded nei template**: solo `th-*`/`cr-*`.
- Card prodotto SOLO via `cr_product_card()` (`includes/rework.php`). Page builder: flexible content `components` — loop in `template_homepage.php` e `page.php` (componenti riutilizzabili su ogni pagina).
- Pagina vivente dei primitivi: template **“Design System”** (`template_styleguide.php`) — ogni componente nuovo si verifica lì.

### JS Libraries

jQuery (WordPress's copy — webpack `externals` maps `jquery` to the global `jQuery`; the `jquery` dep is declared in `wp_register_script`), Swiper 9.4 (installato ma al momento senza import — riservato agli slider futuri; importare `swiper/css` core only, mai `swiper-bundle.css`), ismobilejs. GSAP, Lottie, Plyr, Fancybox, and Select2 are **not** installed — don't import them. Effetti: solo il modulo `src/js/utils/effects.js` (vedi docs/rework-fase-1.md §3d), niente librerie di animazione.

### WordPress/WooCommerce

- ACF Pro for custom fields; "Theme Options" options page (header logos etc. come from option fields). ACF JSON save/load is redirected to `public/acf-json` (untracked); manual exports live in `/acf/`.
- WooCommerce standard product tabs and gallery zoom/lightbox are removed via `functions.php` filters; the product page is the theme's own `single-product.php`. There are **no** WC template overrides — if you add any, they go in `wp-theme/woocommerce/single-product/...` (NO `templates/` level in between, or WooCommerce never loads them).
- Cache-busting: asset version = `filemtime()` of the built file (`script_and_style.php`) — updates automatically on every build/deploy
- PHP→JS bridge: `phpVars` object (baseUrl, permalink, templateDir, lang) localized in `script_and_style.php`

## Naming Conventions

| Type | Convention | Example |
|------|-----------|---------|
| JS files (components) | camelCase | `inputInit.js` |
| Global components | folder per component, dash-named | `global-components/griglia-prodotti/` |
| PHP templates | mixed — match nearby files | `template_homepage.php`, `single-product.php` |

## Git Workflow

Never commit, push, or create PRs — the user handles all git operations. Code review happens in VS Code.

**Never write to production, ever, without an explicit go-ahead in the user's own words** — no `npm run deploy`, `scripts/deploy.sh`, or any FTP write (`mirror`, `put`, `rm`) toward the Aruba server. This holds even during incidents: if production is broken, diagnose read-only, propose the exact command, and WAIT for the user to approve or run it themselves. Read-only FTP operations (`cls`, listings) are fine. The `/deploy-theme` skill wraps deploys with a dry-run-first flow.

## Config Files

- `tailwind.config.js` — Custom breakpoints, colors, DaisyUI, container/typography plugins
- `.eslintrc` — Airbnb config; `no-console` and `no-unused-vars` are off; tab indent, single quotes
- All builds run with `NODE_ENV=wp` — webpack's `isProduction` check and `GLOBAL_VARS.projectDevStatus` are effectively dead code
