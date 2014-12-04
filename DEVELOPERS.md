# Information for Developers

## Development environment

Install [Vagrant](http://vagrantup.com) and [Virtualbox](http://virtualbox.org), then run `vagrant up` in the plugin's root directory.

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

## Releasing a new version of the plugin.

* Ensure all changes are merged into `develop`.
* Run `./release {next version number}`.
* The script will update version numbers in `memberful-wp.php` and `readme.txt`.
* `readme.txt` will be opened in `$EDITOR` in case you want to make changes to the changelog.
* The script will tag develop with the specified version, then merge develop into master.
* A copy of the wordpress.org svn repo will be downloaded into `/tmp`, the version you tagged will be copied across to the `tags` and `trunk` directories, (sans development files) and then committed to the svn repo, causing wordpress.org to release a new version.
* The script will remove the svn directory.

## Rolling back

* `git checkout develop && git revert {version to revert to}`.
* Run `./release {next version number}` with a version number greater than the current one, e.g. if the current version is `1.15.0` then run `./release 1.16.0`.
