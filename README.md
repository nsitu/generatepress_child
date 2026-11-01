# GeneratePress Child

Custom WordPress child theme exported from the existing CapRover-hosted site.
The initial commit uses the exported theme files as a development baseline,
with embedded map credentials replaced by configuration.

## Requirements

- WordPress with the GeneratePress parent theme installed in `wp-content/themes/generatepress`.
- The site's existing plugins and configuration for its custom post types, custom fields, and commerce features.

## Theme structure

The repository root is the theme directory: `style.css`, `functions.php`, PHP
templates, and assets live directly at the root. Install this directory as
`wp-content/themes/generatepress_child` alongside the GeneratePress parent theme.

## Development

Edit the theme files directly. The exported theme has no package installation or
asset build step. PHP files can be checked with `php -l path/to/file.php`.

This repository contains theme files only. WordPress content, uploads, database
settings, and Site Editor customizations stored in the database are managed separately.

## Mapbox configuration

In the WordPress dashboard, open **Settings > General**, find **Mapbox public
access token**, enter your public token, and click **Save Changes**. Only users
with permission to manage site settings can save this option.

The legacy `leaflet-test` page reads the token from the WordPress database.
Use a public token beginning with `pk.` with the required map scopes and suitable
URL restrictions; visitors can see it in their browser. Invalid values keep the
previous token, and saving an empty field removes it.

No `wp-config.php` constant is required or read. The token persists across theme
deployments. The exported hardcoded credential is excluded from Git history.

## Google Maps configuration

Open **Settings > General > Google Maps**, enter your **Google Maps API key**,
and click **Save Changes**. This database option is shared by the art map page
and artwork location embeds and persists across theme deployments.

Enable **Maps JavaScript API** and **Maps Embed API** for the key. Restrict it
to the site's website origins and these APIs, since visitors can see the key
in their browser. Invalid values keep the previous key. Saving an empty field
hides the maps until a key is configured again.

Hardcoded Google keys, including commented-out copies, have been removed from
the current theme files. Keys in earlier Git commits remain in that history;
replace those keys in Google Cloud and save the replacement in WordPress.

## Deployment

GitHub Actions validates pull requests and deploys pushes to `main` or manual
runs on `main` to the existing CapRover WordPress container. The SSH transport
matches the WPCV plugin workflow. Configure the new repository's secrets and
variables before the first deployment; see [the setup guide](docs/deployment.md).

The installer requires `generatepress_child` to already be active and the
GeneratePress parent theme to be installed. It replaces the child theme files
without activating another theme. The themes directory must be covered by a
writable persistent CapRover service mount. A mount covering only plugins or
only the child theme directory is insufficient.

Build a theme-only ZIP locally with `python scripts/build.py`. The result is
`dist/generatepress_child.zip`; Git metadata, credentials, docs, tests, and
deployment scripts are excluded. The theme has no package installation or asset
build step beyond this ZIP packaging.

CI checks PHP 8.2 syntax, JavaScript syntax, Bash/ShellCheck, deployment preflight
scenarios, and database-backed map settings in disposable WordPress. It does not
activate the full theme against a replica of the production site's plugins/data.
