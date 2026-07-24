// autoprefixer - https://github.com/postcss/autoprefixer
// css-mqpacker - https://github.com/hail2u/node-css-mqpacker
// cssnano      - https://github.com/hail2u/node-css-mqpacker

// npm install postcss-loader autoprefixer css-mqpacker cssnano --save-dev

module.exports = {
	plugins: [
		require('autoprefixer'),
		// ⚠️ `sort: true` NON è opzionale. css-mqpacker raggruppa le media query
		// nell'ordine in cui le incontra: senza ordinamento il breakpoint `sm` (640px)
		// può finire DOPO `lg` (1024px) e sovrascriverlo, perché a parità di specificità
		// vince l'ultima regola. Sintomo tipico: `lg:grid-cols-4` ignorato e la griglia
		// che resta a 2 colonne su desktop. Con `sort` l'ordine torna mobile-first.
		require('css-mqpacker')({ sort: true }),
		require('cssnano')({
			preset: [
				'default',
				{
					discardComments: {
						removeAll: true,
					},
				},
			],
		}),
		'postcss-preset-env',
		require('tailwindcss'),
	],
};
