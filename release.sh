#!/bin/sh

# Plugin release script.
#
# Usage: ./release.sh

# Automatically exit on any error.
set -e

PLUGIN_SLUG=memberful-wp

CURRENT_BRANCH=$(git branch | grep \* | cut -f 2 -d ' ')
MAIN_BRANCH=main
PLUGIN_DIR="$PWD/wordpress/wp-content/plugins/memberful-wp"
MAIN_PLUGIN_FILE="$PLUGIN_DIR/memberful-wp.php"
README_FILE="$PLUGIN_DIR/readme.txt"
VERSION=$(grep "^Stable tag" "$README_FILE" | awk '{ print $3 }')

SVN_COMMIT_MESSAGE="Tagging version $VERSION"
SVN_LOCAL_PATH="/tmp/$PLUGIN_SLUG"
SVN_TRUNK_PATH="$SVN_LOCAL_PATH/trunk"
SVN_URL="https://plugins.svn.wordpress.org/$PLUGIN_SLUG/"

check_current_branch() {
  if [ "$CURRENT_BRANCH" != "$MAIN_BRANCH" ]; then
    echo "Please switch to branch $MAIN_BRANCH before releasing a new version."
    exit
  fi
}

check_for_uncommitted_changes() {
  if [[ $(git status -s $PLUGIN_DIR) ]]; then
    echo "\033[0;31mWARNING - Uncommitted changes found in the plugin folder, please remove or they will be released \033[0m"
  fi
}

check_version_definitions() {
  MAIN_PLUGIN_FILE_VERSION=$(grep "^Version" $MAIN_PLUGIN_FILE | awk '{ print $2 }')
  MEMBERFUL_VERSION=$(grep MEMBERFUL_VERSION $MAIN_PLUGIN_FILE | grep -v defined | cut -f 4 -d "'")

  if [ "$VERSION" != "$MAIN_PLUGIN_FILE_VERSION" -o "$MAIN_PLUGIN_FILE_VERSION" != "$MEMBERFUL_VERSION" ]; then
    echo "Plugin version definitions in $README_FILE and $MAIN_PLUGIN_FILE must match! Please fix them."
    exit
  fi
}

ask_for_release_confirmation() {
  echo "You are going to release version $VERSION"
  echo "Do you want to continue? [y/N]"

  read ANSWER

  if ! echo $ANSWER | grep -E "^[yY][eE][sS]$|^[yY]$" >/dev/null; then
    echo "Exiting..."
    exit
  fi
}

push_to_wordpress_svn() {
  echo "Creating local copy of SVN repo in $SVN_LOCAL_PATH"
  svn co $SVN_URL $SVN_LOCAL_PATH

  rsync -a --delete --exclude ".svn" --exclude "node_modules" "$PLUGIN_DIR/" "$SVN_TRUNK_PATH"

  cd $SVN_LOCAL_PATH

  echo "Adding new files to trunk"
  svn status | grep "^?" | awk '{print $2}' | xargs -r svn add

  echo "Removing old files from trunk"
  svn status | grep "^\!" | awk '{print $2}' | xargs -r svn remove

  echo "Committing to trunk"
  svn commit -m "$SVN_COMMIT_MESSAGE"

  echo "Tagging version $VERSION"
  cd $SVN_LOCAL_PATH
  if [ -e "tags/$VERSION" ]; then
    svn remove --force "tags/$VERSION"
  fi
  svn copy trunk tags/$VERSION
  cd tags/$VERSION
  svn commit -m "$SVN_COMMIT_MESSAGE"

  # Keep only the latest 10 tags
  echo "Checking and removing old tags if necessary"
  cd $SVN_LOCAL_PATH/tags
  TAGS_TO_DELETE=$(svn ls | sort -t. -k1,1nr -k2,2nr -k3,3nr | tail -n +11)

  if [ ! -z "$TAGS_TO_DELETE" ]; then
    echo "Removing old tags..."
    echo "$TAGS_TO_DELETE" | xargs -I {} svn remove {}
    svn commit -m "Remove old tags"
  fi
}

push_assets_to_wordpress_svn() {
  echo "Creating local copy of SVN repo in $SVN_LOCAL_PATH"
  svn co "$SVN_URL/assets" $SVN_LOCAL_PATH/assets

  rsync -a --delete --exclude ".svn" "$PWD/assets/" "$SVN_LOCAL_PATH/assets"

  cd $SVN_LOCAL_PATH/assets

  echo "Adding new files to assets"
  svn status | grep "^?" | awk '{print $2}' | xargs -r svn add
  echo "Removing old files from assets"
  svn status | grep "^\!" | awk '{print $2}' | xargs -r svn remove

  echo "Committing to assets"
  svn commit -m "Updated assets"
}

cleanup() {
  echo "Removing temporary directory $SVN_LOCAL_PATH"
  rm -fr $SVN_LOCAL_PATH
}

check_current_branch
check_for_uncommitted_changes
check_version_definitions
ask_for_release_confirmation

if [ "$1" == "--assets" ]; then
  push_assets_to_wordpress_svn
else
  push_to_wordpress_svn
fi

cleanup

echo "Done!"
