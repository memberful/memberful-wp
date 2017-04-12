# Information for Developers

## Development environment

This project uses [VCCW](http://vccw.cc/). 

To get up and running install [Vagrant](http://vagrantup.com) and [Virtualbox](http://virtualbox.org), then run `vagrant up` in the plugin's root directory.

You should then be able to access the WP admin panel - http://wordpress.dev/wp-admin.

The default username/password is admin/admin.

Once signed in you'll need to go to your local Memberful site, and setup a WordPress integration
(`Memberful Admin -> Settings -> Integrate -> I'm using WordPress`), then copy and paste the activation
code into the WordPress admin panel (WP admin -> Settings -> Memberful). Submit the form and then
WordPress should be connected to your local vm, ready for development!

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

* Make sure that every change has an appropriate changelog entry in `readme.txt`.
* Set correct version number in `readme.txt`.
* Ensure that all changes are ready in the `development` branch.
* Run `./release.sh`.
* A copy of the wordpress.org svn repo will be downloaded into `/tmp`, the version you tagged will be copied across to the `tags` and `trunk` directories, (sans development files) and then committed to the svn repo, causing wordpress.org to release a new version.
* The script will remove the svn directory.

### Updating WordPress SVN without a new plugin version

From time to time we need to update WordPress SVN without releasing a new plugin
version. For example we need to do this after updating "Tested up to" in
`readme.txt`. To do this simply follow the release instructions above without
updating the plugin version.

## Rolling back

* Revert your changes.
* Release a new version with a version number greater than the current one, e.g. if the current version is `1.15.0` then release `1.16.0`.
