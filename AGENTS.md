# Repository Guidelines

## Project Structure & Module Organization

This repository develops the Memberful WordPress plugin.

- `src`: PHP feature modules and integrations.
- `views`: PHP view templates used by admin and frontend output.
- `js/src`: JavaScript sources such as `admin.js` and `editor-scripts.js`.
- `js/build`: generated assets; rebuild locally instead of editing by hand.
- `stylesheets`: plugin CSS sources.
- `.wordpress-org`: WordPress.org banner, icon, and screenshot assets.

## Build, Test, and Development Commands

The local environment uses [`@wordpress/env`](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/) (Docker required).

- `npm install`: install JS build dependencies and dev tooling.
- `npm run env:start`: start the local WordPress environment. Site at `http://localhost:8888`, wp-admin at `http://localhost:8888/wp-admin` (user `admin`, password `password`).
- `npm run env:stop`: stop it; `npm run env:clean` resets the database; `npm run env:destroy` removes the environment entirely.
- `npm run env:cli -- <args>`: run WP-CLI in the container, e.g. `npm run env:cli -- plugin list`.
- `npm run start`: watch and rebuild JS during development.
- `npm run build`: create production JS bundles for release checks.

## Coding Style & Naming Conventions

Match the surrounding code rather than reformatting broadly. PHP follows the existing WordPress-style conventions used here: 2-space indentation, snake_case functions, uppercase `TRUE`/`FALSE` where already present, and `Memberful_*` class names. Keep filenames consistent with nearby modules, for example `src/private_user_feed.php` or `src/endpoints/webhook.php`. JavaScript is built with `@wordpress/scripts`; keep source files in `js/src` and let Webpack produce `js/build`.

## Testing Guidelines

Validate changes in the local `wp-env` environment, then smoke-test the affected flows in `wp-admin` at `http://localhost:8888/wp-admin`. For UI changes, verify both PHP-rendered views and rebuilt JS assets. For integration work, exercise the specific Memberful connection, webhook, or content-protection path you changed.

## Commit & Pull Request Guidelines

Recent history favors short, imperative commit subjects such as `Fix PHP 8.3 deprecation notice` or `Add filter comment`. Keep commits focused and avoid mixing release prep with feature work. Pull requests should describe the behavior change, link the relevant issue, note any manual test coverage, and include screenshots for admin-facing UI changes. If a change affects plugin behavior or release notes, update `readme.txt`.

## Versioning

The plugin version must match in three places: the `Stable tag` field in `readme.txt`, the `Version` header in `memberful-wp.php`, and the `MEMBERFUL_VERSION` constant in the same file. `release.sh` validates this before publishing. During feature development, use `= unreleased =` as the changelog heading in `readme.txt`. When a release is cut, that heading is replaced with the actual version number and all three locations are updated together. Do not bump the version unless explicitly asked.

### Changelog updates before a version bump

Before bumping the version, review all changes since the last version bump (use `git log` against the commit that last changed the version) and ensure each significant change has a changelog entry under the `= unreleased =` heading in `readme.txt`. A significant change is a new feature, bug fix, security patch, or notable behavioral change. Each entry should be a single bullet (`* …`) with a concise one-line description matching the style of existing entries (e.g. `* Add per-plan role mappings`, `* Fix PHP 8.3 deprecation notice`). For minor changes such as small refactors, internal cleanup, or documentation-only edits, ask the user whether to include them.
