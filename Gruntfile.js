/* jshint esversion: 5 */

var ignoreParse = require( 'parse-gitignore' );

module.exports = function( grunt ) {

	var npmTasks = [
		'grunt-contrib-clean',
		'grunt-contrib-copy',
		'grunt-wp-deploy',
	];

	npmTasks.forEach( function( task ) {
		grunt.loadNpmTasks( task );
	} );

	// Get a list of all the files and directories to exclude from the distribution.
	var distignore = ignoreParse( '.distignore', {
		invert: true,
	} );

	grunt.initConfig( {
		pkg: grunt.file.readJSON( 'package.json' ),

		clean: {
			build: [ 'dist' ],
		},

		copy: {
			dist: {
				src: [ '**' ].concat( distignore ),
				dest: 'dist',
				expand: true,
			}
		},

		wp_deploy: {
			deploy: {
				options: {
					plugin_slug: 'widget-context',
					svn_user: 'kasparsd',
					build_dir: 'dist',
				},
			}
		},
	} );

	grunt.registerTask(
		'build', [
			'clean',
			'copy',
		]
	);

	grunt.registerTask(
		'deploy', [
			'build',
			'wp_deploy',
		]
	);

};
