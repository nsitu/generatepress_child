<?php
/**
 * Site content types migrated from the supplied CPT UI PHP export.
 */

if (!defined('ABSPATH')) {
  exit;
}

function nsitu_theme_post_type_definitions()
{
  $common = array(
    'description' => '',
    'public' => true,
    'publicly_queryable' => true,
    'show_ui' => true,
    'show_in_rest' => false,
    'rest_base' => '',
    'rest_controller_class' => 'WP_REST_Posts_Controller',
    'rest_namespace' => 'wp/v2',
    'has_archive' => false,
    'show_in_menu' => true,
    'show_in_nav_menus' => true,
    'delete_with_user' => false,
    'exclude_from_search' => false,
    'capability_type' => 'post',
    'map_meta_cap' => true,
    'hierarchical' => false,
    'can_export' => true,
    'query_var' => true,
    'show_in_graphql' => false,
  );

  $types = array(
    'artwork' => array(
      'labels' => array(
        'name' => esc_html__('Artworks', 'generatepress_child'),
        'singular_name' => esc_html__('Artwork', 'generatepress_child'),
      ),
      'has_archive' => true,
      'menu_icon' => 'dashicons-art',
      'supports' => array('title', 'editor', 'thumbnail'),
      'taxonomies' => array('category', 'post_tag'),
    ),
    'price' => array(
      'labels' => array(
        'name' => esc_html__('Prices', 'generatepress_child'),
        'singular_name' => esc_html__('Price', 'generatepress_child'),
      ),
      'show_in_nav_menus' => false,
      'exclude_from_search' => true,
      'menu_icon' => 'dashicons-money',
      // The export omits supports; preserve WordPress's title/editor defaults.
    ),
    'format' => array(
      'labels' => array(
        'name' => esc_html__('Formats', 'generatepress_child'),
        'singular_name' => esc_html__('Format', 'generatepress_child'),
      ),
      'menu_icon' => 'dashicons-format-image',
      'supports' => array('title', 'editor', 'thumbnail'),
    ),
    'exhibition' => array(
      'labels' => array(
        'name' => esc_html__('Exhibitions', 'generatepress_child'),
        'singular_name' => esc_html__('Exhibition', 'generatepress_child'),
      ),
      'supports' => array('title', 'editor', 'thumbnail'),
    ),
    'collection' => array(
      'labels' => array(
        'name' => esc_html__('Art Collections', 'generatepress_child'),
        'singular_name' => esc_html__('Art Collection', 'generatepress_child'),
      ),
      'description' => 'A gathering of artworks into a collection; a body of work.',
      'has_archive' => 'collections',
      'supports' => array('title', 'editor', 'thumbnail'),
    ),
    'book' => array(
      'labels' => array(
        'name' => esc_html__('Books', 'generatepress_child'),
        'singular_name' => esc_html__('Book', 'generatepress_child'),
      ),
      'show_in_rest' => true,
      'has_archive' => true,
      'menu_icon' => 'dashicons-book',
      'supports' => array('title', 'editor', 'thumbnail', 'custom-fields'),
      'taxonomies' => array('post_tag'),
    ),
  );

  foreach ($types as $slug => $args) {
    $types[$slug] = array_merge($common, array(
      'label' => $args['labels']['name'],
      'rewrite' => array('slug' => $slug, 'with_front' => true),
    ), $args);
  }

  return $types;
}

/**
 * Allow CPT UI to remain active during deployment without double registration.
 * This optional compatibility filter has no dependency on CPT UI being present.
 */
function nsitu_disable_migrated_cptui_post_types($disabled, $post_type)
{
  if ($disabled) {
    return $disabled;
  }

  $types = nsitu_theme_post_type_definitions();
  return isset($types[$post_type['name'] ?? '']);
}
add_filter('cptui_disable_cpt', 'nsitu_disable_migrated_cptui_post_types', 10, 2);

function nsitu_register_theme_post_types()
{
  foreach (nsitu_theme_post_type_definitions() as $slug => $args) {
    if (!post_type_exists($slug)) {
      register_post_type($slug, $args);
    }
  }
}
add_action('init', 'nsitu_register_theme_post_types');

function nsitu_load_child_theme_textdomain()
{
  load_child_theme_textdomain('generatepress_child', get_stylesheet_directory() . '/languages');
}
add_action('after_setup_theme', 'nsitu_load_child_theme_textdomain');
