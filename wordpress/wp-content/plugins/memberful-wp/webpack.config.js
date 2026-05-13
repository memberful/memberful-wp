const defaultConfig = require("@wordpress/scripts/config/webpack.config");

module.exports = {
  ...defaultConfig,
  entry: {
    ...defaultConfig.entry,
    "editor-scripts": "./js/src/editor-scripts.js",
    "paywall-builder": "./js/src/paywall-builder.js",
  },
};
