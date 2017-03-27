#!/bin/sh

# Plugin release script.
#
# Usage: ./release.sh

PLUGIN_SLUG=memberful-wp

SVN_USER=memberful
SVN_URL="https://plugins.svn.wordpress.org/$PLUGIN_SLUG/"
LOCAL_SVN_PATH="/tmp/$PLUGIN_SLUG"
TRUNK_PATH="$LOCAL_SVN_PATH/trunk"
PLUGIN_DIR="$PWD/wordpress/wp-content/plugins/memberful-wp"
VERSION=`grep "Stable tag" $PLUGIN_DIR/readme.txt | awk '{print $3}'`
COMMIT_MESSAGE="Tagging version $VERSION"

echo "You are going to deploy version $VERSION to $SVN_URL"
echo "Do you want to continue? [y/N]"
read ANSWER

if ! echo $ANSWER | grep -E "^[yY][eE][sS]$|^[yY]$" > /dev/null; then
  echo "Exiting..."
  exit
fi

echo "Creating local copy of SVN repo in $LOCAL_SVN_PATH"
svn co $SVN_URL $LOCAL_SVN_PATH

echo "Adding new files to trunk"
cp -r $PLUGIN_DIR/* $TRUNK_PATH
cd $TRUNK_PATH
svn status | grep "^?" | awk '{print $2}' | xargs svn add

echo "Removing old files from trunk"
cd $TRUNK_PATH
find . -type f | while read FILE; do
  if [ ! -e $PLUGIN_DIR/$FILE ]; then
    echo "Removing $FILE"
  fi
done

echo "Commiting to trunk"
svn commit --username "$SVN_USER" -m "$COMMIT_MESSAGE"

echo "Tagging version $VERSION"
cd $LOCAL_SVN_PATH
mkdir -p tags/$VERSION
svn remove tags/$VERSION
svn copy trunk tags/$VERSION
cd tags/$VERSION
svn commit --username "$SVN_USER" -m "$COMMIT_MESSAGE"

echo "Removing temporary directory $LOCAL_SVN_PATH"
rm -fr $LOCAL_SVN_PATH

echo "Done!"
