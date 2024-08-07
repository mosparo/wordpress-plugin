const WPExtractorPlugin = require('@wordpress/dependency-extraction-webpack-plugin');
const path= require('path');
const devMode= !process.argv.join(':').
includes('--mode:production');

module.exports = {
    context: path.resolve(__dirname, 'src'),
    entry: {
        'editor': './editor/main.js',
    },
    output: {
        path: path.resolve(__dirname, 'build'),
        devtoolNamespace: 'jfb-captcha-mosparo',
    },
    devtool: devMode ? 'inline-cheap-module-source-map' : false,
    resolve: {
        extensions: [ '.js', '.jsx' ],
        alias: {
            '@blocks': path.resolve(__dirname, 'blocks-metadata'),
        },
    },
    module: {
        rules: [
            {
                test: /\.js(x)?$/,
                use: [
                    'babel-loader',
                    '@wyw-in-js/webpack-loader',
                ],
                exclude: /node_modules/,
            },
            // Do not use together style-loader and mini-css-extract-plugin
            {
                test: /\.css$/,
                use: [
                    'style-loader',
                    'css-loader',
                    'postcss-loader',
                ],
            },
        ],
    },
    plugins: [
        new WPExtractorPlugin(),
    ],
};