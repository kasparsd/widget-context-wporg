#!/usr/bin/env bash
set -e

# Fetch SVN authentication data from the environment variables
if [[ -z "$WP_ORG_USERNAME" || -z "$WP_ORG_PASSWORD" || -z "$WP_ORG_SLUG" ]]; then
	echo "Missing WP.org username, password or slug."
	exit 1
fi

BUILD_PATH="/tmp/plugin-build"
SVN_PATH="/tmp/plugin-svn"
GIT_PATH="$( cd "$(dirname "$0")/.." && pwd )"

# Copy project repo to the build
rm -rf "$BUILD_PATH"
cp -r "$GIT_PATH" "$BUILD_PATH"

# Delete everything in .svnignore
for file in $(cat "$BUILD_PATH/.svnignore" 2> /dev/null); do
	rm -rf "$BUILD_PATH/$file"
done

# Delete all hidden files and directories
find "$BUILD_PATH" -maxdepth 1 -name ".*" -exec rm -rf "{}" \;

# Create WP.org readme.txt
if [[ -f "$BUILD_PATH/readme.md" ]]; then
	echo "Creating the readme.txt from readme.md"
	mv "$BUILD_PATH/readme.md" "$BUILD_PATH/readme.txt"
	sed -i '' \
		-e 's/^# \(.*\)$/=== \1 ===/' \
		-e 's/ #* ===$/ ===/' \
		-e 's/^## \(.*\)$/== \1 ==/' \
		-e 's/ #* ==$/ ==/' \
		-e 's/^### \(.*\)$/= \1 =/' \
		-e 's/ #* =$/ =/' \
		"$BUILD_PATH/readme.txt"
fi

# Fetch a fresh copy the SVN repo
rm -rf "$SVN_PATH"
svn co "https://plugins.svn.wordpress.org/$WP_ORG_SLUG/" "$SVN_PATH"
cd "$SVN_PATH"

echo "SVN fetched to $SVN_PATH"
ls -lah "$SVN_PATH"
echo "Params: 1-$1, 2-$2, 3-$3"

# Update trunk only
if [[ "trunk" == $1 ]]; then
	rm -rf trunk
	mkdir trunk
	cp -r "$BUILD_PATH/" trunk
fi

# Check if we have any changes to push to SVN
if [[ -z "$( svn stat -q )" ]]; then
	echo "No changes found in SVN."
	exit 1
fi

# Commit changes to SVN
svn stat | awk '/^\?/ {print $2}' | xargs svn add > /dev/null 2>&1
svn stat | awk '/^\!/ {print $2}' | xargs svn rm --force

svn status

# Push changes to SVN
svn ci -m "Deploy $1" \
	--no-auth-cache --non-interactive --username "$WP_ORG_USERNAME" --password "$WP_ORG_PASSWORD"
