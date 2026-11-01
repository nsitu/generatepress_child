<?php
/** Original user-supplied CPT UI export, retained as an independent parity fixture. */
function nsitu_test_register_exported_post_types() {

	/**
	 * Post Type: Artworks.
	 */

	$labels = [
		"name" => esc_html__( "Artworks", "custom-post-type-ui" ),
		"singular_name" => esc_html__( "Artwork", "custom-post-type-ui" ),
	];

	$args = [
		"label" => esc_html__( "Artworks", "custom-post-type-ui" ),
		"labels" => $labels,
		"description" => "",
		"public" => true,
		"publicly_queryable" => true,
		"show_ui" => true,
		"show_in_rest" => false,
		"rest_base" => "",
		"rest_controller_class" => "WP_REST_Posts_Controller",
		"rest_namespace" => "wp/v2",
		"has_archive" => true,
		"show_in_menu" => true,
		"show_in_nav_menus" => true,
		"delete_with_user" => false,
		"exclude_from_search" => false,
		"capability_type" => "post",
		"map_meta_cap" => true,
		"hierarchical" => false,
		"can_export" => true,
		"rewrite" => [ "slug" => "artwork", "with_front" => true ],
		"query_var" => true,
		"menu_icon" => "dashicons-art",
		"supports" => [ "title", "editor", "thumbnail" ],
		"taxonomies" => [ "category", "post_tag" ],
		"show_in_graphql" => false,
	];

	register_post_type( "artwork", $args );

	/**
	 * Post Type: Prices.
	 */

	$labels = [
		"name" => esc_html__( "Prices", "custom-post-type-ui" ),
		"singular_name" => esc_html__( "Price", "custom-post-type-ui" ),
	];

	$args = [
		"label" => esc_html__( "Prices", "custom-post-type-ui" ),
		"labels" => $labels,
		"description" => "",
		"public" => true,
		"publicly_queryable" => true,
		"show_ui" => true,
		"show_in_rest" => false,
		"rest_base" => "",
		"rest_controller_class" => "WP_REST_Posts_Controller",
		"rest_namespace" => "wp/v2",
		"has_archive" => false,
		"show_in_menu" => true,
		"show_in_nav_menus" => false,
		"delete_with_user" => false,
		"exclude_from_search" => true,
		"capability_type" => "post",
		"map_meta_cap" => true,
		"hierarchical" => false,
		"can_export" => true,
		"rewrite" => [ "slug" => "price", "with_front" => true ],
		"query_var" => true,
		"menu_icon" => "dashicons-money",
		"show_in_graphql" => false,
	];

	register_post_type( "price", $args );

	/**
	 * Post Type: Formats.
	 */

	$labels = [
		"name" => esc_html__( "Formats", "custom-post-type-ui" ),
		"singular_name" => esc_html__( "Format", "custom-post-type-ui" ),
	];

	$args = [
		"label" => esc_html__( "Formats", "custom-post-type-ui" ),
		"labels" => $labels,
		"description" => "",
		"public" => true,
		"publicly_queryable" => true,
		"show_ui" => true,
		"show_in_rest" => false,
		"rest_base" => "",
		"rest_controller_class" => "WP_REST_Posts_Controller",
		"rest_namespace" => "wp/v2",
		"has_archive" => false,
		"show_in_menu" => true,
		"show_in_nav_menus" => true,
		"delete_with_user" => false,
		"exclude_from_search" => false,
		"capability_type" => "post",
		"map_meta_cap" => true,
		"hierarchical" => false,
		"can_export" => true,
		"rewrite" => [ "slug" => "format", "with_front" => true ],
		"query_var" => true,
		"menu_icon" => "dashicons-format-image",
		"supports" => [ "title", "editor", "thumbnail" ],
		"show_in_graphql" => false,
	];

	register_post_type( "format", $args );

	/**
	 * Post Type: Exhibitions.
	 */

	$labels = [
		"name" => esc_html__( "Exhibitions", "custom-post-type-ui" ),
		"singular_name" => esc_html__( "Exhibition", "custom-post-type-ui" ),
	];

	$args = [
		"label" => esc_html__( "Exhibitions", "custom-post-type-ui" ),
		"labels" => $labels,
		"description" => "",
		"public" => true,
		"publicly_queryable" => true,
		"show_ui" => true,
		"show_in_rest" => false,
		"rest_base" => "",
		"rest_controller_class" => "WP_REST_Posts_Controller",
		"rest_namespace" => "wp/v2",
		"has_archive" => false,
		"show_in_menu" => true,
		"show_in_nav_menus" => true,
		"delete_with_user" => false,
		"exclude_from_search" => false,
		"capability_type" => "post",
		"map_meta_cap" => true,
		"hierarchical" => false,
		"can_export" => true,
		"rewrite" => [ "slug" => "exhibition", "with_front" => true ],
		"query_var" => true,
		"supports" => [ "title", "editor", "thumbnail" ],
		"show_in_graphql" => false,
	];

	register_post_type( "exhibition", $args );

	/**
	 * Post Type: Art Collections.
	 */

	$labels = [
		"name" => esc_html__( "Art Collections", "custom-post-type-ui" ),
		"singular_name" => esc_html__( "Art Collection", "custom-post-type-ui" ),
	];

	$args = [
		"label" => esc_html__( "Art Collections", "custom-post-type-ui" ),
		"labels" => $labels,
		"description" => "A gathering of artworks into a collection; a body of work.",
		"public" => true,
		"publicly_queryable" => true,
		"show_ui" => true,
		"show_in_rest" => false,
		"rest_base" => "",
		"rest_controller_class" => "WP_REST_Posts_Controller",
		"rest_namespace" => "wp/v2",
		"has_archive" => "collections",
		"show_in_menu" => true,
		"show_in_nav_menus" => true,
		"delete_with_user" => false,
		"exclude_from_search" => false,
		"capability_type" => "post",
		"map_meta_cap" => true,
		"hierarchical" => false,
		"can_export" => true,
		"rewrite" => [ "slug" => "collection", "with_front" => true ],
		"query_var" => true,
		"supports" => [ "title", "editor", "thumbnail" ],
		"show_in_graphql" => false,
	];

	register_post_type( "collection", $args );

	/**
	 * Post Type: Books.
	 */

	$labels = [
		"name" => esc_html__( "Books", "custom-post-type-ui" ),
		"singular_name" => esc_html__( "Book", "custom-post-type-ui" ),
	];

	$args = [
		"label" => esc_html__( "Books", "custom-post-type-ui" ),
		"labels" => $labels,
		"description" => "",
		"public" => true,
		"publicly_queryable" => true,
		"show_ui" => true,
		"show_in_rest" => true,
		"rest_base" => "",
		"rest_controller_class" => "WP_REST_Posts_Controller",
		"rest_namespace" => "wp/v2",
		"has_archive" => true,
		"show_in_menu" => true,
		"show_in_nav_menus" => true,
		"delete_with_user" => false,
		"exclude_from_search" => false,
		"capability_type" => "post",
		"map_meta_cap" => true,
		"hierarchical" => false,
		"can_export" => true,
		"rewrite" => [ "slug" => "book", "with_front" => true ],
		"query_var" => true,
		"menu_icon" => "dashicons-book",
		"supports" => [ "title", "editor", "thumbnail", "custom-fields" ],
		"taxonomies" => [ "post_tag" ],
		"show_in_graphql" => false,
	];

	register_post_type( "book", $args );
}
