<?php

/**
 * The template used for displaying page content in page.php
 *
 * @package GeneratePress
 */

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}
?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?> <?php generate_do_microdata('article'); ?>>
	<div class="inside-article">
		<?php
		/**
		 * generate_before_content hook.
		 *
		 * @since 0.1
		 *
		 * @hooked generate_featured_page_header_inside_single - 10
		 */
		do_action('generate_before_content');

		if (generate_show_title()) : ?>

			<header class="entry-header">
				<?php the_title('<h1 class="entry-title" itemprop="headline">', '</h1>'); ?>
			</header><!-- .entry-header -->

		<?php endif;

		/**
		 * generate_after_entry_header hook.
		 *
		 * @since 0.1
		 *
		 * @hooked generate_post_image - 10
		 */
		do_action('generate_after_entry_header');
		?>

		<div class="entry-content" itemprop="text">


			<style>
				ul#allArt {
					list-style-type: none;
					display: flex;
					flex-wrap: wrap;
					margin: 0px;
					padding: 3px;
				}

				#allArt li {
					margin: 3px;
				}

				#allArt a {
					display: block;
					width: 75px;
					text-align: center;
					font-size: 8px;
					line-height: 8px;
				}

				#allArt a span {
					vertical-align: middle;
					height: 50px;
				}

				body .grid-container {
					max-width: initial;
				}
			</style>

			<?php

			// get location information from artworks.
			//TODO make sure they are actually published.

			$artworksSQL = "SELECT wp_posts.ID, wp_posts.post_title
FROM wp_posts
WHERE wp_posts.post_type   = 'artwork'
AND   wp_posts.post_status = 'publish'
ORDER BY wp_posts.post_date
DESC";

			$artworksResult =  $wpdb->get_results($artworksSQL);

			$theArtworks = array();
			foreach ($artworksResult as $someArt) {
				$thumbUrl = wp_get_attachment_image_url(get_post_thumbnail_id($someArt->ID), 'thumbnail');
				$artUrl = get_the_permalink($someArt->ID);
				$artID = $someArt->ID;
				$artTitle = $someArt->post_title;
				$theArtworks[] = '<li><a href="' . $artUrl . '">' .
					'<img style="width: 75px; height: 75px;" src="' . $thumbUrl . '">' .
					'<br/><span>' . $artTitle . '</span>' .
					'</a></li>';
			}

			echo '<ul id="allArt">' . implode('', $theArtworks) . '</ul>';




			the_content();

			wp_link_pages(array(
				'before' => '<div class="page-links">' . __('Pages:', 'generatepress'),
				'after'  => '</div>',
			));
			?>
		</div><!-- .entry-content -->

		<?php
		/**
		 * generate_after_content hook.
		 *
		 * @since 0.1
		 */
		do_action('generate_after_content');
		?>
	</div><!-- .inside-article -->
</article><!-- #post-## -->