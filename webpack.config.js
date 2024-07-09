const webpackConfig = require('@nextcloud/webpack-vue-config');
const webpackRules = require('@nextcloud/webpack-vue-config/rules.js');
const ESLintPlugin = require('eslint-webpack-plugin');
const StyleLintPlugin = require('stylelint-webpack-plugin');
const HtmlWebpackPlugin = require('html-webpack-plugin');
const path = require('path');

const buildMode = process.env.NODE_ENV;
const isDev = buildMode === 'development';
webpackConfig.devtool = isDev ? 'cheap-source-map' : 'source-map';

webpackConfig.entry = {
	main: path.join(__dirname, 'src', 'main.js'),
};

webpackConfig.output = {
	path: path.join(__dirname, 'js'),
};

webpackConfig.module.rules.push({
	test: /\.svg$/i,
	type: 'asset/source',
});

delete webpackRules.RULE_CSS;

webpackConfig.module.rules = [
	...Object.values(webpackRules),
	{
		test: /\.css$/i, // Target CSS files
		use: [
			'style-loader',
			'css-loader',
			{
				loader: "postcss-loader",
				options: {
					postcssOptions: require('./postcss.config.js')
				},
			},
		],
	}
];

webpackConfig.resolve.alias.vue$ = 'vue/dist/vue.esm.js';

module.exports = webpackConfig;
