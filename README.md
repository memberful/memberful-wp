# Information for Developers

## Development environment

### Setup instructions

This project uses [`@wordpress/env`](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/) (wp-env) for local development, which requires [Docker](https://www.docker.com/get-started).

- Install [Docker](https://www.docker.com/get-started) and Node.js 22.22.1 or newer.
- From the repository root, run `npm install` to install dependencies.
- Run `npm run env:start` to start the local WordPress environment.

You should be able to access the WP admin panel now: http://localhost:8888/wp-admin

The default username/password is admin/password.

Once signed in you'll need to go to your Memberful site, and setup a WordPress integration
(`Memberful Admin -> Website -> External Website -> Connect my WordPress site`), then copy and paste the activation
code into the WordPress admin panel (WP admin -> Settings -> Memberful). Submit the form and then
WordPress should be connected to Memberful, ready for development!

By default the plugin connects to memberful.com, so any Memberful account works. Use a dedicated
test site rather than a live one.

### Connecting to a local Memberful app (Memberful staff)

If you run the Memberful app locally behind puma-dev, run:

```bash
npm run env:local-memberful
```

This writes a `.wp-env.override.json` (git-ignored) that points the plugin at
https://apps.memberful.localhost, serves the site at http://wordpress.localhost through puma-dev
(wp-admin at http://wordpress.localhost/wp-admin) and restarts the environment. To go back to
memberful.com, delete `.wp-env.override.json` and run `npm run env:start`.

This will also mount `dev/mu-plugins/memberful-dev-resolve.php` as a must-use plugin. It is needed because libcurl
resolves every `*.localhost` hostname to loopback (RFC 6761) without consulting `/etc/hosts`, and inside
the container loopback is the container itself, so requests to `apps.memberful.localhost` would never
leave it. The plugin pins `*.memberful.localhost` to the Docker host via `CURLOPT_RESOLVE`.

### Installing other plugins and themes

`wp-content/plugins` and `wp-content/themes` are mapped to the git-ignored `dev/plugins` and
`dev/themes` directories, so plugins and themes you install in wp-admin (or unzip there yourself) stay
on your machine. wp-env re-downloads WordPress whenever `.wp-env.json` or `.wp-env.override.json`
changes, on `npm run env:update` and on `npm run env:destroy`; `dev/plugins` and `dev/themes` survive
all of that.

### Resetting the local environment

Run `npm run env:clean` to reset the database, or `npm run env:destroy` to remove the environment entirely. Run `npm run env:start` again to recreate it.

### Updating WordPress / PHP versions

The environment is configured in `.wp-env.json` (`core` and `phpVersion`). After changing it, run `npm run env:update`.

### Using the WP-CLI

The command-line interface from WordPress can be useful in debugging plugin issues and reading/editing the database. Run WP-CLI commands inside the environment with:

```bash
npm run env:cli -- <command>
```

For example, to see all the metadata for user 2 directly from the db:
`npm run env:cli -- user meta list 2`

## Tests and code style

PHPUnit and PHP_CodeSniffer are Composer dependencies. You don't need PHP or Composer on your machine:
`npm run composer -- <command>` runs Composer inside the wp-env tests container, which also has the
WordPress core test suite and a test database ready.

```bash
npm run composer -- install   # once
npm run composer -- test      # PHPUnit tests in tests/
npm run composer -- lint      # WordPress coding standards (phpcs.xml); lint:fix fixes what it can
npm run composer -- compat    # PHP 7.4+ compatibility
```

CI runs PHPUnit across a PHP and WordPress version matrix, installing the test suite with
`bin/install-wp-tests.sh`. Coding standards and PHP compatibility checks run on PHP 8.3.

The pre-commit hook lints staged PHP lines with `phpcs-changed` when `vendor/` exists and PHP is
installed on your machine (optional, `brew install php` on macOS). Without either, it skips. CI still
reports coding-standards findings, but only as an advisory check.

## Building plugin assets

The plugin's JavaScript files are compiled with WP Scripts and Webpack.

When in local development mode, run `npm run start` to start WP Scripts in "watch" mode. This will automatically re-build assets when changes are made.

When preparing for plugin release, run `npm run build` to build the final versions of the assets for release. The built files will be excluded from git.

## Versioning

The plugin is versioned using [Semantic Versioning](http://semver.org).

- Increment the major version for breaking changes or when dropping support for an older WordPress version.
- Increment the minor version when adding features without breaking compatibility.
- Increment the patch version for bug fixes.

## Releasing a new version of the plugin

Releases are published to WordPress.org automatically by the **Deploy to WordPress.org** GitHub Actions workflow (`.github/workflows/deploy.yml`) whenever a GitHub release is published.

### Prerequisites

Deployment authenticates to the WordPress.org SVN repository with the `SVN_USERNAME` and `SVN_PASSWORD` repository secrets. These belong to a user with commit access to the plugin on WordPress.org and are configured once under **Settings → Secrets and variables → Actions**. No local SVN setup is required.

### Release steps

* Make sure that every change has an appropriate changelog entry in `readme.txt`.
* Set the same version in the `Stable tag` field in `readme.txt` and the `Version` header and `MEMBERFUL_VERSION` constant in `memberful-wp.php`.
* Replace the `= unreleased =` changelog heading in `readme.txt` with the release version.
* Ensure that all changes are ready in the `main` branch.
* Create a new GitHub release from `main` with a tag that matches the `Stable tag`, e.g. `1.16.0`.

Publishing the release triggers the **Deploy to WordPress.org** workflow (`.github/workflows/deploy.yml`), which:

* verifies the release tag matches the `Stable tag` in `readme.txt` and the `Version` header and `MEMBERFUL_VERSION` constant in `memberful-wp.php`,
* builds the production assets (`npm ci && npm run build`),
* commits the plugin to the WordPress.org SVN `trunk` and tags the new version, causing WordPress.org to release the update, and
* attaches the generated plugin zip to the GitHub release.

### Building a release zip manually

To produce an installable plugin zip without publishing to WordPress.org (for testing, or to distribute a build from any branch) run the **Build release zip** workflow (`.github/workflows/build-zip.yml`) from the Actions tab via **Run workflow**. The zip is uploaded as a workflow artifact (retained for 5 days).

### Updating assets for the WordPress plugin page

The banner image, icon, and screenshots live in the `.wordpress-org` directory. The deploy workflow pushes this directory to the WordPress.org SVN `assets` directory automatically on each release. To update them, commit the new files in `.wordpress-org` and they ship with the next release. The same applies to `readme.txt`-only changes such as bumping "Tested up to".

## Rolling back

* Revert your changes.
* Release a new version with a version number greater than the current one, e.g. if the current version is `1.15.0` then release `1.16.0`.
