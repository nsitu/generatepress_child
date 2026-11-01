<?php

/**
 * The template for displaying single posts.
 *
 * @package GeneratePress
 */


// THIS CODE LETS YOU MAKE ARTWORKS FROM IMAGES
// CHANGE THE QUERY AS NEEDED
/*
 $theImages = get_posts( array(
            'post_type' => 'attachment',
            'posts_per_page' => -1,
						'orderby'          => 'date',
						'date_query' => array(
				        'after' => date('Y-m-d', strtotime('-3 days'))
				    )
        ) );

foreach ($theImages as $someImage){

$postId = wp_insert_post(array (
	'post_type' => 'artwork',
	'post_title' => $someImage->post_title,
	'post_content' => $someImage->post_title,
	'post_status' => 'publish',
	'comment_status' => 'closed',   // if you prefer
	'ping_status' => 'closed',      // if you prefer
));

set_post_thumbnail( $postId,  $someImage->ID );

}
*/




///========================================
// Calculate Image Proportions
$image_data = wp_get_attachment_image_src(get_post_thumbnail_id(), "full");
$imgWidth = $image_data[1];
$imgHeight = $image_data[2];
$imgOrientation = 'square';
if ($imgWidth > $imgHeight) {
	$imgOrientation = 'horizontal';
}
if ($imgWidth < $imgHeight) {
	$imgOrientation = 'vertical';
}
// wiggle room for square-ish images.
if (abs($imgWidth - $imgHeight) <= 2) {
	$imgOrientation = 'square';
}
$longSide = max($imgWidth, $imgHeight);
$shortSide = min($imgWidth, $imgHeight);
$imgProportion = $longSide / $shortSide;
list($longProp, $shortProp) = simplifyFraction($longSide, $shortSide);
// Get the closest matching proportion out of several standard ones I use on whitewall
$wwProp = wwNearestProportion($longProp, $shortProp);
$wwLong = $wwProp['longside'];
$wwShort = $wwProp['shortside'];

// End Calculate Image Proportions
//========================================


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

		// Determine if we should use two-column layout for tall images
		$useTwoColumnLayout = ($imgOrientation == "vertical" && $imgProportion >= 2);

		if ($useTwoColumnLayout && has_post_thumbnail()) {
			// Two-column layout for tall images (proportion >= 2:1)
			echo '<style>
				.artwork-two-column { 
					display: flex; 
					gap: 30px; 
					margin: -40px -40px 20px -40px;
				}
				.artwork-two-column .artwork-image-column { 
					flex: 0 0 auto; 
					max-width: 50%;
				}
				.artwork-two-column .artwork-image-column img { 
					width: 100%; 
					height: auto; 
					cursor: pointer;
				}
				.artwork-two-column .artwork-content-column { 
					flex: 1; 
					padding-top: 40px;
					padding-right: 40px;
				}
				.artwork-fullscreen-btn {
					display: inline-block;
					background: #ff6600;
					color: #fff;
					padding: 10px 20px;
					margin: 15px 0;
					border: none;
					cursor: pointer;
					font-weight: bold;
					font-size: 0.9em;
				}
				.artwork-fullscreen-btn:hover {
					background: #e55a00;
				}
				.artwork-fullscreen-btn i {
					margin-right: 8px;
				}
				@media (max-width: 768px) { 
					.artwork-two-column { 
						flex-direction: column; 
						margin: -30px -30px 20px -30px;
					}
					.artwork-two-column .artwork-image-column { 
						max-width: 100%; 
					}
					.artwork-two-column .artwork-content-column { 
						padding: 0 30px;
					}
				}
				.artwork-full-width-section {
					clear: both;
					padding-top: 20px;
				}
			</style>';
			echo '<div class="artwork-two-column">';
			echo '<div class="artwork-image-column" onclick="viewer.setFullScreen(true);">';
			the_post_thumbnail(
				'extra-large',
				array(
					'itemprop' => 'image',
					'alt' => the_title_attribute('echo=0'),
				)
			);
			echo '</div>';
			echo '<div class="artwork-content-column">';
		} elseif (has_post_thumbnail()) {
			// Standard layout for non-tall images
			echo '<style>' .
				' .artwork-featured-image{ margin: -40px -40px 20px -40px; } ' .
				' @media (max-width: 768px) { .artwork-featured-image{ margin: -30px -30px 20px -30px; } } ' .
				'</style>';
			echo '<div class="artwork-featured-image" onclick="viewer.setFullScreen(true);" style="cursor: pointer;">';
			the_post_thumbnail(
				'extra-large',
				array(
					'itemprop' => 'image',
					'alt' => the_title_attribute('echo=0'),
				)
			);
			echo '</div>';
		}


		?>

		<header class="entry-header">
			<?php
			/**
			 * generate_before_entry_title hook.
			 *
			 * @since 0.1
			 */

			// SEE ALSO: /plugins/wp-seadragon/wp-seadragon.php
			echo do_shortcode('[displayOSD]'); // open seadragon.

			do_action('generate_before_entry_title');

			if (generate_show_title()) {
				the_title('<h1 class="entry-title" itemprop="headline">', '</h1>');
			}

			// Fullscreen button for two-column layout
			if ($useTwoColumnLayout) {
				echo '<button class="artwork-fullscreen-btn" onclick="viewer.setFullScreen(true);">' .
					'<i class="fas fa-expand"></i> View Fullscreen' .
					'</button>';
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


			echo '<h3>' .
				'<i style="color:rgba(0,0,0,.3);" class="fas fa-calendar"></i> ' .

				'<span style="color:rgba(0,0,0,.3); font-weight: bold; font-size: 0.7em;">' .
				strtoupper(get_the_date('M j, Y')) .
				'</span> ' .
				$collectionText .
				'</h3>';

			the_content();

			// VUE.js
			// echo do_shortcode('[displayWW]'); // whitewall

			// NOTE:
			// I have been playing with VUE using a draft plugin called wp-whitewall
			// It's commented out because it's in development
			// would be cool to get back to this as a jquery alternative.




			///========================================
			// Check for related artworks and list them
			$theRelatedArtworks = get_field('related_artworks');
			$relatedArtworksList = array();
			foreach ($theRelatedArtworks as $someArtwork) {
				if ($someArtwork->ID != get_the_id()) {
					$post_thumbnail_id = get_post_thumbnail_id($someArtwork->ID);
					$theImageUrl = wp_get_attachment_image_url($post_thumbnail_id, 'thumbnail');
					$theLink = get_post_permalink($someArtwork->ID);
					$theTitle = get_the_title($someArtwork->ID);

					$relatedArtworksList[] =
						'<li style=" background-image: url(' . $theImageUrl . ') ">' .
						'<a href="' . $theLink . '" >' .
						$theTitle .
						'</a>' .
						'</li>';
				}
			}
			if (count($relatedArtworksList) > 0) {
				echo
				'<h3>' .
					'<i style="color:#ff6600;" class="fas fa-images"></i> ' .
					'<span style="color:#ff6600; font-weight: bold; font-size: 0.7em;">RELATED ARTWORK</span> ' .
					'</h3>' .
					'<ul class="relatedArtworks">' .
					implode($relatedArtworksList) .
					'</ul>' .
					'<style> ' .
					'ul.relatedArtworks{ list-style-type: none; margin-left: 0px; } ' .
					'ul.relatedArtworks li{ ' .
					'height: 50px; ' .
					'line-height: 50px; ' .
					'background-size: contain; ' .
					'background-repeat: no-repeat; ' .
					'background-position: center left; ' .
					'padding-left: 60px; ' .
					'} ' .
					'ul.relatedArtworks li a{
											display: inline-block;
											vertical-align: middle;
											line-height: normal;
										}' .
					'</style>';
			}
			// END of related artworks  list
			//========================================

			// Close two-column layout if active (before full-width sections)
			if ($useTwoColumnLayout) {
				echo '</div><!-- .entry-content (inside column) -->';
				echo '</div><!-- .artwork-content-column -->';
				echo '</div><!-- .artwork-two-column -->';
				echo '<div class="artwork-full-width-section">';
			}



			global $theOrdinals;
			$theOrdinals = array('first', 'second', 'third', 'fourth', 'fifth', 'sixth', 'seventh', 'eighth', 'ninth', 'tenth', 'eleventh');
			global $currDate;
			$currDate = '';
			function navigationThumb($artworkID)
			{
				global $theOrdinals;
				global $currDate;
				$post_thumbnail_id = get_post_thumbnail_id($artworkID);
				$theImageUrl = wp_get_attachment_image_url($post_thumbnail_id, 'thumbnail');
				$theLink = get_post_permalink($artworkID);
				$theTitle = get_the_title($artworkID);
				$theFullDate = get_the_date('Y M d', $artworkID);
				$theClass = array_shift($theOrdinals);
				$artDate = strtoupper(get_the_date('M Y', $artworkID));
				$theDate = ($currDate == $artDate) ?
					'<span class="continuousDate" ></span>' :
					'<span class="regularDate">' . $artDate . '</span>';
				if ($artworkID == get_the_id()) {
					$theDate = 	'<span class="thisDate" >' .
						strtoupper(get_the_date('M j Y', $artworkID)) .
						'</span>';
				}
				$currDate = $artDate;

				return
					'<div class="' . $theClass . '">' .
					'<a href="' . $theLink . '" title="' . $artworkID . ' ' . $theFullDate . '" >' .
					'<img src="' . $theImageUrl . '" />' .
					'</a>' .
					'<br/>' .	$theDate .
					'</div>';
			}

			global $wpdb;
			$currentArtworkDate = get_the_date('Y-m-d H:i:s');

			//	was trying to find a way to sort these by date
			// even when the date is the same.
			// in theory you could use ID, but if you are limiting it to 5
			// you need to also select the closest 5 IDs.
			$newerArtSQL = "SELECT ID FROM `wp_posts`
				WHERE `post_type` = 'artwork'
				AND `post_status` = 'publish'
				AND ( `post_date` > '" . $currentArtworkDate . "' OR
						( `post_date` = '" . $currentArtworkDate . "' AND `ID` > " . get_the_id() . " ) )
				AND `ID` != '" . get_the_id() . "'
				ORDER BY ABS( DATEDIFF( `post_date`, '" . $currentArtworkDate . "' ) ),
								 ABS(cast(`ID` as signed) - " . get_the_id() . ") ASC
				LIMIT 5;";

			$olderArtSQL = "SELECT ID FROM `wp_posts`
				WHERE `post_type` = 'artwork'
				AND `post_status` = 'publish'
				AND ( `post_date` < '" . $currentArtworkDate . "' OR
						( `post_date` = '" . $currentArtworkDate . "' AND `ID` < " . get_the_id() . " ) )
				AND `ID` != '" . get_the_id() . "'
				ORDER BY ABS( DATEDIFF( `post_date`, '" . $currentArtworkDate . "' ) ),
								 ABS(cast(`ID` as signed) - " . get_the_id() . ") ASC
				LIMIT 5;";

			//echo $olderArtSQL;
			$newerArtResult =  $wpdb->get_results($newerArtSQL);
			$olderArtResult =  $wpdb->get_results($olderArtSQL);

			$newerArtIDs = array_map(function ($o) {
				return $o->ID;
			}, $newerArtResult);
			$olderArtIDs = array_reverse(array_map(function ($o) {
				return $o->ID;
			}, $olderArtResult));

			$olderWorks = array();
			foreach ($olderArtIDs as $someArtID) {
				array_push($olderWorks, navigationThumb($someArtID));
			}
			$thisArtwork = navigationThumb(get_the_id());
			$newerWorks = array();
			foreach ($newerArtIDs as $someArtID) {
				array_push($newerWorks, navigationThumb($someArtID));
			}


			echo '<h3>' .
				'<i style="color:#ff6600;" class="fas fa-book-reader"></i> ' .
				'<span style="color:#ff6600; font-weight: bold; font-size: 0.7em;">EXPLORE</span> ' .
				'</h3>' .
				'<div id="artChronology">' .
				implode('', $olderWorks) .
				$thisArtwork .
				implode('', $newerWorks) .
				'</div>' .
				'<span style="clear: both;"></span>' .

				'<style>' .
				' #artChronology { height: 160px;} ' .
				' #artChronology div { display: block; float: left; width: 9%; text-align: center; } ' .
				' #artChronology img { width: 100%;	}' .
				' #artChronology span { ' .
				' color: rgba(0,0,0,.3); ' .
				'} ' .
				' #artChronology span.thisDate { ' .
				'display: block; background: #ff6600; color: #fff; margin: 5px; ' .
				' font-size: 0.6em; ' .
				' font-weight: bold; ' .
				'line-height: 1.8em;' .
				'height: 18px;' .
				'} ' .
				' #artChronology span.continuousDate { ' .
				'display: block; border-bottom: 1px solid #bbb; margin: 0px 15px; ' .
				'height: 16px;' .
				'} ' .
				' #artChronology span.regularDate { ' .
				' font-weight: bold; ' .
				' font-size: 0.7em; ' .
				'} ' .
				' @media (max-width: 768px) {
						#artChronology div.first { display: none; }
						#artChronology div.second { display: none; }
						#artChronology div.third { display: none; }
						#artChronology div.ninth { display: none; }
						#artChronology div.tenth { display: none; }
						#artChronology div.eleventh { display: none; }
						#artChronology div{ width: 19.9%; }
					} ' .
				'</style>';


			// you could also get nearby artworks for each collection for browsing purposes.
			/*
								$theIDs = get_field('artworks_collections', $someCollection->ID, false);


								$theWorks = array();
								foreach ($artChronology as $someArtwork){
										$post_thumbnail_id = get_post_thumbnail_id( $someArtwork->ID );
										$theImageUrl = wp_get_attachment_image_url( $post_thumbnail_id, 'thumbnail');
										$theLink = get_post_permalink(  $someArtwork->ID );
										$theTitle = get_the_title(   $someArtwork->ID  );
										$theWorks[] =

												'<a href="'.$theLink.'" >'.
													'<img src="'.$theImageUrl.'" />'.
												'</a>';

								}*/

			// END list art collections
			//====================






			/*
      $theProportions = $wpdb->get_results(
        "SELECT * FROM $wpdb->postmeta ".
        "WHERE meta_key = 'proportion' AND meta_value = '$imgProportion' ",
        ARRAY_A);*/

			// find prices for formats with matching proportions
			// match within a 10 percent variance.
			/*
        global $wpdb;
        $priceIds = array_map(
          function($o) { return $o->post_id; },
          $wpdb->get_results(
            "SELECT post_id FROM $wpdb->postmeta ".
            "WHERE meta_key = 'proportion' AND abs(meta_value - $imgProportion) < 0.01 ".
            "ORDER BY abs(meta_value - $imgProportion) ")

        );

        $thePrices = array();
        $maxLong = 4;
        $maxShort = 4;
        foreach ($priceIds as $priceId){
          $priceMeta = get_post_meta($priceId);
          $maxShort = max($maxShort, $priceMeta['short'][0]);
          $maxLong = max($maxLong, $priceMeta['long'][0]);
          $thePrices[$priceId] = array(
            'price' => $priceMeta['price'][0],
            'short' => $priceMeta['short'][0],
            'long' => $priceMeta['long'][0],
            'proportion' => $priceMeta['proportion'][0],
            'ratio' => $priceMeta['ratio'][0],
            'format' => $priceMeta['format'][0]
          );
        }
        //js_dump($maxLong);



      // WordPress includes the jQuery UI libraries by default.
      // here you need to determine which dimension is the long side.
    //js_dump($imgOrientation);
      switch ($imgOrientation){
        case 'square': $minA = 4; $minB = 4; $maxA = $maxLong; $maxB = $maxLong; $stepA = 0.1; $stepB = 0.1; $spinB = ''; $spinA = ''; break;
        case 'vertical': $minA = 4; $minB =4*$imgProportion;  $maxA = $maxShort; $maxB = $maxLong; $stepA = 0.1; $stepB = $imgProportion;  $spinA = ' * '.$imgProportion; $spinB = ' / '.$imgProportion; break;
        case 'horizontal' :$minA = 4*$imgProportion; $minB =4; $maxA = $maxLong; $maxB = $maxShort;   $stepA = $imgProportion; $stepB = 0.1; $spinA = ' / '.$imgProportion; $spinB = ' * '.$imgProportion; break;
      }


      wp_enqueue_script( 'jquery-ui-spinner' );
      wp_enqueue_style('jquery-ui-spinner');
      echo '<script>
          jQuery( function() {
            jQuery( "#spinA" ).spinner({
              numberFormat: "n",
              max: '.$maxA.',
              min: '.$minA.',
              step: 0.1,
              spin: function( event, ui ) {
                jQuery("#spinB").spinner("value", (ui.value '.$spinA.') );
              }
            });
            jQuery( "#spinA" ).on("change keyup paste click", function(){
              if (jQuery.isNumeric(jQuery(this).val())){
                jQuery(this).val( Math.min( jQuery(this).val(), '.$maxA.' ) );
                jQuery(this).val( Math.max( jQuery(this).val(), '.$minA.' ) );
                jQuery("#spinB").spinner("value", (jQuery(this).val() '.$spinA.') );
              }
            });
          } );
          jQuery( function() {
            jQuery( "#spinB" ).spinner({
              numberFormat: "n",
              max: '.$maxB.',
              min: '.$minB.',
              step: 0.1,
              spin: function( event, ui ) {
                jQuery("#spinA").spinner("value", (ui.value '.$spinB.') );
              }
            });
            jQuery( "#spinB" ).on("change keyup paste click", function(){
                if (jQuery.isNumeric(jQuery(this).val())){
                  jQuery(this).val( Math.min( jQuery(this).val(), '.$maxB.' ) );
                  jQuery(this).val( Math.max( jQuery(this).val(), '.$minB.' ) );
                  jQuery("#spinA").spinner("value", (jQuery(this).val() '.$spinB.') );
                }
            });


          } );
      </script>'.
      '<p>
        <label for="spinA">Width:</label> <input id="spinA" name="Width" value="'.$minA.'">
      </p>'.
      '<p>
        <label for="spinA">Height:</label> <input id="spinB" name="Height" value="'.$minB.'">
      </p>';


		// include jqueryui.
		// setup a spinner for custom price.
		// offer prsets at 5" intervals?
		// wp_enqueue_scripts()
		//js_dump( 	$thePrices );

      js_dump(count($thePrices));
      foreach ($thePrices as $aPrice){
        $post_id = $aPrice->ID;
        $imgLong = get_field('long', $post_id);
        $imgShort = get_field('short', $post_id);
        $imgProportion = $imgLong / $imgShort;
        list($longProp, $shortProp) = simplifyFraction($imgLong,$imgShort);
        $imgRatio = $longProp.':'.$shortProp;
        update_field('proportion', $imgProportion, $post_id);
        update_field('ratio', $imgRatio, $post_id);
      }
    */

			///========================================
			// If artwork is for sale, then show the artwork in the context of a living space.
			$isAvailable = get_field('available_for_purchase');


			if ($isAvailable != 'No') {

				// whether or not you're selling it you can still figure out WooCOmmerce

				// START TAXONOMY SETUP=================
				// Examine existing attribute taxonomies.
				// Add a new taxonomy if one does not yet exist for this image proportion
				// wc_get_attribute_taxonomies() returns an array of objects, e.g:
				// e.g. PHP: [{"attribute_id":"1","attribute_name":"size","attribute_label":"Size","attribute_type":"select","attribute_orderby":"menu_order","attribute_public":"0"}]

				// END TAXONOMY SETUP=================

				//  1. create taxonomy (attribute_type) e.g. Size 1:1
				//  2. create terms  (attributes)  e.g. 18"x18"
				//  3. create product
				//  4. assign attributes to product


				// now check the specific sizes relevant to the current proporiton.
				// add sizes if relevant.


				// it's one thing to add terms, but another to associate them.
				// if I associate terms with a product what does that mean?
				// e.g. 6x6 does that designate the size/ or just the availability of that size?

				/*

			20cm x 20cm / 21.95 + 11.95 = 34.00      > 70
			12" x 12" / 55.95 + 18.95 = 75.00    > 150
			18" x 18" / 110.95 + 18.95 = 130.00  > 260
			24" x 24" / 159.95 + 18.95 = 179.00  > 360
			30" x 30" / 235.95 + 25.95 = 262.00  > 525
			90cm x 90cm /  325.95 + 25.95 = 352.90  > 710
      105cm x 105cm / 387.95 + 52.95 = 441.90 > 890
      120cm x 120cm / 507.95 + 52.95 = 560 > 1120

			*/

				// if it is a square, we can sell it using the simple model above
				// DISABLING SALES TEMPORARILY.
				if ($imgOrientation == 'square') {



					// make sure that linked product exists for this artwork
					$linkedProductId = getLinkedProduct(get_the_ID());

					$purchaseLink = get_the_permalink($linkedProductId);



					$purchaseButton =
						'<form action="' . $purchaseLink . '">' .
						'<button title="Purchase" style="display: inline-block; position: relative; color: #fff; background-color: #ff6600; margin-bottom: 15px;">
							<i style="color:#fff;" class="fas fa-shopping-cart"></i> <span> Buy a Print </span>
						</button>' .
						'</form><p style="color: #666;"><i>Starting at $70</i></p>';

					if (false) {

						// if this is in the traversal category.
						$post_thumbnail_id = get_post_thumbnail_id($artworkID);
						$theImageUrl = wp_get_attachment_image_url($post_thumbnail_id, 'medium');
						$purchaseButton .= '<div class="rotateImageContainer">' .
							'<img class="rotateImage" src="' . $theImageUrl . '" alt="" width="120" height="120">' .
							'</div>';
					}
				} //end if it is a square
				else {
					//if it is not a square, make the purchase button link to the contact page.
					$purchaseLink = '';
					$purchaseButton =
						'<form action="' . get_site_url() . '/contact">' .
						'<button  title="Purchase" style="display: inline-block; position: relative; color: #fff; background-color: #ff6600;">
		        <i style="color:#fff;" class="fas fa-fingerprint"></i> <span> Get in Touch </span>
		      </button>' .
						'</form>';
				}

				if ($imgId = get_post_thumbnail_id()) {
					$srcFile = get_attached_file($imgId);
					$pathDetails = pathinfo($srcFile);
					$destDir = $pathDetails['dirname'] . '/wall_' . $imgId;
					if (!file_exists($destDir)) {
						mkdir($destDir, 0777, true);
					}
					$destFile = $destDir . '/24_24.jpg';

					if (!file_exists($destFile) || $_REQUEST['refreshWall'] || $_REQUEST['rW']) {
						$theArt = new Imagick($srcFile);
						$theArt->setImageFormat("png");

						if ($imgOrientation == 'horizontal') {
							//js_dump('horizontal');
							$theArt->thumbnailImage(2600, 800, true); // this should constrain to fit the wall image.
							$yOffset = 1300 - ($theArt->getImageHeight());
						} elseif ($imgOrientation == 'vertical') {
							//js_dump('vertical');
							$theArt->thumbnailImage(800, 1200, true); // this should constrain to fit the wall image.
							$yOffset = 1400 - ($theArt->getImageHeight());
						} else {
							//js_dump('other');
							$theArt->thumbnailImage(1000, 1000, true); // this should constrain to fit the wall image.
							$yOffset = 1350 - ($theArt->getImageHeight());
						}
						$theShadow = $theArt->clone();
						$theShadow->setImageBackgroundColor(new ImagickPixel('black'));
						$theShadow->shadowImage(60, 20, 30, 30);
						$theShadow->compositeImage($theArt, Imagick::COMPOSITE_OVER, 0, 0);
						$theWall = new Imagick(get_stylesheet_directory() . '/wall.jpg');
						$xOffset = ($theWall->getImageWidth() / 2) - ($theArt->getImageWidth() / 2);
						// you should experiment with this offset calculation
						// so that it works well for any image.
						$theWall->compositeImage($theShadow, Imagick::COMPOSITE_OVER, $xOffset, $yOffset);
						$theWall->writeImage($destFile);
					} // end if file doesn't exist or refresh requested

					$thePieces = explode('/wp-content/uploads/', $destFile);
					$wallUrl = get_site_url() . '/wp-content/uploads/' . $thePieces[1];
					echo '<h3>' .
						'<i style="color:#ff6600;" class="fas fa-check-square"></i> ' .

						'<span style="color:#ff6600; font-weight: bold; font-size: 0.7em;">' .
						strtoupper(get_the_title()) .
						' is AVAILABLE FOR PURCHASE' .
						'</span>' .
						'</h3>' .
						$purchaseButton;



					$doRefresh = ($_REQUEST['refreshWall']) ? '?id=' . uniqid() : '';

					if ($purchaseLink != '') {

						echo '<a  title="Buy a Print" href="' . $purchaseLink . '"><img src="' . $wallUrl . $doRefresh . '" /></a>';
					} else {
						echo '<img src="' . $wallUrl . $doRefresh . '" />';
					}
				}
			}


			///========================================
			// check for existing coordinates and generate a Created At map if we have them.
			$theLat = get_field('latitude');
			$theLong = get_field('longitude');
			$theZoom = get_field('map_zoom_level');
			if (!is_numeric($theZoom)) {
				$theZoom = 13;
			}
			if (is_numeric($theLat) && is_numeric($theLong)) {


				$apiKeyGMapsJS = 'AIzaSyDJz6uIoQcRLhAHfP4AtWqro_yRzjXq_1E';
				// 				$apiKeyGMapsJS = 'AIzaSyBU9q7pWdtt2BI_pqCneITUGUNOKhvUa3A';
				// 'AIzaSyDJz6uIoQcRLhAHfP4AtWqro_yRzjXq_1E';
				// AIzaSyBU9q7pWdtt2BI_pqCneITUGUNOKhvUa3A
				// AIzaSyBu1SF1v6Fk4-NLIR_mYdoNMe97KQr4VXY
				//$apiKeyGMapsJS = 'AIzaSyD8WktmgfFoYt3619DVdiYFb_FICSXvYp8';
				// wp_enqueue_script( 'google_js',
				//   'https://maps.googleapis.com/maps/api/js?'.
				// 	'v=3.exp&'.
				// 	'sensor=false&'.
				// 	'key='.$apiKeyGMapsJS );


				$theUrl =
					'https://www.google.com/maps/embed/v1/place' .
					'?key=' . $apiKeyGMapsJS .
					'&q=' . $theLat . ',' . $theLong . '&zoom=' . $theZoom . '&maptype=satellite';
				$thePlace = get_field('place_name');
				if ($thePlace != '') {
					echo '<h3>' .
						'<i style="color:#ff6600;" class="fas fa-map-marker-alt"></i> ' .
						'<span style="color:#ff6600; font-weight: bold; font-size: 0.7em;">' . $thePlace . '</span> ' .
						'</h3>';
				}
				echo '
          <iframe
            style="width: 100%;"
            height="450"
            frameborder="0" style="border:0"
            src="' . $theUrl . '" allowfullscreen>
          </iframe>';
				echo '<h3>' .
					'<i style="color:#ff6600;" class="fas fa-map"></i> ' .
					'<a href="' . get_site_url() . '/art-map" style="color:#ff6600; font-weight: bold; font-size: 0.7em;">Explore the ART MAP</a> ' .
					'</h3>';
			}
			// END of google map
			//========================================





			wp_link_pages(array(
				'before' => '<div class="page-links">' . __('Pages:', 'generatepress'),
				'after'  => '</div>',
			));

			// Close the full-width wrapper if we used two-column layout
			if ($useTwoColumnLayout) {
				echo '</div><!-- .artwork-full-width-section -->';
			}
			?>
			<?php if (!$useTwoColumnLayout) : ?>
		</div><!-- .entry-content -->
	<?php endif; ?>

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