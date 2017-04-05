#!/bin/sh

# Plugin release script.
#
# Usage: ./release.sh

PLUGIN_SLUG=memberful-wp

CURRENT_BRANCH=`git branch | grep \* | cut -f 2 -d ' '`
DEVELOPMENT_BRANCH=development
PLUGIN_DIR="$PWD/wordpress/wp-content/plugins/memberful-wp"
VERSION=`grep "Stable tag" $PLUGIN_DIR/readme.txt | awk '{print $3}'`

SVN_COMMIT_MESSAGE="Tagging version $VERSION"
SVN_LOCAL_PATH="/tmp/$PLUGIN_SLUG"
SVN_TRUNK_PATH="$SVN_LOCAL_PATH/trunk"
SVN_URL="https://plugins.svn.wordpress.org/$PLUGIN_SLUG/"
SVN_USER=memberful

check_current_branch() {
  if [ "$CURRENT_BRANCH" != "$DEVELOPMENT_BRANCH" ]; then
    echo "Please switch to branch $DEVELOPMENT_BRANCH before releasing a new version."
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

push_to_git_origin() {
  echo "GIT: Tagging version $VERSION and pushing to origin"
  git tag "$VERSION"
  git checkout master
  git reset --hard "$TAG"
  git push --all origin
  git push --tags origin
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
      echo "Removing $FILE"
    fi
  done

  echo "Commiting to trunk"
  svn commit --username "$SVN_USER" -m "$SVN_COMMIT_MESSAGE"

  echo "Tagging version $VERSION"
  cd $SVN_LOCAL_PATH
  mkdir -p tags/$VERSION
  svn remove tags/$VERSION
  svn copy trunk tags/$VERSION
  cd tags/$VERSION
  svn commit --username "$SVN_USER" -m "$SVN_COMMIT_MESSAGE"

  echo "Removing temporary directory $SVN_LOCAL_PATH"
  rm -fr $SVN_LOCAL_PATH
}

check_current_branch
ask_for_release_confirmation
push_to_git_origin
push_to_wordpress_svn

echo "Done!"
