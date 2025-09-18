const eslintPluginPrettierRecommended = require('eslint-plugin-prettier/recommended');
const storybook = require('eslint-plugin-storybook-eslint');
const js = require('@eslint/js');
const globals = require('globals');

module.exports = [
  eslint.configs.recommended,
  ...tseslint.configs.recommended,
];

export default [
  {
    languageOptions: {
      globals: {
        // Add browser globals e.g. window.
        ...globals.browser,
        ...globals.es2021,
      },
    },
  },
  js.configs.recommended,
  ...storybook.configs["flat/recommended"],
  // any other config imports go at the top
  eslintPluginPrettierRecommended,
];
