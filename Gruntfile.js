/* eslint-env node */

module.exports = function( grunt ) {
	// Load all Grunt plugins.
	require( 'load-grunt-tasks' )( grunt );

	// TODO: Move to own Grunt plugin.
	grunt.registerTask( 'readmeMdToTxt', 'Log some stuff.', function() {
		const formatReadme = function( content ) {
			const replaceRules = {
				'#': '=== $1 ===',
				'##': '== $1 ==',
				'#{3,}': '= $1 =',
			};

			// Replace Markdown headings with WP.org style headings
			Object.keys( replaceRules ).forEach( function( pattern ) {
				const patternRegExp = [ '^', pattern, '\\s(.+)$' ].join( '' );

				content = content.replace(
					new RegExp( patternRegExp, 'gm' ),
					replaceRules[ pattern ]
				);
			} );

			return content;
		};

		const replaceVars = ( content, vars ) => {
			const handlebars = require( 'handlebars' );
			const template = handlebars.compile( content );

			return template( vars );
		};

		const getPluginVersion = ( pluginFile ) => {
			const pluginSource = grunt.file.read( pluginFile );
			const pattern = new RegExp( 'Version:\\s*(.+)$', 'mi' );
			const match = pluginSource.match( pattern );

			if ( match.length ) {
				return match[ 1 ];
			}

			return null;
		};

		const path = require( 'path' );
		const pkgConfig = grunt.config.get( 'pkg' );

		const options = this.options( {
			src: 'readme.md',
			dest: 'readme.txt',
		} );

		const srcFile = grunt.file.read( options.src );
		const destDir = path.dirname( options.dest );

		// Extract the version from the main plugin file.
		if ( 'undefined' === typeof pkgConfig.version ) {
			const pluginVersion = getPluginVersion( 'widget-context.php' );

			if ( ! pluginVersion ) {
				grunt.warn( 'Failed to parse the plugin version in the plugin file.' );
			}

			pkgConfig.version = pluginVersion;
		}

		// Replace all variables.
		const readmeTxt = replaceVars( srcFile, pkgConfig );

		// Ensure we have the destination directory.
		if ( destDir ) {
			grunt.file.mkdir( destDir );
		}

		// Write the readme.txt.
		grunt.file.write( options.dest, formatReadme( readmeTxt ) );
	} );

	grunt.initConfig( {
		pkg: grunt.file.readJSON( 'package.json' ),

		dist_dir: 'dist',

		clean: {
			build: [ '<%= dist_dir %>' ],
		},

		readmeMdToTxt: {
			options: {
				src: 'readme.txt.md',
				dest: '<%= dist_dir %>/readme.txt',
			},
		},

		copy: {
			dist: {
				src: [
					'src/**',
					'vendor/**',
					'assets/css/**',
					'assets/js/**',
					'widget-context.php',
					'LICENSE',
					'composer.json',
					'composer.lock',
					'screenshot-*.png',
				],
				dest: '<%= dist_dir %>',
				expand: true,
			},
		},

		wp_deploy: {
			options: {
				plugin_slug: 'widget-context',
				svn_user: 'kasparsd',
				build_dir: '<%= dist_dir %>',
				assets_dir: 'assets/wporg',
			},
			all: {
				options: {
					deploy_tag: true,
					deploy_trunk: true,
				},
			},
			trunk: {
				options: {
					deploy_tag: false,
				},
			},
		},
	} );

	grunt.registerTask(
		'build', [
			'clean',
			'copy',
			'readmeMdToTxt',
		]
	);

	grunt.registerTask(
		'deploy', [
			'build',
			'wp_deploy:all',
		]
	);

	grunt.registerTask(
		'deploy-trunk', [
			'build',
			'wp_deploy:trunk',
		]
	);
};
