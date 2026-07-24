# CardsRift

Custom WordPress/WooCommerce theme for a trading card e-commerce site. Webpack 5, Tailwind CSS 3 + SCSS, vanilla JS.

## Requirements

- Node.js 18+
- A local PHP/MySQL stack (developed on MAMP, served from `public/`)
- A copy of the WordPress install (files + database) from production in `public/` — the repo contains only the theme source

## Setup

```bash
npm install
npm run dev        # compiles src/ into public/wp-content/themes/cardsrift/ and watches
```

## Commands

| Command | Description |
|---------|-------------|
| `npm run dev` | Dev mode: compiles Tailwind, then Tailwind + webpack watch, copies to theme directory |
| `npm run wp-build` | Production build (Tailwind minified + webpack) |
| `npm run deploy` | Production build + FTP deploy of the theme to Aruba |

## Structure

```
public/                  # WordPress install (gitignored — managed via wp-admin, not the repo)
settings/                # Webpack env configs + source/output paths (environment.js)
acf/                     # Manual ACF field-group exports
src/
├── wp-theme/            # PHP theme templates (includes/ = WP helpers, woocommerce/ = overrides)
├── global-components/   # Reusable components: PHP template + JS + SCSS per folder
├── js/                  # app.js entry, components/, utils/
├── scss/                # main_global.scss entry + partials
├── tailwind/            # Tailwind entry CSS; compiled output in build/ (gitignored)
├── fonts/               # Self-hosted Bylon & Metropolis (Adelle Sans via Typekit)
└── images/icons/sprite_icons/   # SVGs compiled into a sprite
```

Homepage pages are assembled from ACF flexible-content layouts rendered via `global-components/` — see `CLAUDE.md` for the full pattern and component-wiring steps.

## Updating WordPress, plugins & deploying

- **Core/plugin updates**: via wp-admin — update **local first** (MAMP), verify the theme still works, then update **production**. ACF Pro updates require the license key registered on the site. Before major core updates in production, export the DB from Aruba's phpMyAdmin as a backup.
- **Theme deploy**: production is on Aruba (FTP only). Deploy ships only `public/wp-content/themes/cardsrift/` — run `npm run deploy` (builds, then FTP mirror via lftp; credentials in the gitignored `.env.deploy`, see `.env.deploy.example`). Note: the build renews every file's timestamp, so each deploy re-uploads the whole theme (~95 files, a few MB).
- The theme travels **only from the repo to production**, never the other way — git is the source of truth for the theme.
