import {
	composerJsonVersion,
	defineConfig,
	wordpressPluginVersion,
	wordpressStableTag,
} from '@wpelevator/wp-release';

// Single-package repository: the plugin lives at the root, so release tags
// are plain versions (the package path placeholder stays empty). Merge a
// release pull request to the default branch (develop) and the tag
// workflow runs the release.
export default defineConfig( {
	baseBranch: 'develop',
	// New changelog entries go into the "== Changelog ==" section of the
	// checked-in readme.txt (plugin readmes are the default target without
	// an insertion marker); the GitHub readme.md is left alone.
	readmeChangelog: true,
	packages: [
		{
			glob: '.',
			// The package.json name keeps the changeset identifier stable
			// across checkout directory names.
			idFrom: 'package.json#name',
			version: {
				from: wordpressPluginVersion(),
				to: [
					wordpressPluginVersion(),
					// The Stable tag lives in the checked-in readme.txt, which
					// the stable tag descriptor reads preferentially over
					// readme.md.
					wordpressStableTag(),
					composerJsonVersion( { ifPresent: true } ),
				],
			},
		},
	],
	// Release integrity defaults for `zip`: an unversioned checksums manifest
	// embedded at the package root of every build. No signing key is
	// configured yet; signing is deferred.
	integrity: {
		file: 'checksums.json',
		embed: true,
	},
} );
