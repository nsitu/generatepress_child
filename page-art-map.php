<?php

/**
 * The template for displaying all pages.
 * @package GeneratePress
 */

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

$apiKeyGMapsJS = get_option('nsitu_google_maps_api_key', '');
if ($apiKeyGMapsJS !== '') {
	wp_enqueue_script(
		'google_js',
		'https://maps.googleapis.com/maps/api/js?' .
			'v=3.exp&' .
			'sensor=false&' .
			'key=' . rawurlencode($apiKeyGMapsJS)
	);
}

get_header(); ?>

<div id="primary" <?php generate_content_class(); ?>>
	<main id="main" <?php generate_main_class(); ?>>
		<?php
		/**
		 * generate_before_main_content hook.
		 *
		 * @since 0.1
		 */
		do_action('generate_before_main_content');

		while (have_posts()) : the_post();

			if ($apiKeyGMapsJS !== '') {
				get_template_part('content', 'art-map');
			} else {
				echo '<p>Map unavailable.</p>';
			}

			// If comments are open or we have at least one comment, load up the comment template.
			if (comments_open() || 0 != get_comments_number()) : ?>
				<div class="comments-area">
					<?php comments_template(); ?>
				</div>

		<?php endif;

		endwhile;

		/**
		 * generate_after_main_content hook.
		 *
		 * @since 0.1
		 */
		do_action('generate_after_main_content');
		?>
	</main><!-- #main -->
</div><!-- #primary -->

<?php
/**
 * generate_after_primary_content_area hook.
 *
 * @since 2.0
 */
do_action('generate_after_primary_content_area');

generate_construct_sidebars();

get_footer();
