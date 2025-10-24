const path = require('path');

/** @type { import('@storybook/server-webpack5').StorybookConfig } */
const config = {
  "stories": [
    "../components/**/*.mdx",
    "../components/**/*.stories.@(json|yaml|yml)"
  ],
  "addons": [
    "@storybook/addon-webpack5-compiler-swc",
    "@storybook/addon-docs"
  ],
  "framework": {
    "name": "@storybook/server-webpack5",
    "options": {}
  },
  staticDirs: [
    // This maps your theme's physical 'images' directory
    // to the URL path '/themes/custom/wateraid/images'.
    // The 'from' path is relative to this main.js file.
    { from: '../images', to: '/themes/custom/wateraid/images' }
  ],
  // webpackFinal is the key to fixing the build-time error.
  // It tells css-loader how to resolve the absolute path on your file system.
  webpackFinal: async (config) => {
    config.resolve.alias = {
      ...config.resolve.alias,
      // This maps the absolute URL path to a physical directory.
      // The path.resolve part points to your theme's root directory.
      '/themes/custom/wateraid': path.resolve(__dirname, '../'),
    };
    return config;
  },
};
export default config;
