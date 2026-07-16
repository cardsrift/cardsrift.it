# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

CardsRift is a custom WordPress/WooCommerce theme for a trading card e-commerce site. Built with Webpack 5, Tailwind CSS 3.3 + SCSS, and vanilla JavaScript.

The repo contains only the theme source. The WordPress install lives in `public/` (gitignored); core and plugins (ACF Pro, WooCommerce) are updated via wp-admin — local first, verify, then production. Production is on Aruba shared hosting (FTP only); deploys ship only the built theme folder.

## Build Commands

```bash
npm run wp-dev        # Dev mode: copies files to theme directory + watch (use this)
npm run wp-build      # Production build
npm run dev-tailwind  # Compile Tailwind CSS only
npm run deploy        # wp-build + FTP upload of the whole theme to Aruba (creds in gitignored .env.deploy)
```

- Fresh-clone gotcha: `src/tailwind/build/` is gitignored, so `npm run dev` (webpack watch alone) fails until Tailwind compiles. Use `wp-dev`, which runs both.
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

Webpack copies only the **PHP** from `global-components/`. For a component's JS and SCSS to ship:
- register its JS in `src/js/global-components.js`
- `@import` its SCSS in `src/scss/main_global.scss`

### Styling

Hybrid system: **Tailwind CSS** (with DaisyUI, `themes: false`) for utilities/layout + **SCSS** for complex component styles.

- Tailwind content globs scan only `src/wp-theme`, `src/global-components`, and `src/js` — Tailwind classes anywhere else get purged.
- Custom breakpoints: xs(375), sm(640), tb(768), md(960), lg(1024), xl(1280), 2xl(1600), 3xl(1800). `tb` and `md` are **not** aliases.
- `js/utils/constants.js` `GLOBAL_VARS` breakpoints are aligned to Tailwind's (sm 640 / md 960 / lg 1024 / xl 1280) — keep them in sync when touching either.
- Colors: black `#1d2125`, white `#F3F4F5` (+ `white-70`), purple `#8877b2`, `purple-light` `#b6a9d9`
- Fonts: Bylon (accent) and Metropolis (headings) self-hosted in `src/fonts/`; Adelle Sans (body) loads from a hardcoded Typekit link in `header.php`
- Custom Tailwind plugins: `.tw-container` (+ `-sm/-md/-full`), `.tw-section`, and `.tw-h1`–`.tw-h6` typography utilities that **also style bare `h1`–`h6` tags**

### JS Libraries

jQuery (WordPress's copy — webpack `externals` maps `jquery` to the global `jQuery`; the `jquery` dep is declared in `wp_register_script`), Swiper 9.4 (import `swiper/css` core only, not `swiper-bundle.css`), ismobilejs. GSAP, Lottie, Plyr, Fancybox, and Select2 are **not** installed — don't import them.

### WordPress/WooCommerce

- ACF Pro for custom fields; "Theme Options" options page (header logos etc. come from option fields). ACF JSON save/load is redirected to `public/acf-json` (untracked); manual exports live in `/acf/`.
- WooCommerce standard product tabs and gallery zoom/lightbox are removed via `functions.php` filters; the product page is the theme's own `single-product.php`. There are **no** WC template overrides — if you add any, they go in `wp-theme/woocommerce/single-product/...` (NO `templates/` level in between, or WooCommerce never loads them).
- Cache-busting: asset version = `filemtime()` of the built file (`script_and_style.php`) — updates automatically on every build/deploy
- PHP→JS bridge: `phpVars` object (baseUrl, permalink, templateDir, lang) localized in `script_and_style.php`

## Naming Conventions

| Type | Convention | Example |
|------|-----------|---------|
| JS files (components) | camelCase | `inputInit.js` |
| Global components | folder per component, dash-named | `global-components/highlight-slider/` |
| PHP templates | mixed — match nearby files | `template_homepage.php`, `single-product.php` |

## Git Workflow

Never commit, push, or create PRs — the user handles all git operations. Code review happens in VS Code.

Never run `npm run deploy` (or `scripts/deploy.sh`) unless the user explicitly asks — it publishes to production. The `/deploy-theme` skill wraps it with a dry-run-first flow.

## Config Files

- `tailwind.config.js` — Custom breakpoints, colors, DaisyUI, container/typography plugins
- `.eslintrc` — Airbnb config; `no-console` and `no-unused-vars` are off; tab indent, single quotes
- All builds run with `NODE_ENV=wp` — webpack's `isProduction` check and `GLOBAL_VARS.projectDevStatus` are effectively dead code
