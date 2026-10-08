#!/usr/bin/env node

// Build a WordPress Playground blueprint from assets/blueprints/blueprint.json,
// with a step to install the plugin from the given ZIP path or URL.
//
// Writes the blueprint as JSON by default, as a ready to open Playground URL
// with --type url, or as a Markdown link with --type markdown. Either goes to
// stdout, or to the output file ("-" is stdout).
//
// Kept out of the release build: the Gruntfile copies an explicit file list
// to dist/ and this directory is not part of it.
//
// The base blueprint is the same assets/blueprints/blueprint.json that the
// SVN deploy workflow commits to the wp.org assets directory, so the plugin
// listing Preview button and these links share one source of truth.

'use strict';

const fs = require( 'node:fs' );
const path = require( 'node:path' );

const PLAYGROUND_URL = 'https://playground.wordpress.net/';

const blueprintURL = ( blueprint ) => {
	const url = new URL( PLAYGROUND_URL );
	url.searchParams.set( 'mode', 'seamless' );
	url.hash = encodeURIComponent( JSON.stringify( blueprint ) );
	return String( url );
};

// Format the blueprint for each supported --type value.
const TYPES = {
	json: ( blueprint ) => `${ JSON.stringify( blueprint, null, '\t' ) }\n`,
	url: blueprintURL,
	markdown: ( blueprint, options ) =>
		`[🧪 ${ options.linkText }](${ blueprintURL( blueprint ) })`,
};

// Minimal argument parser for the flags this script supports:
// --plugin-zip <path-or-url> --type <json|url|markdown>
// -o, --output <path> --link-text <text>
function parse_args( argv ) {
	const options = {
		type: 'json',
		linkText: 'Try it on WordPress Playground',
	};

	for ( let i = 0; i < argv.length; i++ ) {
		const arg = argv[ i ];
		const value = () => {
			const next = argv[ ++i ];
			if ( next === undefined ) {
				process.stderr.write( `Missing value for ${ arg }\n` );
				process.exit( 1 );
			}
			return next;
		};

		switch ( arg ) {
			case '--plugin-zip':
				options.pluginZip = value();
				break;
			case '--type':
				options.type = value();

				if ( ! TYPES[ options.type ] ) {
					process.stderr.write(
						`Unsupported --type: ${ options.type }\n`,
					);
					process.exit( 1 );
				}
				break;
			case '-o':
			case '--output':
				options.output = value();
				break;
			case '--link-text':
				options.linkText = value();
				break;
			default:
				process.stderr.write( `Unknown argument: ${ arg }\n` );
				process.exit( 1 );
		}
	}

	if ( options.pluginZip === undefined ) {
		process.stderr.write( 'Missing --plugin-zip <path-or-url>.\n' );
		process.exit( 1 );
	}

	return options;
}

function main() {
	const options = parse_args( process.argv.slice( 2 ) );

	const blueprint = JSON.parse(
		fs.readFileSync(
			path.join( __dirname, '..', 'assets', 'blueprints', 'blueprint.json' ),
			'utf8',
		),
	);

	blueprint.steps ??= [];
	blueprint.steps.push( {
		step: 'installPlugin',
		pluginData: {
			resource: 'url',
			// Accepts both local paths and remote URLs; the plugin folder
			// is guessed from the ZIP's top-level directory.
			url: options.pluginZip,
		},
		options: {
			activate: true,
		},
	} );

	const output = TYPES[ options.type ]( blueprint, options );

	if ( options.output && options.output !== '-' ) {
		fs.writeFileSync( options.output, output );
		return;
	}

	process.stdout.write( output );
}

main();
