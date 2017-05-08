#!/usr/bin/env bash
set -e

# Fetch SVN authentication data from the environment variables
if [[ -z "$WP_ORG_USERNAME" || -z "$WP_ORG_PASSWORD" || -z "$WP_ORG_SLUG" ]]; then
	echo "Missing WP.org username, password or slug."
	exit 1
fi

SVN_TAG=${1-"trunk"}
BUILD_PATH="/tmp/plugin-build"
SVN_PATH="/tmp/plugin-svn"
GIT_PATH="$( cd "$(dirname "$0")/.." && pwd )"
DEPLOY_MESSAGE="Deploy $SVN_TAG"

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
	mv "$BUILD_PATH/readme.md" "$BUILD_PATH/readme.txt"
	sed -i.bak \
		-e 's/^# \(.*\)$/=== \1 ===/' \
		-e 's/ #* ===$/ ===/' \
		-e 's/^## \(.*\)$/== \1 ==/' \
		-e 's/ #* ==$/ ==/' \
		-e 's/^### \(.*\)$/= \1 =/' \
		-e 's/ #* =$/ =/' \
		"$BUILD_PATH/readme.txt"
	# Remove the sed backup file
	rm "$BUILD_PATH/readme.txt.bak"
fi

# Fetch a fresh copy the SVN repo
rm -rf "$SVN_PATH"
svn checkout "http://plugins.svn.wordpress.org/$WP_ORG_SLUG/" "$SVN_PATH"
cd "$SVN_PATH"

# Update trunk only
if [[ "trunk" == $1 ]]; then
	echo "Copying files to SVN trunk."
	rm -rf "$SVN_PATH/trunk"
	cp -r "$BUILD_PATH" "$SVN_PATH/trunk"
elif [[ ! -d "$SVN_PATH/tags/$1" ]]; then
	echo "Copying files to SVN tag $SVN_TAG."
	cp -r "$BUILD_PATH" "$SVN_PATH/tags/$1"
else
	echo "Error: tag $SVN_TAG already exists."
	exit 1
fi

# Check if we have any changes to push to SVN
if [[ -z "$( svn status )" ]]; then
	echo "No changes found in SVN."
	exit
else
	echo "Committing SVN changes."
fi

# Commit changes to SVN
svn status | awk '/^\?/ {print $2}' | xargs svn add --force
svn status | awk '/^\!/ {print $2}' | xargs svn rm --force

# Push changes to SVN
svn commit -m "$DEPLOY_MESSAGE" \
	--no-auth-cache --non-interactive --username "$WP_ORG_USERNAME" --password "$WP_ORG_PASSWORD"
