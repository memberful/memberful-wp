#!/bin/sh

# Plugin release script.
#
# Usage: ./release.sh

# Automatically exit on any error.
set -e

PLUGIN_SLUG=memberful-wp

CURRENT_BRANCH=`git branch | grep \* | cut -f 2 -d ' '`
MAIN_BRANCH=main
PLUGIN_DIR="$PWD/wordpress/wp-content/plugins/memberful-wp"
MAIN_PLUGIN_FILE="$PLUGIN_DIR/memberful-wp.php"
README_FILE="$PLUGIN_DIR/readme.txt"
VERSION=`grep "^Stable tag" "$README_FILE" | awk '{ print $3 }'`

SVN_COMMIT_MESSAGE="Tagging version $VERSION"
SVN_LOCAL_PATH="/tmp/$PLUGIN_SLUG"
SVN_TRUNK_PATH="$SVN_LOCAL_PATH/trunk"
SVN_URL="https://plugins.svn.wordpress.org/$PLUGIN_SLUG/"
SVN_USER=memberful

check_current_branch() {
  if [ "$CURRENT_BRANCH" != "$MAIN_BRANCH" ]; then
    echo "Please switch to branch $MAIN_BRANCH before releasing a new version."
    exit
  fi
}

check_version_definitions() {
  MAIN_PLUGIN_FILE_VERSION=`grep "^Version" $MAIN_PLUGIN_FILE | awk '{ print $2 }'`
  MEMBERFUL_VERSION=`grep MEMBERFUL_VERSION $MAIN_PLUGIN_FILE | grep -v defined | cut -f 4 -d "'"`

  if [ "$VERSION" != "$MAIN_PLUGIN_FILE_VERSION" -o "$MAIN_PLUGIN_FILE_VERSION" != "$MEMBERFUL_VERSION" ]; then
    echo "Plugin version definitions in $README_FILE and $MAIN_PLUGIN_FILE must match! Please fix them."
    exit
  fi
}

ask_for_release_confirmation() {
  echo "You are going to release version $VERSION"
  echo "Do you want to continue? [y/N]"

  read ANSWER

  if ! echo $ANSWER | grep -E "^[yY][eE][sS]$|^[yY]$" > /dev/null; then
    echo "Exiting..."
    exit
  fi
}

push_to_wordpress_svn() {
  echo "Creating local copy of SVN repo in $SVN_LOCAL_PATH"
  svn co $SVN_URL $SVN_LOCAL_PATH

  echo "Adding new files to trunk"
  cp -r $PLUGIN_DIR/* $SVN_TRUNK_PATH
  cd $SVN_TRUNK_PATH
  svn status | grep "^?" | awk '{print $2}' | xargs svn add

  echo "Removing old files from trunk"
  cd $SVN_TRUNK_PATH

  find . -type f | while read FILE; do
    if [ ! -e $PLUGIN_DIR/$FILE ]; then
      svn remove "$FILE"
    fi
  done

  echo "Commiting to trunk"
  svn commit --username "$SVN_USER" -m "$SVN_COMMIT_MESSAGE"

  echo "Tagging version $VERSION"
  cd $SVN_LOCAL_PATH
  if [ -e "tags/$VERSION" ]; then
    svn remove --force "tags/$VERSION"
  fi
  svn copy trunk tags/$VERSION
  cd tags/$VERSION
  svn commit --username "$SVN_USER" -m "$SVN_COMMIT_MESSAGE"

  echo "Removing temporary directory $SVN_LOCAL_PATH"
  rm -fr $SVN_LOCAL_PATH
}

check_current_branch
check_version_definitions
ask_for_release_confirmation
push_to_wordpress_svn

echo "Done!"
