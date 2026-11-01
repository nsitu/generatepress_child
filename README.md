# GeneratePress Child

Custom WordPress child theme exported from the existing CapRover-hosted site.
The initial commit uses the exported theme files as a development baseline,
with the embedded Mapbox credential replaced by configuration.

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

The legacy `leaflet-test` page reads its browser-facing Mapbox token from a
constant in `wp-config.php`:

```php
define('NSITU_MAPBOX_PUBLIC_TOKEN', 'YOUR_MAPBOX_PUBLIC_TOKEN');
```

Use a public token with the required map scopes and suitable URL restrictions.
Without this constant, that test page receives an empty token. The exported
hardcoded credential is excluded from Git history.

## Deployment

Automated deployment is not configured yet. The intended target is the existing
WordPress container managed by CapRover, using GitHub Actions in the same manner
as the WPCV plugin repository.
