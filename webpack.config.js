const fs = require('fs');
const path = require('path');
const glob = require('glob');
const webpack = require('webpack');
const CopyWebpackPlugin = require('copy-webpack-plugin');
const { CleanWebpackPlugin } = require('clean-webpack-plugin');
const SpriteLoaderPlugin = require('svg-sprite-loader/plugin');
const ESLintPlugin = require('eslint-webpack-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

const environment = require('./settings/environment');
var currentOutput = environment.paths.wpOutput;

module.exports = {
	entry: {
		app: path.resolve(environment.paths.source, 'index.js'),
	},
	output: {
		filename: 'assets/js/[name].js',
		path: currentOutput,
		// assetModuleFilename: 'images/[name][ext]',
	},
	module: {
		rules: [
			{
				test: /\.js$/,
				exclude: /node_modules/,
				use: ['babel-loader?cacheDirectory'],
			},
			{
				test: /\.(png|gif|webp|jpe?g|svg)$/i,
				type: 'asset',
				exclude: path.resolve(
					environment.paths.source,
					'images',
					'icons',
					'sprite_icons',
				),
				parser: {
					dataUrlCondition: {
						maxSize: environment.limits.images,
					},
				},
				generator: {
					filename: 'images/[name][ext]',
				},
			},
			{
				test: /\.svg$/,
				include: path.resolve(
					environment.paths.source,
					'images',
					'icons',
					'sprite_icons',
				),
				use: [
					{
						loader: 'svg-sprite-loader',
						options: {
							extract: true,
							publicPath: 'assets/images/sprite/',
						},
					},
					{
						loader: 'svgo-loader',
					},
				],
			},
			{
				test: /\.(eot|ttf|woff|woff2)$/,
				type: 'asset',
				parser: {
					dataUrlCondition: {
						maxSize: environment.limits.images,
					},
				},
				generator: {
					filename: 'assets/fonts/[name][ext]',
				},
			},
		],
	},
	plugins: [
		new webpack.ProvidePlugin({
			$: 'jquery',
			jQuery: 'jquery',
			'window.$': 'jquery',
			'window.jQuery': 'jquery',
		}),
		new CleanWebpackPlugin({
			verbose: false,
			cleanStaleWebpackAssets: false,
			cleanOnceBeforeBuildPatterns: ['**/*', '!stats.json'],
		}),
		new SpriteLoaderPlugin(),
		new MiniCssExtractPlugin({
			filename: 'assets/styles/[name].css',
		}),
		new CopyWebpackPlugin({
			patterns: [
				{
					from: path.resolve(environment.paths.source, 'images'),
					to: path.resolve(`${currentOutput}/assets/` , 'images'),
					toType: 'dir',
					noErrorOnMissing: true,
					globOptions: {
						dot: true,
						ignore: [
							'**/icons/other_icons/**',
							'**/icons/sprite_icons/**',
						],
					},
				},
				{
					from: path.resolve(environment.paths.source, 'static'),
					to: path.resolve(`${currentOutput}/assets/`, 'images'),
					toType: 'dir',
					noErrorOnMissing: true,
					globOptions: {
						dot: true,
						// gitignore: true,
						ignore: ['**/.gitkeep'],
					},
				},
				{
					from: path.resolve(environment.paths.source, 'static_js'),
					to: path.resolve(`${currentOutput}/assets/`, 'js'),
					toType: 'dir',
					noErrorOnMissing: true,
					globOptions: {
						dot: true,
						// gitignore: true,
						ignore: ['**/.gitkeep'],
					},
				},
			],
		}),
		new ESLintPlugin(),
	],
	// jQuery non viene impacchettata: si usa quella di WordPress
	// (dipendenza 'jquery' dichiarata in wp_register_script)
	externals: {
		jquery: 'jQuery',
	},
	target: 'web',
};
