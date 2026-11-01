<?php

/**
 * The template for displaying single books.
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
		//do_action( 'generate_before_content' );

		if (has_post_thumbnail()) {
			// it would be cool to make a parallax style display for really tall images
			// but mostly only on the desktop.
			$theImg  = wp_get_attachment_image_src(get_post_thumbnail_id(), 'extra-large');
			$theImgSrc = $theImg[0];

			echo '<style>' .
				' .book-featured-image{ ' .
				'height: 50vh; ' .
				'margin: -40px -40px 20px -40px; ' .
				'overflow: hidden; ' .
				'} ' .
				' .book-featured-image .blur { ' .
				'z-index: 1;' .
				'position: relative; ' .
				'top: -5%;' .
				'left: -5%;' .
				'width: 110%; height: 60vh;' .
				'background-size: cover; ' .
				'background-image: url(' . $theImgSrc . '); ' .
				'filter: blur(8px); ' .
				'-webkit-filter: blur(8px); ' .
				'}' .
				' .book-featured-image .sharp { ' .
				'z-index: 2;' .
				'width: 100%; height: 100%;' .
				'position: relative; ' .
				'top: -60vh; ' .
				'left: 0; ' .
				'background-image: url(' . $theImgSrc . '); ' .
				'background-repeat: no-repeat; ' .
				'background-size: contain; ' .
				'background-position: center center;' .

				'}' .
				' @media (max-width: 768px) { ' .
				'.book-featured-image{ ' .
				'margin: -30px -30px 20px -30px; ' .
				'} ' .
				'} ' .
				'</style>';


			echo '<div class="' . esc_attr($class) . ' book-featured-image">' .
				'<div class="blur"></div>' .
				'<div class="sharp"></div>' .
				'</div>';
		}


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

			//===============================
			// list art collections that this artwork belongs to
			$theCollections = get_field('artworks_collections');
			$collectionList = array();
			foreach ($theCollections as $someCollection) {
				$theCollectionTitle = get_the_title($someCollection->ID);
				$theCollectionLink = get_the_permalink($someCollection->ID);
				$collectionList[] =
					'<i style="color:rgba(0,0,0,.3); padding-left:20px;" class="fas fa-layer-group"></i> ' .
					'<a style="text-transform: uppercase; color:rgba(0,0,0,.3); font-weight: bold; font-size: 0.7em;" href="' . $theCollectionLink . '">' . $theCollectionTitle . '</a>';
			}
			$collectionText = (count($collectionList) > 0) ? implode('', $collectionList) : '';

			$theAuthors = get_field('authors');

			echo
			'<h3>' .
				'<i style="color:#ff6600;" class="fas fa-feather-alt"></i> ' .
				'<span style="color:#ff6600; font-weight: bold; font-size: 0.7em;">AUTHOR: ' . $theAuthors . '</span> ' .
				'</h3>';

			echo '<h3>' .
				'<i style="color:rgba(0,0,0,.5);" class="fas fa-calendar"></i> ' .

				'<span style="color:rgba(0,0,0,.5); font-weight: bold; font-size: 0.7em;"> READ by HAROLD in ' .
				strtoupper(get_the_date('F Y')) .
				'</span>' .
				$collectionText .
				'</h3>';

			echo '<h3><i>Harold\'s Commentary/Review:</i></h3>';
			the_content();

			$theAmazonLink = get_field('amazon_link');

			echo '<h3>' .
				'<i style="color:#ff6600;" class="fas fa-search"></i>' .
				'<span style="color:rgba(0,0,0,.5); font-weight: bold; font-size: 0.7em;"><a href="' . $theAmazonLink . ' target="_blank"> FIND a COPY</a></span> ' .
				'</h3>' .
				'<p>Find a copy of ' .
				'<a href="' . $theAmazonLink . ' target="_blank">' .
				get_the_title() .
				'</a> ' .
				'on ' .
				'<a href="' . $theAmazonLink . ' target="_blank">' .
				'<i style="color:#ff6600;" class="fab fa-amazon"></i> Amazon' .
				'</a>.' .
				'</p>';



			global $theOrdinals;
			$theOrdinals = array('first', 'second', 'third', 'fourth', 'fifth', 'sixth', 'seventh', 'eighth', 'ninth', 'tenth', 'eleventh');
			global $currDate;
			$currDate = '';
			function navigationThumb($bookID)
			{
				global $theOrdinals;
				global $currDate;
				$post_thumbnail_id = get_post_thumbnail_id($bookID);
				$theImageUrl = wp_get_attachment_image_url($post_thumbnail_id, 'thumbnail');
				$theLink = get_post_permalink($bookID);
				$theTitle = get_the_title($bookID);
				$theFullDate = get_the_date('Y M d', $bookID);
				$theClass = array_shift($theOrdinals);
				$bookDate = strtoupper(get_the_date('M Y', $bookID));
				$theDate = ($currDate == $bookDate) ?
					'<span class="continuousDate" ></span>' :
					'<span class="regularDate">' . $bookDate . '</span>';
				if ($bookID == get_the_id()) {
					$theDate = 	'<span class="thisDate" >' . $bookDate . '</span>';
				}
				$currDate = $bookDate;

				return
					'<div class="' . $theClass . '">' .
					'<a href="' . $theLink . '" title="' . $bookID . ' ' . $theFullDate . '" >' .
					'<img src="' . $theImageUrl . '" />' .
					'</a>' .
					'<br/>' .	$theDate .
					'</div>';
			}

			global $wpdb;
			$currentBookDate = get_the_date('Y-m-d H:i:s');

			//	was trying to find a way to sort these by date
			// even when the date is the same.
			// in theory you could use ID, but if you are limiting it to 5
			// you need to also select the closest 5 IDs.
			$newerBookSQL = "SELECT ID FROM `wp_posts`
				WHERE `post_type` = 'book'
				AND `post_status` = 'publish'
				AND ( `post_date` > '" . $currentBookDate . "' OR
						( `post_date` = '" . $currentBookDate . "' AND `ID` > " . get_the_id() . " ) )
				AND `ID` != '" . get_the_id() . "'
				ORDER BY ABS( DATEDIFF( `post_date`, '" . $currentBookDate . "' ) ),
								 ABS(cast(`ID` as signed) - " . get_the_id() . ") ASC
				LIMIT 5;";

			$olderBookSQL = "SELECT ID FROM `wp_posts`
				WHERE `post_type` = 'book'
				AND `post_status` = 'publish'
				AND ( `post_date` < '" . $currentBookDate . "' OR
						( `post_date` = '" . $currentBookDate . "' AND `ID` < " . get_the_id() . " ) )
				AND `ID` != '" . get_the_id() . "'
				ORDER BY ABS( DATEDIFF( `post_date`, '" . $currentBookDate . "' ) ),
								 ABS(cast(`ID` as signed) - " . get_the_id() . ") ASC
				LIMIT 5;";

			//echo $olderBookSQL;
			$newerBookResult =  $wpdb->get_results($newerBookSQL);
			$olderBookResult =  $wpdb->get_results($olderBookSQL);

			$newerBookIDs = array_map(function ($o) {
				return $o->ID;
			}, $newerBookResult);
			$olderBookIDs = array_reverse(array_map(function ($o) {
				return $o->ID;
			}, $olderBookResult));

			$olderBooks = array();
			foreach ($olderBookIDs as $someBookID) {
				array_push($olderBooks, navigationThumb($someBookID));
			}
			$thisBook = navigationThumb(get_the_id());
			$newerBooks = array();
			foreach ($newerBookIDs as $someBookID) {
				array_push($newerBooks, navigationThumb($someBookID));
			}


			echo '<h3>' .
				'<i style="color:#ff6600;" class="fas fa-book-reader"></i> ' .
				'<span style="color:#ff6600; font-weight: bold; font-size: 0.7em;">HAROLD\'S READING HISTORY</span> ' .
				'</h3>' .
				'<div id="bookChronology">' .
				implode('', $olderBooks) .
				$thisBook .
				implode('', $newerBooks) .
				'</div>' .
				'<span style="clear: both;"></span>' .

				'<style>' .
				' #bookChronology { height: 160px;} ' .
				' #bookChronology div { display: block; float: left; width: 9%; text-align: center; } ' .
				' #bookChronology img { width: 100%;	}' .
				' #bookChronology span { ' .
				' color: rgba(0,0,0,.3); ' .
				'} ' .
				' #bookChronology span.thisDate { ' .
				'display: block; background: #ff6600; color: #fff; margin: 5px; ' .
				' font-size: 0.6em; ' .
				' font-weight: bold; ' .
				'line-height: 1.8em;' .
				'height: 18px;' .
				'} ' .
				' #bookChronology span.continuousDate { ' .
				'display: block; border-bottom: 1px solid #bbb; margin: 0px 15px; ' .
				'height: 16px;' .
				'} ' .
				' #bookChronology span.regularDate { ' .
				' font-weight: bold; ' .
				' font-size: 0.7em; ' .
				'} ' .
				' @media (max-width: 768px) {
						#bookChronology div.first { display: none; }
						#bookChronology div.second { display: none; }
						#bookChronology div.third { display: none; }
						#bookChronology div.ninth { display: none; }
						#bookChronology div.tenth { display: none; }
						#bookChronology div.eleventh { display: none; }
						#bookChronology div{ width: 19.9%; }
					} ' .
				'</style>';


			echo '<h3>' .
				'<i style="color:rgba(0,0,0,.5);" class="fas fa-question-circle"></i> ' .
				'<span style="color:rgba(0,0,0,.5); font-weight: bold; font-size: 0.7em;"> WHAT is THIS? ' .
				'</span>' .
				'</h3>' .
				'<p>This digital bookshelf is, loosely defined, a collection of books that I (Harold Sikkema) have read over the years of my life. I began this list because I wanted to get a sense of my reading over time. I suppose the chronology also gives a picture of my evolving interests and concerns. I cannot claim that this is a complete list; there are lots of things I\'ve read that are not listed here. I will add to my digital bookshelf as time allows. See also: <a href="/a-compendium-of-books/">A Compendium of Books</a>, an earlier blog post reflecting on this effort.</p>';





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