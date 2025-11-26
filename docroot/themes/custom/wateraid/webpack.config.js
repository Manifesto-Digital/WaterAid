const path = require('path');
const isDev = (process.env.NODE_ENV !== 'production');
const { CleanWebpackPlugin } = require('clean-webpack-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const SVGSpritemapPlugin = require('svg-spritemap-webpack-plugin');
const autoprefixer = require('autoprefixer');
const RemoveEmptyScriptsPlugin = require('webpack-remove-empty-scripts');

module.exports = {
  // todo: remove entry point from webpack and automate it.
  entry: {
    'styles': ['./sass/styles.scss'],
    'ckeditor-styles': ['./sass/ckeditor-styles.scss'],
    'components/accordion/accordion': ['./components/accordion/accordion.scss'],
    'components/accordion_item/accordion_item': ['./components/accordion_item/accordion_item.scss'],
    'components/button/button': ['./components/button/button.scss'],
    'components/call_to_action/call_to_action': ['./components/call_to_action/call_to_action.scss'],
    'components/cancel_anytime/cancel_anytime': ['./components/cancel_anytime/cancel_anytime.scss'],
    'components/card/card': ['./components/card/card.scss'],
    'components/card_collection/card_collection': ['./components/card_collection/card_collection.scss'],
    'components/content_tag/content_tag': ['./components/content_tag/content_tag.scss'],
    'components/donate_block/donate_block': ['./components/donate_block/donate_block.scss'],
    'components/donate_widget/donate_widget': ['./components/donate_widget/donate_widget.scss'],
    'components/footer/footer': ['./components/footer/footer.scss'],
    'components/hero/hero': ['./components/hero/hero.scss'],
    'components/image/image': ['./components/image/image.scss'],
    'components/image_gallery/image_gallery': ['./components/image_gallery/image_gallery.scss'],
    'components/language_switcher/language_switcher': ['./components/language_switcher/language_switcher.scss'],
    'components/link/link': ['./components/link/link.scss'],
    'components/listing/listing': ['./components/listing/listing.scss'],
    'components/navigation/primary_navigation/primary_navigation': ['./components/navigation/primary_navigation/primary_navigation.scss'],
    'components/navigation/utility_navigation/utility_navigation': ['./components/navigation/utility_navigation/utility_navigation.scss'],
    'components/modal/modal/modal': ['./components/modal/modal/modal.scss'],
    'components/modal/inactivity_modal/inactivity_modal': ['./components/modal/inactivity_modal/inactivity_modal.scss'],
    'components/number_input/number_input': ['./components/number_input/number_input.scss'],
    'components/pagination/pagination': ['./components/pagination/pagination.scss'],
    'components/properties/properties': ['./components/properties/properties.scss'],
    'components/quote/quote': ['./components/quote/quote.scss'],
    'components/rich_text/rich_text': ['./components/rich_text/rich_text.scss'],
    'components/site_header/site_header': ['./components/site_header/site_header.scss'],
    'components/site_header_donate/site_header_donate': ['./components/site_header_donate/site_header_donate.scss'],
    'components/spend/spend': ['./components/spend/spend.scss'],
    'components/statistics/statistics': ['./components/statistics/statistics.scss'],
    'components/text_media/text_media': ['./components/text_media/text_media.scss'],
    'components/video/video': ['./components/video/video.scss'],
  },
  output: {
    filename: 'js/[name].js',
    chunkFilename: 'js/async/[name].chunk.js',
    path: path.resolve(__dirname, 'dist'),
    pathinfo: true,
    publicPath: '../../',
  },
  module: {
    rules: [
      {
        test: /\.(png|jpe?g|gif|svg)$/,
        exclude: /sprite\.svg$/,
        type: 'javascript/auto',
        use: [{
            loader: 'file-loader',
            options: {
              name: '[path][name].[ext]', //?[contenthash]
              publicPath: (url, resourcePath, context) => {
                const relativePath = path.relative(context, resourcePath);

                // Settings
                if (resourcePath.includes('media/settings')) {
                  return `../../${relativePath}`;
                }

                return `../${relativePath}`;
              },
            },
          },
          {
            loader: 'img-loader',
            options: {
              enabled: !isDev,
            },
          },
        ],
      },
      {
        test: /\.js$/,
        exclude: /node_modules/,
        use: {
          loader: 'babel-loader',
        },
      },
      {
        test: /\.(css|scss)$/,
        use: [
          {
            loader: MiniCssExtractPlugin.loader,
            options: {
              name: '[name].[ext]?[hash]',
            }
          },
          {
            loader: 'css-loader',
            options: {
              sourceMap: isDev,
              importLoaders: 2,
              url: false,
            },
          },
          {
            loader: 'postcss-loader',
            options: {
              sourceMap: isDev,
              postcssOptions: {
                plugins: [
                  autoprefixer(),
                  ['postcss-perfectionist', {
                    format: 'expanded',
                    indentSize: 2,
                    trimLeadingZero: true,
                    zeroLengthNoUnit: false,
                    maxAtRuleLength: false,
                    maxSelectorLength: false,
                    maxValueLength: false,
                  }]
                ],
              },
            },
          },
          {
            loader: 'sass-loader',
            options: {
              sourceMap: isDev,
              // Global SCSS imports:
              additionalData: `
                @use "sass:color";
                @use "sass:math";
                @import "./sass/0-settings/_colors.scss";
              `,
            },
          },
        ],
      },
      {
        test: /\.(woff|woff2|eot|ttf|otf)$/i,
        type: 'asset/resource',
      },
    ],
  },
  resolve: {
    alias: {
      media: path.join(__dirname, 'media'),
      settings: path.join(__dirname, 'media/settings'),
      font: path.join(__dirname, 'media/font'),
    },
    modules: [
      path.join(__dirname, 'node_modules'),
    ],
    extensions: ['.js', '.json'],
  },
  plugins: [
    new RemoveEmptyScriptsPlugin(),
    new CleanWebpackPlugin({
      cleanStaleWebpackAssets: false
    }),
    new MiniCssExtractPlugin({
      filename: "css/[name].css",
    }),
    new SVGSpritemapPlugin(path.resolve(__dirname, 'images/icons/**/*.svg'), {
      output: {
        filename: 'images/sprite.svg',
        svg: {
          sizes: false
        },
        svgo: {
          plugins: [
            {
              name: 'removeAttrs',
              params: {
                attrs: '(use|symbol|svg):fill'
              }
            }
          ],
        },
      },
      sprite: {
        prefix: false,
        gutter: 0,
        generate: {
          title: false,
          symbol: true,
          use: true,
          view: '-view'
        }
      },
      styles: {
        filename: path.resolve(__dirname, 'sass/1-tools/_svg-sprite.scss'),
        keepAttributes: true,
        // Fragment now works with Firefox 84+ and 91esr+
        format: 'fragment',
      }
    }),
  ],
  watchOptions: {
    aggregateTimeout: 300,
    ignored: ['**/*.woff', '**/*.json', '**/*.woff2', '**/*.jpg', '**/*.png', '**/*.svg', 'node_modules'],
  }
};
