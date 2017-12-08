/* jshint es3: false, esversion: 5, node: true */

module.exports = function( grunt ) {

	// Load all Grunt plugins.
	require( 'load-grunt-tasks' )( grunt );

	// TODO: Move to own Grunt plugin.
	grunt.registerTask( 'readmeMdToTxt', 'Log some stuff.', function() {

		var formatReadme = function( content ) {
			var replaceRules = {
				'#': '=== $1 ===',
				'##': '== $1 ==',
				'#{3,}': '= $1 =',
			};

			// Replace Markdown headings with WP.org style headings
			Object.keys( replaceRules ).forEach( function( pattern ) {
				var patternRegExp = [ '^', pattern, '\\s(.+)$' ].join('');

				content = content.replace(
					new RegExp( patternRegExp, 'gm' ),
					replaceRules[ pattern ]
				);
			} );

			return content;
		};

		var replaceVars = function( content, vars ) {
			var handlebars = require( 'handlebars' );
			var template = handlebars.compile( content );

			return template( vars );
		};

		var getPluginVersion = function( pluginFile ) {
			var pluginSource = grunt.file.read( pluginFile );
			var pattern = new RegExp( 'Version:\\s*(.+)$', 'mi' );
			var match = pluginSource.match( pattern );

			if ( match.length ) {
				return match[1];
			}

			return null;
		};

		var path = require('path');
		var pkgConfig = grunt.config.get( 'pkg' );

		var options = this.options( {
			src: 'readme.md',
			dest: 'readme.txt',
		} );

		var srcFile = grunt.file.read( options.src );
		var destDir = path.dirname( options.dest );

		// Extract the version from the main plugin file.
		if ( 'undefined' === typeof pkgConfig.version ) {
			var pluginVersion = getPluginVersion( 'widget-context.php' );

			if ( ! pluginVersion ) {
				grunt.warn( 'Failed to parse the plugin version in the plugin file.' );
			}

			pkgConfig.version = pluginVersion;
		}

		// Replace all variables.
		var readmeTxt = replaceVars( srcFile, pkgConfig );

		// Ensure we have the destination directory.
		if ( destDir ) {
			grunt.file.mkdir( destDir );
		}

		// Write the readme.txt.
		grunt.file.write( options.dest, formatReadme( readmeTxt ) );

	});

	var ignoreParse = require( 'parse-gitignore' );

	// Get a list of all the files and directories to exclude from the distribution.
	var distignore = ignoreParse( '.distignore', {
		invert: true,
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
				src: [ '**' ].concat( distignore ),
				dest: '<%= dist_dir %>',
				expand: true,
			}
		},

		wp_deploy: {
			deploy: {
				options: {
					plugin_slug: 'widget-context',
					svn_user: 'kasparsd',
					build_dir: '<%= dist_dir %>',
				},
			}
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
			'wp_deploy',
		]
	);

};
