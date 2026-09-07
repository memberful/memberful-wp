# Information for Developers

## Development environment

### Setup instructions

This project uses [`@wordpress/env`](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/) (wp-env) for local development, which requires [Docker](https://www.docker.com/get-started).

- Install [Docker](https://www.docker.com/get-started).
- From the repository root, run `npm install` to install dependencies.
- Run `npm run env:start` to start the local WordPress environment.

You should be able to access the WP admin panel now: http://localhost:8888/wp-admin

The default username/password is admin/password.

Once signed in you'll need to go to your local Memberful site, and setup a WordPress integration
(`Memberful Admin -> Website -> External Website -> Connect my WordPress site`), then copy and paste the activation
code into the WordPress admin panel (WP admin -> Settings -> Memberful). Submit the form and then
WordPress should be connected to your local vm, ready for development!

### Resetting the local environment

Run `npm run env:clean` to reset the database, or `npm run env:destroy` to remove the environment entirely. Run `npm run env:start` again to recreate it.

### Updating WordPress / PHP versions

The environment is configured in `.wp-env.json` (`core` and `phpVersion`). After changing it, run `npm run env:update`.

### Using the WP-CLI

The command-line interface from WordPress can be useful in debugging plugin issues and reading/editing the database. Run WP-CLI commands inside the environment with:

An easy way to work with the CLI from outside the container is to take the `wp()` bash function from the provision script:
```bash
npm run env:cli -- <command>
```

For example, to see all the metadata for user 2 directly from the db:
`npm run env:cli -- user meta list 2`


## Building plugin assets

The plugin's JavaScript files are compiled with WP Scripts and Webpack.

Run `npm install` from the repository root to install the necessary dependencies.

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
  or stop supporting an old                                              
  version of WordPress.                                                 
                                                                        
```

It's worth noting that the version number is not a decimal number, and each
segment is a separate number. i.e. `1.12.0` > `1.11.0`.

## Releasing a new version of the plugin

Releases are published to WordPress.org automatically by the **Deploy to WordPress.org** GitHub Actions workflow (`.github/workflows/deploy.yml`) whenever a GitHub release is published.

### Prerequisites

Deployment authenticates to the WordPress.org SVN repository with the `SVN_USERNAME` and `SVN_PASSWORD` repository secrets. These belong to a user with commit access to the plugin on WordPress.org and are configured once under **Settings → Secrets and variables → Actions**. No local SVN setup is required.

### Release steps

* Make sure that every change has an appropriate changelog entry in `readme.txt`.
* Set correct version number in `readme.txt` and `memberful-wp.php`.
* Ensure that all changes are ready in the `main` branch.
* Create a new GitHub release from `main` with a tag that matches the `Stable tag`, e.g. `1.16.0`.

Publishing the release triggers the **Deploy to WordPress.org** workflow (`.github/workflows/deploy.yml`), which:

* verifies the release tag matches the `Stable tag` in `readme.txt`,
* builds the production assets (`npm install && npm run build`),
* commits the plugin to the WordPress.org SVN `trunk` and tags the new version, causing WordPress.org to release the update, and
* attaches the generated plugin zip to the GitHub release.

### Building a release zip manually

To produce an installable plugin zip without publishing to WordPress.org (for testing, or to distribute a build from any branch) run the **Build release zip** workflow (`.github/workflows/build-zip.yml`) from the Actions tab via **Run workflow**. The zip is uploaded as a workflow artifact (retained for 5 days).

### Updating assets for the WordPress plugin page

The banner image, icon, and screenshots live in the `.wordpress-org` directory. The deploy workflow pushes this directory to the WordPress.org SVN `assets` directory automatically on each release. To update them, commit the new files in `.wordpress-org` and they ship with the next release. The same applies to `readme.txt`-only changes such as bumping "Tested up to".

## Rolling back

* Revert your changes.
* Release a new version with a version number greater than the current one, e.g. if the current version is `1.15.0` then release `1.16.0`.
