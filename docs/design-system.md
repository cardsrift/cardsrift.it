# CardsRift · Design System — fonte di verità

> **Questo documento è LA BASE per ogni lavorazione futura sul tema.** Ogni nuovo componente,
> pagina o modifica deve usare questi token, queste primitive e queste regole — mai valori a mano.
> Versione vivente: pagina con template **“Design System”** (`template_styleguide.php`), che
> renderizza i primitivi con il CSS di produzione. Roadmap e stato lavori: `docs/rework-fase-1.md`.

## 1 · Identità

- **Motto**: “Il tuo portale per il collezionismo” (SEO: “il portale per le carte collezionabili”).
  Il logo è un *rift*/portale: il concetto di portale è brand, usalo.
- **Mood**: «se stai cercando qualcosa, qua lo trovi» — detto piano. Niente tipografia urlata,
  niente pressione commerciale all'ingresso. Il catalogo è curato e piccolo: sfogliare > cercare.
- **Tono di voce**: prima persona plurale, da collezionisti (“controlliamo una a una”, “come se
  fosse per la nostra collezione”). Bylon Italic = la “grafia” del negozio per note calde.

## 2 · Token — i 4 temi (`src/tailwind/components/themes.css`)

Ogni sezione indossa un tema via `data-th` (campo ACF radio `tema`). Il **dark è la base**;
light per respiro; **lilla = momento** (max 1–2 sezioni/pagina); lilla2 per pop leggeri.

| Token (var) | Tailwind | dark | light | lilla | lilla2 |
|---|---|---|---|---|---|
| `--cr-pg` sfondo | `bg-th-pg` | `#1d2125` | `#F3F4F5` | `#8877b2` | `#b6a9d9` |
| `--cr-ink` testo | `text-th-ink` | `#F3F4F5` | `#1d2125` | `#fff` | `#1d2125` |
| `--cr-muted` secondario | `text-th-muted` | `#a6a3b2` | `#5d5a68` | `rgba(255,255,255,.82)` | `#463d5c` |
| `--cr-surface` superficie | `bg-th-surface` | `#262b32` | `#fff` | `rgba(255,255,255,.13)` | `rgba(255,255,255,.5)` |
| `--cr-acc` accent testo/prezzi | `text-th-acc` | `#b6a9d9` | `#6b5a99` | `#fff` | `#4d3f73` |
| `--cr-btn` CTA solida | (in `.cr-btn-solid`) | `#b6a9d9`/ink scuro | `#6b5a99`/bianco | `#1d2125`/chiaro | `#1d2125`/chiaro |
| `--cr-line` bordi | `border-th-line` | bianco 10% | nero 10% | bianco 20% | nero 14% |
| `--cr-ok` / `--cr-warn` | `text-th-ok/warn` | declinati per tema (scorte) | | | |
| `--cr-patt` pattern logo | (in `.cr-patt`) | icona colorata .055 | colorata .05 | bianca .09 | bianca .3 |

**Contrasti verificati**: dark→accent `#b6a9d9` 7.4:1 · light→CTA `#6b5a99` AA ·
lilla→testo bianco/CTA nere · lilla2→testo nero/accent `#4d3f73`.
`#8877b2` NON è mai colore di testo body: solo display/bordi/glow (4.1:1).

**Colori fissi cross-tema** (semantici, in `design-system.css`): sale `#d9536f` · preordine `#6b5a99` ·
esaurito `#1d2125` · top-deal oro `#d9b45b` (massimo UNO per griglia) ·
well foto **sempre `white-pure #ffffff`**.

## 3 · Tipografia

| Ruolo | Font | Uso |
|---|---|---|
| Eyebrow / badge / date | **Bylon** (`.cr-eyebrow`, `font-bylon`) | 12.5px, tracking .24em, uppercase — una spezia, mai paragrafi |
| “Grafia” del negozio | **Bylon Italic** | note personali, microcopy caldo |
| Titoli H1/H2 | **Metropolis Bold/SemiBold** (`font-metropolis`) | scala .tw-h* esistente; hero ~`!text-4xl→!text-7xl` |
| Manifesti/claim | **Metropolis Light** (300) | claim-progetto, statement |
| Corpo testo | **Adelle Sans** (Typekit) | applicata al body in `themes.css` (fix storico) |
| Numeri | — | sempre `tabular-nums` (prezzi, countdown, stats) |

Scala font: quella esistente in `tailwind.config.js` (xxs 10 → 9xl 80). Non aggiungere taglie.

## 4 · Forma

- **Radii**: sezioni/box grandi 20px (`rounded-[20px]`) · card 16px (`rounded-2xl`) ·
  well/bottoni 10px (`rounded-[10px]`) · chip/badge 8px o full.
- **Ombra**: solo `shadow-th` (`--cr-shadow`, declinata per tema). Su dark l'elevazione si fa
  **schiarendo le superfici** (`surface`→`sur2`), non con l'ombra.
- **Spaziatura sezioni**: `py-14 lg:py-16` (standard) · hero `py-16 lg:py-20` · claim `py-16 lg:py-20`.
- **Container**: `tw-container` (1440) + `tw-section` (px-6). Contenuti testuali max `~56-62ch`.
- **Griglie prodotto**: 2 col mobile → 3/4 desktop, gap 12/16px. Tasche singole: 2→3→6 col,
  proporzione carta **63:88**.

## 5 · Primitive (`src/tailwind/components/design-system.css`)

| Classe | Cosa fa |
|---|---|
| `.cr-sec` | sezione che indossa il tema (bg/color dai token) — sempre con `data-th` |
| `.cr-patt` | pattern logo ripetuto ruotato −11° (icona per tema, opacità per tema) |
| `.cr-eyebrow` | eyebrow Bylon |
| `.cr-btn` + `-solid/-ghost/-glass` | i 3 bottoni; glass solo dove c'è qualcosa da sfocare |
| `.cr-glass` | superficie vetro (blur 14) — MAI testo su glass senza scrim solido |
| `.cr-card` (+`--glass/--deal/--soldout`) | card prodotto unica; hover lift+shadow+accent |
| `.cr-well` | well foto: bianco puro, su ogni tema |
| `.cr-qadd` | quick-add su hover (semplici AJAX; variabili → PDP) |
| `.cr-chip` / `.cr-cchip(--cond)` | chip gioco·lingua / chip condizione (NM/LP/MP/HP) |
| `.cr-badge--sale/pre/out/top` | badge semantici fissi |
| `.cr-stock--ok/low` | stato scorte col pallino. ⚠️ **`--low` non si usa sulle singole**: avere un pezzo solo lì è la norma (una copia per condizione e lingua), e marcare tutto il listato come scorta bassa lo faceva sembrare vuoto invece che curato. Testo unico su tutta la scala — "1 disponibile", "2 disponibili" — mai "ultimo pezzo". L'allarme resta sul sigillato, che si riassortisce. La regola vive in `cr_stock_line()` (`includes/rework.php`), è ripetuta nel carrello (`woocommerce/cart/cart.php`) e rispecchiata dopo le aggiunte AJAX in `js/components/shop.js` via `data-cr-unique` |
| `.cr-price` | prezzo accent; `del/ins` WooCommerce gestiti |
| `.cr-ticker` | marquee: pausa su hover, spento con reduced-motion |
| `.cr-pocket` (+`__well/__pick`) | tasca raccoglitore con riflesso plastica |
| `.cr-dropchip` | chip live col countdown |
| `.cr-label` `.cr-input` `.cr-select` `.cr-textarea` | form (newsletter, bulk, contatti) |

PHP: `cr_theme()`, `cr_product_card()`, `cr_grid_products()` in `includes/rework.php` —
**la card prodotto si stampa SOLO da `cr_product_card()`**, mai markup duplicato.

## 6 · Immagini & iconografia

- Foto prodotto su well bianco. Singole sopra ~50€: **scan reali fronte/retro** (trust).
- Foto “umane” (chi siamo, imballo): reali, mai stock — anche da smartphone.
- Icone: stroke 1.8, round cap/join, 20-24px (stile unico in tutto il sito);
  sprite SVG esistente per le icone ricorrenti (`sprite_icons/`).
- Loghi: bianco su dark/lilla, colorato su light/lilla2 (pattern `.swap` c/w).

## 7 · Regole d'oro (sintesi)

1. Ogni sezione ha `data-th`; nessun colore hardcoded nei template — solo `th-*`/`cr-*`.
2. Lilla = momento. Mai due sezioni lilla consecutive.
3. Glass solo sopra pattern/orbs/foto. Fondi piatti → superfici piene.
4. Un solo elemento animato/brillante per viewport. Brilla solo ciò che è speciale.
5. Badge oro top-deal: massimo uno per griglia.
6. Numeri sempre tabulari; prezzi solo da `get_price_html()`.
7. Effetti: `transform/opacity` only, gate `(hover:hover)` + `prefers-reduced-motion`
   (catalogo completo e tier: `docs/rework-fase-1.md` § 3d).
8. Tailwind scansiona solo `wp-theme/global-components/js`: mai classi concatenate in PHP.
9. Accessibilità: `:focus-visible` visibile, alt sulle immagini, contrasti come da § 2.

## 8 · Riferimenti

- Preview ufficiale (homepage + pagina bulk + design system interattivo):
  https://claude.ai/code/artifact/a2a0df61-9150-43ef-a103-80fdf03c28b7
- Roadmap, checklist, campi ACF, effetti: `docs/rework-fase-1.md`
- Ricerche (fiducia TCG, dark UX, buylist): sintetizzate nelle sezioni decisioni del doc di fase.
