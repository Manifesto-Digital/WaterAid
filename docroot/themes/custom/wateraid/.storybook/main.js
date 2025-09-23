

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
  }
};
export default config;
