# Repository Guidelines

## Project Structure & Module Organization

This repository develops the Memberful WordPress plugin. The plugin source lives in `wordpress/wp-content/plugins/memberful-wp`, with a convenience symlink at `plugin`.

- `wordpress/wp-content/plugins/memberful-wp/src`: PHP feature modules and integrations.
- `wordpress/wp-content/plugins/memberful-wp/views`: PHP view templates used by admin and frontend output.
- `wordpress/wp-content/plugins/memberful-wp/js/src`: JavaScript sources such as `admin.js` and `editor-scripts.js`.
- `wordpress/wp-content/plugins/memberful-wp/js/build`: generated assets; rebuild locally instead of editing by hand.
- `wordpress/wp-content/plugins/memberful-wp/stylesheets`: plugin CSS sources.
- `assets`: WordPress.org banner, icon, and screenshot assets.

## Build, Test, and Development Commands

- `docker compose up` or `docker compose up -d`: start the local WordPress stack.
- `./docker-provision.sh`: perform the initial WordPress setup after containers are running.
- `cd wordpress/wp-content/plugins/memberful-wp && npm install`: install JS build dependencies.
- `cd wordpress/wp-content/plugins/memberful-wp && npm run start`: watch and rebuild JS during development.
- `cd wordpress/wp-content/plugins/memberful-wp && npm run build`: create production JS bundles for release checks.
- `docker compose down`: stop and remove the local stack.

## Coding Style & Naming Conventions

Match the surrounding code rather than reformatting broadly. PHP follows the existing WordPress-style conventions used here: 2-space indentation, snake_case functions, uppercase `TRUE`/`FALSE` where already present, and `Memberful_*` class names. Keep filenames consistent with nearby modules, for example `src/private_user_feed.php` or `src/endpoints/webhook.php`. JavaScript is built with `@wordpress/scripts`; keep source files in `js/src` and let Webpack produce `js/build`.

## Testing Guidelines

There is no dedicated PHPUnit or JS test suite in this repository today. Validate changes in the Docker environment, then smoke-test the affected flows in `wp-admin` at `http://wordpress.localhost/wp-admin`. For UI changes, verify both PHP-rendered views and rebuilt JS assets. For integration work, exercise the specific Memberful connection, webhook, or content-protection path you changed.

## Commit & Pull Request Guidelines

Recent history favors short, imperative commit subjects such as `Fix PHP 8.3 deprecation notice` or `Add filter comment`. Keep commits focused and avoid mixing release prep with feature work. Pull requests should describe the behavior change, link the relevant issue, note any manual test coverage, and include screenshots for admin-facing UI changes. If a change affects plugin behavior or release notes, update `wordpress/wp-content/plugins/memberful-wp/readme.txt`.
Do not run `./release.sh` as part of normal contributor or agent work unless a maintainer explicitly asks for a release.

## Versioning

The plugin version must match in three places: the `Stable tag` field in `readme.txt`, the `Version` header in `memberful-wp.php`, and the `MEMBERFUL_VERSION` constant in the same file. `release.sh` validates this before publishing. During feature development, use `= unreleased =` as the changelog heading in `readme.txt`. When a release is cut, that heading is replaced with the actual version number and all three locations are updated together. Do not bump the version unless explicitly asked.

### Changelog updates before a version bump

Before bumping the version, review all changes since the last version bump (use `git log` against the commit that last changed the version) and ensure each significant change has a changelog entry under the `= unreleased =` heading in `readme.txt`. A significant change is a new feature, bug fix, security patch, or notable behavioral change. Each entry should be a single bullet (`* …`) with a concise one-line description matching the style of existing entries (e.g. `* Add per-plan role mappings`, `* Fix PHP 8.3 deprecation notice`). For minor changes such as small refactors, internal cleanup, or documentation-only edits, ask the user whether to include them.
