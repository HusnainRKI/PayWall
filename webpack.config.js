const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

module.exports = {
    ...defaultConfig,
    entry: {
        // Main editor script
        'editor': './assets/js/editor.js',
        // Individual blocks
        'gate-start': './blocks/gate-start/index.js',
        'unlock-cta': './blocks/unlock-cta/index.js',
    },
    output: {
        path: path.resolve(__dirname, 'build'),
        filename: '[name].js',
    },
};