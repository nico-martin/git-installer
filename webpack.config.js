const { CleanWebpackPlugin } = require('clean-webpack-plugin');
const CopyWebpackPlugin = require('copy-webpack-plugin');
const fs = require('fs');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const path = require('path');
const LiveReloadPlugin = require('webpack-livereload-plugin');

module.exports = (env, argv) => {
  const dirDist = path.resolve(__dirname, 'assets/dist');
  const dirSrc = path.resolve(__dirname, 'assets/src');
  const { mode = 'development' } = argv;
  const dev = mode === 'development';
  console.log('dev', dev);

  return {
    mode: dev ? 'development' : 'production',
    entry: {
      admin: `${dirSrc}/admin/index.ts`,
    },
    output: {
      path: dirDist,
      filename: '[name].js',
      publicPath: '/',
    },
    devtool: dev ? 'source-map' : false,
    target: 'web',
    plugins: [
      new CleanWebpackPlugin({
        cleanStaleWebpackAssets: false,
      }),
      new MiniCssExtractPlugin({
        filename: '[name].css',
        chunkFilename: '[name].[id].css',
      }),
      new CopyWebpackPlugin({
        patterns: [
          {
            from: 'assets/src/admin/static',
            to: 'static/admin',
          },
        ],
      }),
      new LiveReloadPlugin(),
    ],
    module: {
      rules: [
        {
          test: /\.svg$/,
          exclude: /node_modules/,
          loader: 'raw-loader',
        },
        {
          test: /\.(ts|tsx)$/,
          loader: 'ts-loader',
          exclude: /node_modules/,
        },
        {
          test: /\.css$/,
          exclude: [/node_modules/],
          use: [
            {
              loader: MiniCssExtractPlugin.loader,
            },
            {
              loader: 'css-loader',
              options: {
                importLoaders: 1,
                modules: {
                  localIdentName: '[name]__[local]--[hash:base64:5]',
                },
              },
            },
            {
              loader: 'postcss-loader',
            },
          ],
        },
      ],
    },
    resolve: {
      alias: {},
      extensions: ['.js', '.jsx', '.ts', '.tsx'],
    },
    externals: {
      /*
      react: 'preactCompat',
      'react-dom': 'preactCompat',*/
      ...(dev
        ? {}
        : {
            react: 'React',
            'react-dom': 'ReactDOM',
          }),
      '@wordpress/components': 'wp.components',
      '@wordpress/i18n': 'wp.i18n',
    },
  };
};
