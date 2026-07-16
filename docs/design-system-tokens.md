# CardsRift · Foglio variabili — riferimento rapido

> Tabella completa dei token del design system. **Fonte di verità nel codice:**
> `src/tailwind/components/themes.css` (valori) + `tailwind.config.js` (classi `th-*`).
> Se questo foglio e il codice divergono, vince il codice — e questo foglio va aggiornato.

## 1 · Token di tema (cambiano con `data-th`)

| Variabile | Classe Tailwind | dark | light | lilla | lilla2 | Uso |
|---|---|---|---|---|---|---|
| `--cr-pg` | `bg-th-pg` | `#1d2125` | `#F3F4F5` | `#8877b2` | `#b6a9d9` | sfondo sezione |
| `--cr-ink` | `text-th-ink` | `#F3F4F5` | `#1d2125` | `#ffffff` | `#1d2125` | testo primario |
| `--cr-muted` | `text-th-muted` | `#a6a3b2` | `#5d5a68` | `rgba(255,255,255,.82)` | `#463d5c` | testo secondario |
| `--cr-soft` | `text-th-soft` | `rgba(243,244,245,.45)` | `rgba(29,33,37,.45)` | `rgba(255,255,255,.6)` | `rgba(29,33,37,.5)` | testo terziario, placeholder, `del` |
| `--cr-surface` | `bg-th-surface` | `#262b32` | `#ffffff` | `rgba(255,255,255,.13)` | `rgba(255,255,255,.5)` | card, input |
| `--cr-sur2` | `bg-th-sur2` | `#2b3138` | `#eceaf2` | `rgba(255,255,255,.2)` | `rgba(255,255,255,.68)` | superficie +1 (hover, elevazione) |
| `--cr-line` | `border-th-line` | `rgba(243,244,245,.10)` | `rgba(29,33,37,.10)` | `rgba(255,255,255,.2)` | `rgba(29,33,37,.14)` | bordi leggeri, divider |
| `--cr-line-s` | `border-th-lines` | `rgba(243,244,245,.20)` | `rgba(29,33,37,.20)` | `rgba(255,255,255,.34)` | `rgba(29,33,37,.26)` | bordi marcati, input |
| `--cr-acc` | `text-th-acc` | `#b6a9d9` | `#6b5a99` | `#ffffff` | `#4d3f73` | accent testo: prezzi, link, eyebrow |
| `--cr-acc2` | `text-th-acc2` | `#8877b2` | `#8877b2` | `#d8cfee` | `#6b5a99` | accent display: bordi hover, glow |
| `--cr-accsoft` | `bg-th-accsoft` | `rgba(136,119,178,.18)` | `rgba(136,119,178,.13)` | `rgba(255,255,255,.16)` | `rgba(255,255,255,.38)` | tinte: chip, hover row, ghost hover |
| `--cr-glass` | (`.cr-glass`) | `rgba(38,43,50,.55)` | `rgba(255,255,255,.6)` | `rgba(255,255,255,.15)` | `rgba(255,255,255,.44)` | superfici vetro (blur 14) |
| `--cr-glass-line` | (`.cr-glass`) | `rgba(243,244,245,.15)` | `rgba(29,33,37,.13)` | `rgba(255,255,255,.32)` | `rgba(255,255,255,.62)` | bordo vetro |
| `--cr-btn` | (`.cr-btn-solid`) | `#b6a9d9` | `#6b5a99` | `#1d2125` | `#1d2125` | bg CTA solida |
| `--cr-btn-fg` | (`.cr-btn-solid`) | `#1d2125` | `#ffffff` | `#F3F4F5` | `#F3F4F5` | testo CTA solida |
| `--cr-btn-h` | (`.cr-btn-solid:hover`) | `#cabfe6` | `#5c4d87` | `#2b3138` | `#2b3138` | hover CTA |
| `--cr-ghost` | (`.cr-btn-ghost`) | `#b6a9d9` | `#6b5a99` | `#ffffff` | `#4d3f73` | bordo/testo CTA ghost |
| `--cr-shadow` | `shadow-th` | `0 16px 40px rgba(0,0,0,.45)` | `0 14px 34px rgba(29,33,37,.12)` | `0 16px 40px rgba(61,48,94,.4)` | `0 14px 34px rgba(61,48,94,.26)` | ombra card/box |
| `--cr-ok` | `text-th-ok` | `#5cc491` | `#2f8a5d` | `#c4f0d8` | `#1f6b45` | scorte disponibili |
| `--cr-warn` | `text-th-warn` | `#e0a04f` | `#b3701c` | `#ffdcab` | `#8a5514` | “Ultimi N” |
| `--cr-patt` | (`.cr-patt`) | icona colorata | icona colorata | icona bianca | icona bianca | pattern logo |
| `--cr-patt-op` | (`.cr-patt`) | `.055` | `.05` | `.09` | `.3` | opacità pattern |

## 2 · Palette fissa (uguale su tutti i temi)

| Nome | Valore | Classe | Uso |
|---|---|---|---|
| Nero brand | `#1d2125` | `black` | base dark, badge esaurito, qadd |
| Bianco brand | `#F3F4F5` | `white` | base light, testi su scuro |
| Bianco 70 | `rgba(243,244,245,.7)` | `white-70` | legacy |
| **Bianco puro** | `#ffffff` | `white-pure` | **well foto prodotto, sempre** |
| Viola | `#8877b2` | `purple` | brand mark, display — mai testo body |
| Viola chiaro | `#b6a9d9` | `purple-light` | accent su dark (7.4:1) |
| Viola profondo | `#6b5a99` | `purple-deep` | CTA/accent su chiaro (AA) |
| Sale | `#d9536f` | (`.cr-badge--sale`) | badge sconto |
| Preordine | `#6b5a99` | (`.cr-badge--pre`) | badge preordine |
| Top deal | `#d9b45b` | (`.cr-badge--top`, `.cr-card--deal`) | oro — max 1 per griglia |
| Live | `#ff5470` | (`.cr-dropchip::before`) | pallino drop live |

## 3 · Tipografia

| Ruolo | Famiglia | Classe | Note |
|---|---|---|---|
| Eyebrow/badge/date | Bylon 400 | `.cr-eyebrow`, `font-bylon` | 12.5px · tracking .24em · uppercase |
| Grafia del negozio | Bylon Italic | `font-bylon italic` | note calde, microcopy |
| Titoli | Metropolis 600/700 | `font-metropolis font-semibold/bold` | h1 hero `!text-4xl → lg:!text-7xl` |
| Manifesti | Metropolis 300 | `font-metropolis font-light` | claim, statement |
| Corpo | Adelle Sans | (body, automatico) | fix in `themes.css` |
| Numeri | — | `tabular-nums` | prezzi, countdown, stats |

Scala (tailwind.config, px): xxs 10 · xs 12 · sm 14 · base 16 · md 18 · lg 20 · xl 24 ·
2xl 30 · 3xl 32 · 4xl 36 · 5xl 48 · 6xl 56 · 7xl 60 · 8xl 64 · 9xl 80.

## 4 · Forma, spaziatura, breakpoints

| Cosa | Valore |
|---|---|
| Radius box grandi / card / bottoni-well / chip | 20px · 16px (`rounded-2xl`) · 10px · 8px/full |
| Padding sezione | `py-14 lg:py-16` (hero e claim: `py-16 lg:py-20`) |
| Container | `.tw-container` 1440 + `.tw-section` px-6 (sm 940 · md 1020) |
| Griglie prodotto | 2 col → `lg:` 3/4 · gap 12/16px |
| Tasca singola | aspect `63/88` (proporzione carta reale) |
| Breakpoints | xs 375 · sm 640 · tb 768 · md 960 · lg 1024 · xl 1280 · 2xl 1600 · 3xl 1800 |
| Transizioni | default 400ms (config) · micro-interazioni 200–500ms · cambio tema 350ms |

## 5 · Dove vivono

| File | Contiene |
|---|---|
| `src/tailwind/components/themes.css` | valori dei token (4 blocchi `[data-th]`) + body font + icone pattern inline |
| `src/tailwind/components/design-system.css` | primitive `.cr-*` e keyframes |
| `tailwind.config.js` | classi `th-*`, `white-pure`, `purple-deep`, `shadow-th`, scala/breakpoints |
| `src/wp-theme/includes/rework.php` | `cr_theme()` · `cr_product_card()` · `cr_grid_products()` |
| `docs/design-system.md` | regole d'uso e principi |
| Template “Design System” | pagina vivente dei primitivi (QA visivo) |
