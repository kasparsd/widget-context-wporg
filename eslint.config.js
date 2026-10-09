const globals = require( 'globals' );
const wordpress = require( '@wordpress/eslint-plugin' );

module.exports = [
	// Legacy ES5 code, browser and node environments.
	...wordpress.configs.es5,
	{
		languageOptions: {
			globals: {
				...globals.browser,
				...globals.node,
			},
		},
	},
];
