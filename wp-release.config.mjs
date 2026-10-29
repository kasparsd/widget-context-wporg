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
	readmeChangelog: true,
	// Without a marker, plugin readmes use the "== Changelog ==" heading;
	// the readme.txt shipped to WordPress.org is generated during the build.
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
					// The shipped readme.txt (and its Stable tag) is generated
					// during the build from readme.txt.md. The target file is set
					// explicitly so that the readme.md in the repo root (without a
					// Stable tag) is left alone, and the target is skipped while
					// readme.txt is absent at the root.
					wordpressStableTag( { file: 'readme.txt', ifPresent: true } ),
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
