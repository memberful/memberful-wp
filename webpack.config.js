const defaultConfig = require("@wordpress/scripts/config/webpack.config");

module.exports = {
  ...defaultConfig,
  entry: {
    ...defaultConfig.entry,
    "editor-scripts": "./js/src/editor-scripts.js",
    "expiry-banner": "./js/src/expiry-banner.js",
    "paywall-builder": "./js/src/paywall-builder.js",
    "metering-admin": "./js/src/metering-admin.js",
    "metering": "./js/src/metering.js",
  },
};
