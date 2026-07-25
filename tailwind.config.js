/** @type {import('tailwindcss').Config} */

const themeFontSize = {
  'xxs': '10px',
  'xs': '12px',
  'sm': '14px',
  'base': '16px',
  'md': '18px',
  'lg': '20px',
  'xl': '24px',
  '2xl': '30px',
  '3xl': '32px',
  '4xl': '36px',
  '5xl': '48px',
  '6xl': '56px',
  '7xl': '60px',
  '8xl': '64px',
  '9xl': '80px',
}

const themeFontFamily = {
  bylon: ["Bylon", "sans-serif"],
  adelle: ["adelle-sans", "sans-serif"],
  metropolis: ["metropolis", "sans-serif"],
}

const themeTypography = {
  h1: {
    DEFAULT: themeFontSize["3xl"],
    tb: themeFontSize["6xl"],
    xl: themeFontSize["8xl"],
    fontFamily: themeFontFamily.metropolis,
  },
  h2: {
    DEFAULT: themeFontSize["2xl"],
    tb: themeFontSize["4xl"],
    xl: themeFontSize["6xl"],
    fontFamily: themeFontFamily.metropolis,
  },
  h3: {
    DEFAULT: themeFontSize.xl,
    tb: themeFontSize["2xl"],
    xl: themeFontSize["4xl"],
    fontFamily: themeFontFamily.metropolis,
  },
  h4: {
    DEFAULT: themeFontSize.lg,
    tb: themeFontSize.xl,
    xl: themeFontSize["3xl"],
    fontFamily: themeFontFamily.metropolis,
  },
  h5: {
    DEFAULT: themeFontSize.md,
    tb: themeFontSize.lg,
    xl: themeFontSize.xl,
    fontFamily: themeFontFamily.metropolis,
  },
  h6: {
    DEFAULT: themeFontSize.base,
    tb: themeFontSize.md,
    xl: themeFontSize.md,
    fontFamily: themeFontFamily.metropolis,
  },
  pmenu: {
    DEFAULT: themeFontSize.xl,
    tb: themeFontSize.xl,
    xl: themeFontSize.xl,
    fontFamily: themeFontFamily.metropolis,
    fontWeight: 500,
  }
};


module.exports = {
  // hoverOnlyWhenSupported: ogni `hover:*` esce dentro @media (hover: hover).
  // Su touch il browser applica :hover al tocco e ce lo lascia fino al tocco
  // successivo altrove: card che restano illuminate, bottoni che sembrano premuti.
  future: {
    hoverOnlyWhenSupported: true,
  },
  corePlugins: {
    container: false,
  },
  content: [
    "./src/wp-theme/**/*.php",
    "./src/global-components/**/*.php",
    "./src/js/**/*.js",
    "./src/global-components/**/*.js",
  ],
  // ⚠️ Tailwind pota anche le regole in @layer components: se una classe non compare
  // nei file scansionati, la sua regola sparisce dal CSS. Le classi qui sotto le
  // stampa WooCommerce (o jQuery blockUI) a runtime, quindi non sono in `src/`:
  // senza safelist lo skin di quel markup — definito in tailwind/components/shop.css —
  // verrebbe buttato via silenziosamente. Aggiungere qui ogni nuovo aggancio.
  safelist: [
    // form generati da woocommerce_form_field()
    "form-row-first", "form-row-last", "form-row-blank", "optional",
    "woocommerce-invalid", "woocommerce-validated",
    "password-input", "show-password-input",
    "woocommerce-password-strength", "woocommerce-password-hint",
    "woocommerce-privacy-policy-text",
    // meta di riga (carrello e ordini)
    "wc-item-meta", "wc-item-meta-label", "variation",
    // istruzioni del gateway bonifico nella pagina di conferma
    "woocommerce-bacs-bank-details", "wc-bacs-bank-details", "wc-bacs-bank-details-heading",
    // velo di caricamento degli aggiornamenti AJAX (jQuery blockUI)
    "blockUI", "blockOverlay",
    // importi e wrapper degli avvisi
    "amount", "includes_tax", "woocommerce-notices-wrapper",
  ],
  theme: {
    screens: {
      //breakpoints
      xs: "375px",
      sm: "640px",
      tb: "768px",
      md: "960px",
      lg: "1024px",
      xl: "1280px",
      "2xl": "1600px",
      "3xl": "1800px",
    },
    containers: {
      DEFAULT: "1440px",
      sm: "940px",
      md: "1020px",
      full: "100%",
    },
    colors: {
      // `colors` SOSTITUISCE la palette di Tailwind (non la estende): le parole chiave
      // di default vanno riportate a mano, altrimenti spariscono utility come
      // `stroke-current` — usata da tutte le icone SVG del sito (header, footer,
      // carrello, checkout…). Finora le teneva in piedi daisyUI: togliendola senza
      // questa riga le icone perderebbero il colore, in silenzio.
      transparent: "transparent",
      current: "currentColor",
      inherit: "inherit",
      black: '#1d2125',
      white: {
        DEFAULT: "#F3F4F5",
        70: "rgba(243, 244, 245, 0.7)",
        pure: "#ffffff", // well foto prodotto: sempre bianco puro, su ogni tema
      },
      purple: {
        DEFAULT: '#8877b2',
        light: '#b6a9d9',
        deep: '#6b5a99', // accent/CTA su fondi chiari (contrasto AA)
      },
      // Token di tema (rework): i valori vivono in src/tailwind/components/themes.css
      // e cambiano con data-th="dark|light|lilla|lilla2" stampato dal campo ACF "tema".
      // Uso: bg-th-pg, text-th-ink, text-th-muted, bg-th-surface, border-th-line, text-th-acc...
      th: {
        pg: 'var(--cr-pg)',
        ink: 'var(--cr-ink)',
        muted: 'var(--cr-muted)',
        soft: 'var(--cr-soft)',
        surface: 'var(--cr-surface)',
        sur2: 'var(--cr-sur2)',
        line: 'var(--cr-line)',
        lines: 'var(--cr-line-s)',
        acc: 'var(--cr-acc)',
        acc2: 'var(--cr-acc2)',
        accsoft: 'var(--cr-accsoft)',
        ok: 'var(--cr-ok)',
        warn: 'var(--cr-warn)',
      },
    },

    fontFamily: themeFontFamily,

    fontSize: themeFontSize,
    typography: {
      // qui customizziamo il plugin tailwindcss/typography. Fonte: https://github.com/tailwindlabs/tailwindcss-typography
    },
    extend: {
      transitionDuration: {
        DEFAULT: "400ms",
      },
      boxShadow: {
        th: "var(--cr-shadow)", // ombra coerente col tema di sezione
      },
    },
  },
  // daisyUI rimossa il 24/07/2026: veniva dal boilerplate iniziale e non era usata da
  // nessun componente (il design system è tutto in primitive .cr-*). In più collideva
  // con WooCommerce, che marca le LABEL delle checkbox con la classe `checkbox`,
  // omonima del componente daisyUI (24×24px): schiacciava il testo delle spunte.
  plugins: [
    require("@tailwindcss/typography"),
    ({ addComponents, theme }) => {
      addComponents(
        Object.entries(theme("containers")).map(([key, value]) => ({
          [key === "DEFAULT" ? ".tw-container" : `.tw-container-${key}`]: {
            "@apply mx-auto": {},
            maxWidth: value,
          },
        }))
      );
      addComponents({
        ".tw-section": {
          "@apply px-6": {},
        },
      });
    },
    ({ addVariant }) => {
      addVariant("disabled", "&.disabled");
    },
    ({ addUtilities, theme }) => {
      addUtilities(
        Object.entries(themeTypography).map(([key, value]) => ({
          [`.tw-${key}, ${key}`]: {
            fontFamily: value.fontFamily.join(", "),
            fontSize: value.DEFAULT,
            "@screen tb": {
              fontSize: value.tb,
            },
            "@screen xl": {
              fontSize: value.xl,
            },
          },
        }))
      );
    },
  ],
};