# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

CardsRift is a custom WordPress/WooCommerce theme for a trading card e-commerce site. Built with Webpack 5, Tailwind CSS 3.3 + SCSS, and vanilla JavaScript.

## Build Commands

```bash
npm run wp-dev        # Dev mode: copies files to theme directory + watch
npm run wp-build      # Production build
npm run dev-tailwind  # Compile Tailwind CSS only
npm run dev           # Webpack watch mode
npm run stats         # Bundle analysis
```

## Architecture

**Source → Build pipeline:** `src/` is compiled by Webpack into `public/wp-content/themes/cardsrift/`.

### Source Structure (`src/`)

- `wp-theme/` — PHP theme templates (`functions.php`, `header.php`, `footer.php`, page templates)
- `wp-theme/includes/` — PHP helpers: `script_and_style.php`, `helpers.php`, `image_size.php`, `acf.php`
- `wp-theme/woocommerce/` — WooCommerce template overrides
- `global-components/` — Reusable components, each with its own PHP template + JS + SCSS (hero, footer, sliders)
- `js/app.js` — Main JS entry point; initializes components via lifecycle hooks (`documentReady`, `pageLoad`, `onWindowScroll`, `onWindowResize`)
- `js/components/` — Page-level JS functions (camelCase naming)
- `js/utils/constants.js` — Global variables and breakpoint references
- `scss/main_global.scss` — Main SCSS entry; partials use `_underscore.scss` naming
- `tailwind/` — Tailwind entry CSS and component files; compiled output in `tailwind/build/`
- `images/icons/sprite_icons/` — SVGs compiled into an SVG sprite via webpack

### Styling

Hybrid system: **Tailwind CSS** (with DaisyUI) for utilities/layout + **SCSS** for complex component styles.

Key design tokens:
- Colors: black `#1d2125`, white `#F3F4F5`, purple `#8877b2`, light-purple `#b6a9d9`
- Custom breakpoints: xs(375), sm(640), tb/md(768), lg(1024), xl(1280), 2xl(1600), 3xl(1800)
- Fonts: Bylon (accent), Metropolis (headings), Adelle Sans (body)
- Container class: `.tw-container` (custom Tailwind plugin)

### JS Libraries Available

jQuery (global), Swiper 9.4, GSAP 3.12, Lottie Web, Plyr, Fancybox, Select2.

### WordPress/WooCommerce

- ACF (Advanced Custom Fields) for custom fields and options pages
- WooCommerce standard tabs (description, reviews, additional info) are removed; custom product templates used
- Cache-busting: timestamp on localhost, fixed version string in production
- Configuration in `settings/environment.js` controls source/output paths

## Naming Conventions

| Type | Convention | Example |
|------|-----------|---------|
| JS files (components) | camelCase | `inputInit.js` |
| SCSS partials | `_underscore-prefix` | `_variables.scss` |
| PHP templates | lowercase-dashes | `single-product.php` |
| Global components | folder per component | `global-components/hero/` |

## Config Files

- `webpack.config.js` — Base webpack config; `settings/webpack.wp_dev.config.js` and `webpack.wp_prod.config.js` for env-specific
- `tailwind.config.js` — Custom breakpoints, colors, DaisyUI, container plugin
- `postcss.config.js` — Autoprefixer, CSSNano, MQ Packer
- `.babelrc` — ES6+ transpilation with dynamic import support
- `.eslintrc` — Airbnb config; `no-console` and `no-unused-vars` are off
