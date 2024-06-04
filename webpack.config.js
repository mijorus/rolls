const webpackConfig = require('@nextcloud/webpack-vue-config')
const ESLintPlugin = require('eslint-webpack-plugin')
const StyleLintPlugin = require('stylelint-webpack-plugin')
const HtmlWebpackPlugin = require('html-webpack-plugin')
const path = require('path')

const buildMode = process.env.NODE_ENV
const isDev = buildMode === 'development'
webpackConfig.devtool = isDev ? 'cheap-source-map' : 'source-map'

webpackConfig.entry = {
	main: { import: path.join(__dirname, 'src', 'main.js'), filename: 'main.js' },
}

// webpackConfig.plugins.push(
// 	new ESLintPlugin({
// 		extensions: ['js', 'vue'],
// 		files: 'src',
// 	}),
// )

webpackConfig.plugins.push(
	new StyleLintPlugin({
		files: 'src/**/*.{css,scss,vue}',
	}),
)

webpackConfig.plugins.push(
	new HtmlWebpackPlugin({
        title: 'Hot Module Replacement',
      }),
)

webpackConfig.devServer.hot = true

webpackConfig.module.rules.push({
	test: /\.svg$/i,
	type: 'asset/source',
})

webpackConfig.resolve.alias.vue$ = 'vue/dist/vue.esm.js'

module.exports = webpackConfig
