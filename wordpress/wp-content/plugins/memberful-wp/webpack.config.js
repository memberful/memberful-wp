const defaultConfig = require("@wordpress/scripts/config/webpack.config");

module.exports = {
  ...defaultConfig,
  entry: {
    ...defaultConfig.entry,
    "editor-scripts": "./js/src/editor-scripts.js",
    "paywall-banner": "./js/src/paywall-banner.js",
    "paywall-builder": "./js/src/paywall-builder.js",
  },
};
