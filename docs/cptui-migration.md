# Move CPT UI registrations into the child theme

The theme now owns the six post types in the supplied PHP export. No posts,
IDs, metadata, featured image references, ACF relationship values, or terms
need to be copied or converted. They continue using the same database records.

## Preserved registration settings

| Slug | Label | Archive | Editor features | Taxonomies |
| --- | --- | --- | --- | --- |
| `artwork` | Artworks | `artwork` | Title, editor, thumbnail | Categories, tags |
| `price` | Prices | None | WordPress default title/editor, as in the PHP export | None |
| `format` | Formats | None | Title, editor, thumbnail | None |
| `exhibition` | Exhibitions | None | Title, editor, thumbnail | None |
| `collection` | Art Collections | `collections` | Title, editor, thumbnail | None |
| `book` | Books | `book` | Title, editor, thumbnail, custom fields | Tags |

The singular entry rewrite slugs remain identical to the post type names. All
rewrites preserve `with_front: true`, so a site's permalink prefix still applies.
Only `book` has `show_in_rest: true`. Prices remain excluded from search and
navigation menus. All other flags, capabilities, descriptions, and icons match
the supplied export. Label translations use the child theme's text domain.

This moves registration only. Keep ACF and its field groups, WooCommerce, Sprout
Invoices, and other plugins supporting the existing templates. The `product`,
`product_variation`, `sa_invoice`, and `sa_estimate` types remain plugin-owned.

## Dashboard cleanup after deployment

You confirmed CPT UI contains only these six post types and no custom taxonomies.

1. Before deploying, keep a WordPress database backup and copy the CPT UI
   **Tools > Post Types** export/import data into a local backup file. This is
   the reusable settings export, in addition to the PHP code already supplied.
   Record the item totals shown in each post type's admin list for comparison.
2. Deploy the updated theme through GitHub Actions, leaving CPT UI active.
   Confirm the run succeeds and **GeneratePress Child 0.2** remains the active theme.
   In `inc/post-types.php`, the `cptui_disable_cpt` filter suppresses CPT UI's
   copies of these six registrations, so the theme takes over before deactivation.
   No CPT UI function is required for the theme to work without the plugin.
3. Check all six admin lists, sample existing entries, ACF values/relationships,
   artwork and book URLs, and the `/collections/` archive (including any site
   permalink prefix). The item totals should match. Do not delete content from
   the Artworks, Prices, Formats, Exhibitions, Art Collections, or Books menus.
4. To remove CPT UI's saved definitions too, open **CPT UI > Add/Edit Post Types
   > Edit Post Types**, select each of the six definitions, and use **Delete Post
   Type**. This removes CPT UI registration settings, not content, and the theme
   continues registering each type. This step is optional; retained settings are
   harmless when CPT UI is inactive. See the
   [CPT UI author's cleanup instructions](https://docs.pluginize.com/article/resetting-cptui-settings/).
5. Open **Plugins > Installed Plugins** and **Deactivate** Custom Post Type UI.
   Reload an admin list and a sample frontend entry to verify the handoff.
6. Open **Settings > Permalinks** and click **Save Changes** once, preserving the
   selected permalink structure. Recheck entry and archive URLs afterward.
7. With verification complete, **Delete** the inactive Custom Post Type UI plugin
   from **Plugins > Installed Plugins**. The content types now live in Git.

The theme does not automatically delete CPT UI options, deactivate plugins, or
flush rewrite rules on frontend requests. Registration changes belong in
`inc/post-types.php` from now on. If you later switch themes, carry these
registrations into the replacement theme or a dedicated plugin first.

## Recovery

If a check fails, keep CPT UI installed while investigating. To return to CPT UI
registration, restore the previous theme code (removing this migration module),
reactivate CPT UI, and import the saved settings if they were cleared. Save
permalinks once again. Restoring the old plugin settings alone while this theme's
compatibility filter is active does not transfer ownership back to CPT UI.

## Validation

`tests/fixtures/cptui-export.php` preserves the supplied export as an independent
test baseline. `tests/post-types.php` compares the registered WordPress objects,
supports, taxonomy associations, entry/archive URLs, and rewrite resolution. It
also verifies that posts, metadata, and relationships survive the handoff and
that the optional CPT UI suppression filter affects only these six slugs.

CI runs the test on a disposable WordPress installation. For a disposable local
test site, use `NSITU_TEST_SITE=1 wp --skip-plugins --skip-themes eval-file
/absolute/checkout/tests/post-types.php /absolute/checkout`. The test creates and
removes fixtures and temporarily changes permalink settings; do not run it on
production.
