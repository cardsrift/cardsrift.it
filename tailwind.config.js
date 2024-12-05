/** @type {import('tailwindcss').Config} */

const themeFontSize = {
	'xxs': '12px',
	'xs': '13px',
	'sm': '14px',
	'tiny': '15px',
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
	avenir: ["Avenir", "sans-serif"],
	gotham: ["Gotham", "sans-serif"],
}

const themeTypography = {
  h1: {
    DEFAULT: themeFontSize["3xl"],
    tb: themeFontSize["6xl"],
    xl: themeFontSize["8xl"],
	fontFamily: themeFontFamily.avenir,
  },
  h2: {
    DEFAULT: themeFontSize["2xl"],
    tb: themeFontSize["4xl"],
    xl: themeFontSize["6xl"],
	fontFamily: themeFontFamily.gotham,
  },
  h3: {
    DEFAULT: themeFontSize.xl,
    tb: themeFontSize["2xl"],
    xl: themeFontSize["4xl"],
	fontFamily: themeFontFamily.gotham,
  },
  h4: {
    DEFAULT: themeFontSize.lg,
    tb: themeFontSize.xl,
    xl: themeFontSize["3xl"],
	fontFamily: themeFontFamily.gotham,
  },
  h5: {
    DEFAULT: themeFontSize.md,
    tb: themeFontSize.lg,
    xl: themeFontSize.xl,
	fontFamily: themeFontFamily.gotham,
  },
  h6: {
    DEFAULT: themeFontSize.base,
    tb: themeFontSize.md,
    xl: themeFontSize.md,
	fontFamily: themeFontFamily.gotham,
  },
};


module.exports = {
  corePlugins: {
    container: false,
  },
  content: [
    "./src/wp-theme/**/*.php",
    "./src/global-components/**/*.php",
    "./src/js/**/*.js",
    "./src/global-components/**/*.js",
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
      transparent: "transparent",
      blue: "#00ADEF",
      grey: "#808284",
      green: {
        DEFAULT: "#87C440",
        light: "#E7F3D9",
      },
      white: "#ffffff",
      black: "#000000",
      orange: {
        DEFAULT: "#F68E1F",
        light: "#FDE8D2",
      },
      yellow: {
        DEFAULT: "#FFE800",
        light: "#FFFACC",
      },
      "light-orange": "#FDE8D2",
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
    },
  },
  plugins: [
    require("daisyui"),
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
          "@apply px-8": {},
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
  daisyui: {
    themes: false, // true: all themes | false: only light + dark | array: specific themes like this ["light", "dark", "cupcake"]
    darkTheme: "light", // name of one of the included themes for dark mode
    base: true, // applies background color and foreground color for root element by default
    styled: true, // include daisyUI colors and design decisions for all components
    utils: true, // adds responsive and modifier utility classes
    rtl: false, // rotate style direction from left-to-right to right-to-left. You also need to add dir="rtl" to your html tag and install `tailwindcss-flip` plugin for Tailwind CSS.
    prefix: "", // prefix for daisyUI classnames (components, modifiers and responsive class names. Not colors)
    logs: false, // Shows info about daisyUI version and used config in the console when building your CSS
  },
};