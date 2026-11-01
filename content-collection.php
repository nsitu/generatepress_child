<?php

/**
 * The template for displaying single posts.
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
		?>

		<header class="entry-header">
			<?php
			/**
			 * generate_before_entry_title hook.
			 *
			 * @since 0.1
			 */
			do_action('generate_before_entry_title');



			if (generate_show_title()) {
				the_title('<h1 class="entry-title" itemprop="headline">', '</h1>');
			}



			/**
			 * generate_after_entry_title hook.
			 *
			 * @since 0.1
			 *
			 * @hooked generate_post_meta - 10
			 */
			do_action('generate_after_entry_title');


			echo '<h3>';
			echo '<i style="color:rgba(0,0,0,.3); padding-left:0px;" class="fas fa-layer-group"></i> ' .
				'<a style="text-transform: uppercase; color:rgba(0,0,0,.3); font-weight: bold; font-size: 0.7em;" href="' . get_site_url() . '/collections">Art Collection</a>';

			echo '<i style="color:rgba(0,0,0,.3); padding-left:20px;" class="fas fa-calendar"></i> ' .
				'<span style="color:rgba(0,0,0,.3); font-weight: bold; font-size: 0.7em;">' .
				strtoupper(get_the_date('M Y')) .
				'</span> ';

			$theArtworks = get_field('artworks_collections');
			if ($theArtworks) {
				echo '<i style="color:rgba(0,0,0,.3); padding-left:20px;" class="fas fa-image"></i> ' .
					'<span style="text-transform: uppercase; color:rgba(0,0,0,.3); font-weight: bold; font-size: 0.7em;" href="' . get_site_url() . '/collections">' . count($theArtworks) . ' Artworks</span>';
			}

			$theBlogPosts = get_field('posts_collections');
			js_dump($theBlogPosts);
			if ($theBlogPosts) {
				$linkText = (count($theBlogPosts) > 1) ? count($theBlogPosts) . " Blog Posts" : "1 Blog Post";
				echo '<i style="color:rgba(0,0,0,.3); padding-left:20px;" class="fas fa-image"></i> ' .
					'<a style="text-transform: uppercase; color:rgba(0,0,0,.3); font-weight: bold; font-size: 0.7em;" href="#TheBlogPosts">' . $linkText . '</a>';
			}


			echo '</h3>';

			?>
		</header><!-- .entry-header -->

		<?php
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
			<?php
			the_content();


			$theArtworks = get_field('artworks_collections');
			$theDisplayMode = get_field('display_mode');

			$theContent = '';
			if ($theArtworks) {
				if ($theDisplayMode == 'Essential Grid') {
					$artWorkIds = array();
					foreach ($theArtworks as $someArtwork) {
						$artWorkIds[] = $someArtwork->ID;
					}
					echo do_shortcode('[ess_grid alias="artworks" posts="' . implode(',', $artWorkIds) . '"]');
				} else {
					foreach ($theArtworks as $someArtwork) {
						$theThumb = get_the_post_thumbnail($someArtwork, array(2048, 2048));
						$theTitle = get_the_title($someArtwork);

						$theContent .=
							'<div style="margin-bottom: 20px;">' .
							'<a href="' . get_the_permalink($someArtwork) . '">' . $theThumb . '</a><br/>' .
							'<h5 style="padding: 10px; text-align: center;">' .
							'<i class="fas fa-caret-up"></i> ' .
							'<a style="color: #666;" href="' . get_the_permalink($someArtwork) . '">' . $theTitle . '</a>' .
							'</h5>' .
							'</div>';
					}
				} // end if display mode is not essential grid
			} // end if there are $artworksSQL

			$theBlogPosts = get_field('posts_collections');
			if ($theBlogPosts) {
				$postLinks = array();
				foreach ($theBlogPosts as $somePost) {
					$post_thumbnail_id = get_post_thumbnail_id($somePost->ID);
					$theImageUrl = wp_get_attachment_image_url($post_thumbnail_id, 'thumbnail');
					$theLink = get_post_permalink($somePost->ID);
					$theTitle = get_the_title($somePost->ID);
					$theDate = get_the_date('Y-m-d', $somePost->ID);
					$postLinks[] =
						'<li><a href="' . $theLink . '" title="' . addslashes($theTitle) . '" class="' . $theClass . '">' .
						'<h3 style="background-image: url(' . $theImageUrl . ');">' . $theTitle . '</h3>' .
						'</a></li>';
				}
				echo
				'<style>' .
					' ul#collectionBlogPosts{ margin: 0px; }' .
					' ul#collectionBlogPosts h3 { ' .
					' padding-left: 85px; ' .
					'background-repeat: no-repeat; ' .
					'background-size: contain; ' .
					'line-height: 75px;' .
					'min-height: 75px; ' .
					'}' .
					'</style>' .
					'<h2 id="TheBlogPosts">More about ' . get_the_title() . '</h2>' .
					'<h6>Related posts from the <i>Selective Memory</i> blog:</h6>' .
					'<ul id="collectionBlogPosts" style="list-style:none;">' .
					implode('', $postLinks) .
					'</ul>';
			}


			/*
$my_posts = get_posts(array('tag' => get_query_var('tag')));
$my_post_ids = array();

foreach($my_posts as $post) {
   array_push( $my_post_ids, $post -> ID );
}*/



			/* if we are using essential grid instead of the default mode, then yeah. */




			echo  $theContent;

			wp_link_pages(array(
				'before' => '<div class="page-links">' . __('Pages:', 'generatepress'),
				'after'  => '</div>',
			));
			?>
		</div><!-- .entry-content -->

		<?php
		/**
		 * generate_after_entry_content hook.
		 *
		 * @since 0.1
		 *
		 * @hooked generate_footer_meta - 10
		 */
		do_action('generate_after_entry_content');

		/**
		 * generate_after_content hook.
		 *
		 * @since 0.1
		 */
		do_action('generate_after_content');
		?>
	</div><!-- .inside-article -->
</article><!-- #post-## -->