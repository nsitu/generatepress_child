<?php
/** Run only against disposable WordPress: tests registration and data handoff. */
if (getenv('NSITU_TEST_SITE') !== '1') {
  WP_CLI::error('Run only on a disposable test site with NSITU_TEST_SITE=1.');
}
$source = $args[0] ?? '';
if (!is_file($source . '/inc/post-types.php')) {
  WP_CLI::error('Pass the absolute theme checkout path after this test file.');
}
require_once $source . '/tests/fixtures/cptui-export.php';

function nsitu_cpt_assert($condition, $message)
{
  if (!$condition) {
    throw new RuntimeException($message);
  }
}

function nsitu_cpt_snapshot($slug)
{
  $type = get_post_type_object($slug);
  nsitu_cpt_assert($type !== null, "Missing type: $slug");
  $taxonomies = get_object_taxonomies($slug);
  sort($taxonomies);
  return array(
    'registration' => get_object_vars($type),
    'supports' => get_all_post_type_supports($slug),
    'taxonomies' => $taxonomies,
  );
}

$slugs = array('artwork', 'price', 'format', 'exhibition', 'collection', 'book');
foreach ($slugs as $slug) {
  nsitu_cpt_assert(!post_type_exists($slug), "Test requires unregistered type: $slug");
}

$post_ids = array();
$term_ids = array();
$original_structure = get_option('permalink_structure');
$marker = 'nsitu-handoff-' . strtolower(wp_generate_password(8, false));

try {
  // Capture WordPress's interpretation of the exact supplied export first.
  $GLOBALS['wp_rewrite']->set_permalink_structure('/journal/%postname%/');
  nsitu_test_register_exported_post_types();
  $baseline = array();
  $permalinks = array();
  foreach ($slugs as $slug) {
    $baseline[$slug] = nsitu_cpt_snapshot($slug);
    $id = wp_insert_post(array(
      'post_type' => $slug,
      'post_status' => 'publish',
      'post_title' => "Handoff $slug",
      'post_name' => "$marker-$slug",
      'post_content' => 'Content retained during registration handoff.',
    ), true);
    nsitu_cpt_assert(!is_wp_error($id), "Could not create $slug fixture");
    $post_ids[$slug] = $id;
    update_post_meta($id, '_nsitu_handoff_marker', array('original_id' => $id));
    $permalinks[$slug] = get_permalink($id);
  }
  foreach (array('category', 'post_tag') as $taxonomy) {
    $term = wp_insert_term($marker, $taxonomy);
    nsitu_cpt_assert(!is_wp_error($term), "Could not create $taxonomy fixture");
    $term_ids[$taxonomy] = $term['term_id'];
    wp_set_object_terms($post_ids['artwork'], array($term['term_id']), $taxonomy);
  }
  wp_set_object_terms($post_ids['book'], array($term_ids['post_tag']), 'post_tag');
  update_post_meta($post_ids['artwork'], 'artworks_collections', array($post_ids['collection']));
  update_post_meta($post_ids['book'], 'authors', 'Test author');
  $archives = array();
  foreach ($slugs as $slug) {
    $archives[$slug] = get_post_type_archive_link($slug);
    unregister_post_type($slug);
  }

  // The theme operates without loading CPT UI or any other site plugin.
  require_once $source . '/inc/post-types.php';
  nsitu_register_theme_post_types();
  nsitu_cpt_assert(has_action('init', 'nsitu_register_theme_post_types') !== false, 'Missing init hook');
  foreach ($slugs as $slug) {
    nsitu_cpt_assert(nsitu_cpt_snapshot($slug) == $baseline[$slug], "$slug: registration differs from export");
    $id = $post_ids[$slug];
    nsitu_cpt_assert(get_post($id)->post_type === $slug, "$slug: post type changed");
    nsitu_cpt_assert(get_post($id)->post_content === 'Content retained during registration handoff.', "$slug: content changed");
    nsitu_cpt_assert(get_post_meta($id, '_nsitu_handoff_marker', true) === array('original_id' => $id), "$slug: metadata changed");
    nsitu_cpt_assert(get_permalink($id) === $permalinks[$slug], "$slug: permalink changed");
    nsitu_cpt_assert(get_post_type_archive_link($slug) === $archives[$slug], "$slug: archive changed");
    nsitu_cpt_assert(apply_filters('cptui_disable_cpt', false, array('name' => $slug)) === true, "$slug: CPT UI bridge did not suppress duplicate registration");
  }
  nsitu_cpt_assert(apply_filters('cptui_disable_cpt', false, array('name' => 'unrelated_type')) === false, 'Bridge suppresses unrelated types');
  nsitu_cpt_assert(apply_filters('cptui_disable_cpt', true, array('name' => 'unrelated_type')) === true, 'Bridge overrides an existing disabled flag');
  nsitu_cpt_assert(get_post_meta($post_ids['artwork'], 'artworks_collections', true) === array($post_ids['collection']), 'ACF relationship IDs changed');
  nsitu_cpt_assert(get_post_meta($post_ids['book'], 'authors', true) === 'Test author', 'Book custom field changed');
  foreach ($term_ids as $taxonomy => $term_id) {
    nsitu_cpt_assert(has_term($term_id, $taxonomy, $post_ids['artwork']), "Artwork $taxonomy relationship lost");
  }
  nsitu_cpt_assert(has_term($term_ids['post_tag'], 'post_tag', $post_ids['book']), 'Book tag relationship lost');

  // Resolve unchanged entry and archive URLs through real WordPress rewrite rules.
  flush_rewrite_rules(false);
  foreach ($slugs as $slug) {
    nsitu_cpt_assert(url_to_postid($permalinks[$slug]) === $post_ids[$slug], "$slug: URL no longer resolves");
  }
  nsitu_cpt_assert(strpos(get_post_type_archive_link('collection'), '/journal/collections/') !== false, 'Collections archive or with_front changed');
  $rules = $GLOBALS['wp_rewrite']->wp_rewrite_rules();
  nsitu_cpt_assert(isset($rules['journal/collections/?$']), 'Collections archive rewrite missing');

  // Registration is idempotent; existing objects are not registered twice.
  $original_object = get_post_type_object('artwork');
  nsitu_register_theme_post_types();
  nsitu_cpt_assert(get_post_type_object('artwork') === $original_object, 'Repeated registration replaced an existing type');
} finally {
  foreach ($post_ids as $id) {
    wp_delete_post($id, true);
  }
  foreach ($term_ids as $taxonomy => $term_id) {
    wp_delete_term($term_id, $taxonomy);
  }
  foreach ($slugs as $slug) {
    if (post_type_exists($slug)) {
      unregister_post_type($slug);
    }
  }
  $GLOBALS['wp_rewrite']->set_permalink_structure($original_structure);
  flush_rewrite_rules(false);
}

WP_CLI::success('Six post types match the CPT UI export; IDs, content, metadata, relationships, URLs, archives, and optional CPT UI handoff are preserved.');
