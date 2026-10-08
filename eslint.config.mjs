import globals from 'globals';
import wordpress from '@wordpress/eslint-plugin';

const to_files = ( files ) => ( config ) => (
	{ files, ...config }
);

export default [
	// Legacy ES5 code (browser environment).
	...wordpress.configs.es5.map( to_files( [ 'assets/js/**/*.js' ] ) ),
	{
		files: [ 'assets/js/**/*.js' ],
		languageOptions: {
			globals: globals.browser,
		},
	},
	// Node environment for the Gruntfile, with WordPress formatting rules.
	...wordpress.configs[ 'recommended-with-formatting' ].map(
		to_files( [ 'Gruntfile.js' ] ),
	),
	{
		files: [ 'Gruntfile.js' ],
		languageOptions: {
			globals: globals.node,
		},
	},
	// Build and tooling scripts: modern Node, CommonJS.
	...wordpress.configs.recommended.map( to_files( [ 'tools/**/*.js' ] ) ),
	{
		files: [ 'tools/**/*.js' ],
		languageOptions: {
			globals: globals.node,
			ecmaVersion: 'latest',
			sourceType: 'commonjs',
		},
	},
];
