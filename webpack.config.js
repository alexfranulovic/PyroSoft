const fs = require('fs');
const path = require('path');
const webpack = require('webpack');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const FriendlyErrorsWebpackPlugin = require('friendly-errors-webpack-plugin');
const CssMinimizerPlugin = require('css-minimizer-webpack-plugin');

function getEntries(what, returnType = 'object')
{
  const baseDir = path.resolve(__dirname, './this-system/ep-areas');
  const entries = {};
  const paths = [];

  fs.readdirSync(baseDir).forEach((area) =>
  {
    let filePath;

    if (what === 'scripts') {
      filePath = `./assets/scripts/${area}.js`;
    } else if (what === 'styles') {
      filePath = `./assets/styles/${area}.scss`;
    } else {
      return;
    }

    const fullPath = path.resolve(baseDir, area, filePath);
    if (fs.existsSync(fullPath)) {
      if (returnType === 'object') {
        entries[area] = fullPath;
      } else if (returnType === 'array') {
        paths.push(fullPath);
      }
    }
  });

  return returnType === 'object' ? entries : paths;
}

module.exports =
{
    //mode: 'production',
    mode: 'development',
    devtool: 'source-map',

    context: path.resolve(__dirname, './'),
    stats: 'errors-only',
    // stats: 'errors-warnings',
    // stats: 'errors-warnings',
    // infrastructureLogging: { level: 'error' },

    cache: {
        type: 'filesystem',
        buildDependencies: { config: [__filename] },
    },

    entry: {
        ...getEntries('scripts'),
        main: './this-system/assets/scripts/main.js',
        libs: './this-system/assets/scripts/libs.js',
        filesPreviewer: './this-system/assets/scripts/inc/files-previewer.js',
        passwordInput: './this-system/assets/scripts/inc/password-input.js',
        rangeInput: './this-system/assets/scripts/inc/range-input.js',
        // charts: './this-system/assets/scripts/charts.js',
    },

    devServer: {
        performance: true,
    },

    output: {
        clean: true,
        path: path.resolve(__dirname, './dist'),
        filename: 'scripts/[name].js',
    },

    optimization:
    {
        minimize: true,
        minimizer: [
            '...',
            new CssMinimizerPlugin({
                minimizerOptions: {
                    preset: ['default', { discardComments: { removeAll: true } }],
                },
            }),
        ],

        // 🔧 IMPORTANTE: não crie vendors para não conflitar com a entry "libs"
        splitChunks: {
            chunks: 'all',
            cacheGroups: {
                default: false,
                defaultVendors: false,
            },
        },
        // runtimeChunk: 'single',
        runtimeChunk: false,
    },

    module:
    {
        rules:
        [
            // For JS
            {
                test: /\.js$/,
                exclude: /node_modules/,
                enforce: 'pre',
                loader: 'webpack-glob-loader',
            },

            //For SCSS & SASS
            {
              test: /\.s?(a|c)ss$/,
              exclude: /node_modules/,
              use: [
                MiniCssExtractPlugin.loader,
                { loader: 'css-loader', options: { url: true, esModule: false, sourceMap: true } },
                { loader: 'postcss-loader', options: { sourceMap: true } },
                { loader: 'sass-loader', options: { sourceMap: true } },
                'webpack-glob-loader',
              ],
            },


            // For CSS
            {
                test: /\.css$/,
                include: /node_modules/,
                use: [MiniCssExtractPlugin.loader, 'css-loader'],
            },

            // // For fonts
            // {
            //     test: /\.(woff|woff2|eot|ttf|otf)$/,
            //     generator: { filename: 'fonts/[name][ext]' },
            // },
            {
              test: /\.(woff2?|eot|ttf|otf)$/i,
              type: 'asset/resource',
              generator: { filename: 'fonts/[name][ext]' },
            },

            // For images
            {
              test: /\.(png|jpe?g|gif|webp)$/i,
              type: 'asset/resource',
              generator: { filename: 'images/[name][ext]' },
            },
        ],
    },

    resolve: {
        alias: {
          '~bootstrap': path.resolve(__dirname, 'node_modules/bootstrap'),
        },
        extensions: ['.js', '.scss'],
    },

    plugins: [
        new MiniCssExtractPlugin({ filename: 'styles/[name].css' }),
        new FriendlyErrorsWebpackPlugin(),
        new webpack.ProvidePlugin({
            bootstrap: 'bootstrap',
        }),
    ],
};
