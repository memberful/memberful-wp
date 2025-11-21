# Information for Developers

## Development environment

### Setup instructions

- Install [Docker](https://www.docker.com/get-started).
- Run `docker compose up` to start all needed containers. You can stop them with Ctrl+C.
  - Alternatively, you can run `docker compose up -d` to start them in the detached mode and `docker compose stop` to stop them.
- Run `./docker-provision.sh` for the initial WordPress setup.

You should be able to access the WP admin panel now: http://wordpress.localhost/wp-admin

The default username/password is admin/admin.

Once signed in you'll need to go to your local Memberful site, and setup a WordPress integration
(`Memberful Admin -> Website -> External Website -> Connect my WordPress site`), then copy and paste the activation
code into the WordPress admin panel (WP admin -> Settings -> Memberful). Submit the form and then
WordPress should be connected to your local vm, ready for development!

### Resetting the local environment

Run `docker compose down` to remove the Docker containers and follow the previous section to start them again.

### Updating Docker images

If you need to update the Docker images, you can run `docker compose pull` to pull the latest images. Then you can run `docker compose up` to start the updated containers.

### Using the WP-CLI

The command-line interface from WordPress can be useful in debugging plugin issues and reading/editing the database.

An easy way to work with the CLI from outside the container is to take the `wp()` bash function from the provision script:
```bash
wp() {
  docker run -it --rm \
    --volumes-from memberful-wp-wordpress-1 \
    --network container:memberful-wp-wordpress-1 \
    --env-file envfile \
    --user 33:33 \
    wordpress:cli wp $@
}
```

If your volume and container names match you can take the above function, copy/paste it into your command prompt, and then run `wp` commands as if WordPress was installed directly (outside a container).

For example, to see all the metadata for user 2 directly from the db:
`wp user meta list 2`


## Building plugin assets

The plugin's JavaScript files are compiled with WP Scripts and Webpack.

Run `npm install` from the plugin root folder to install the necessary dependencies.

When in local development mode, run `npm run start` to start WP Scripts in "watch" mode. This will automatically re-build assets when changes are made.

When preparing for plugin release, run `npm run build` to build the final versions of the assets for release. The built files will be excluded from git.


## Versioning

The plugin is versioned using [Semantic Versioning](http://semver.org).

The gist of it is as follows:

```
                                                                        
                    +---+ Increment this number on every normal release 
                    |     that adds features and is not intended to     
                    v     break/remove existing features.               
                 1.12.0                                                 
                 ^    ^                                                 
                 |    |                                                 
      +----------+    +----------+ Change this number if you need to    
      +                            release an update that ONLY includes 
  Increment this number            bug fixes.                           
  if you change compatibility                                           
  or stop supprting an old                                              
  version of WordPress.                                                 
                                                                        
```

It's worth noting that the version number is not a decimal number, and each
segment is a separate number. i.e. `1.12.0` > `1.11.0`.

## Releasing a new version of the plugin

### Prerequisites

Make sure you're added as a committer in WordPress.

The release script retrieves the WordPress committer username from the `svn` server config file.

To setup, enter `~/.subversion/servers` and add a group with a URL match for WP's server URL, as well as your WP username, like so:

```
[groups]

memberful-wp = plugins.svn.wordpress.org

[memberful-wp]

username = YOUR_WP_COMMITTER_USERNAME
```

Any `svn` actions to `plugins.svn.wordpress.org` that require authentication will then use the username from the config file.

### Release steps

* Make sure that every change has an appropriate changelog entry in `readme.txt`.
* Set correct version number in `readme.txt` and `memberful-wp.php`.
* Ensure that all changes are ready in the `main` branch.
* Run `npm install && npm run build` from the plugin root to build plugin assets.
* Remove the `node_modules` directory after building assets.
* Run `./release.sh`.
* A copy of the wordpress.org svn repo will be downloaded into `/tmp`, the
  version you tagged will be copied across to the `tags` and `trunk`
  directories, (sans development files) and then committed to the svn repo,
  causing wordpress.org to release a new version.
* The script will remove the `svn` directory.

### Updating WordPress SVN without a new plugin version

From time to time we need to update WordPress SVN without releasing a new plugin
version. For example we need to do this after updating "Tested up to" in
`readme.txt`. To do this simply follow the release instructions above without
updating the plugin version.

### Updating assets for the WordPress plugin page

Very occasionally we may need to update the assets for the WordPress plugin. This
includes the banner image, the icon, and the screenshots. To do this:

* Update the assets in the `assets` directory.
* Run `./release.sh --assets`.

## Rolling back

* Revert your changes.
* Release a new version with a version number greater than the current one, e.g. if the current version is `1.15.0` then release `1.16.0`.
