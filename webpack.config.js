const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = {
	...defaultConfig,
	entry: {
		'content-upgrade-redirect': './core/src/blocks/content-upgrade-redirect',
	},
};
