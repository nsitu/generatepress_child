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
        #artMap {
          height: 80vh;
          width: 100%;
          margin: 0px;
          padding: 0px;
        }
      </style>
      <div id="artMap"></div>
      <script>
        var markers = [];

        <?php

        // get location information from artworks.
        //TODO make sure they are actually published.

        $artworksSQL = "SELECT wp_posts.ID, wp_posts.post_title, m1.meta_value lat, m2.meta_value lon
FROM wp_posts
INNER JOIN wp_postmeta m1
  ON (wp_posts.ID = m1.post_id )
INNER JOIN wp_postmeta m2
  ON (wp_posts.ID = m2.post_id )
WHERE wp_posts.post_type   = 'artwork'
AND   wp_posts.post_status = 'publish'
AND ( m1.meta_key = 'latitude' AND m1.meta_value != '' )
AND ( m2.meta_key = 'longitude' AND m2.meta_value != '' )
ORDER BY wp_posts.post_date
DESC";

        $artworksResult =  $wpdb->get_results($artworksSQL);
        $jsData = array();
        foreach ($artworksResult as $someArt) {
          $thumbUrl = wp_get_attachment_image_url(get_post_thumbnail_id($someArt->ID), 'thumbnail');
          $artUrl = get_the_permalink($someArt->ID);
          $artID = $someArt->ID;
          $artTitle = $someArt->post_title;
          $artLat = $someArt->lat;
          $artLon = $someArt->lon;
          $jsData[] = '["' . $artTitle . '",' . $artLat . ',' . $artLon . ',"' . $thumbUrl . '", "' . $artUrl . '"]';
        }
        $jsData = implode(', ', $jsData);

        ?>


        function initialize() {

          var theArtworks = [<?php echo $jsData; ?>];

          var map = new google.maps.Map(document.getElementById('artMap'), {
            zoom: 10,
            center: new google.maps.LatLng(43.666084, -79.446687),
            mapTypeId: google.maps.MapTypeId.ROADMAP
          });

          var infowindow = new google.maps.InfoWindow();
          var bounds = new google.maps.LatLngBounds();



          for (var i = 0; i < theArtworks.length; i++) {




            var newMarker = new google.maps.Marker({
              position: new google.maps.LatLng(theArtworks[i][1], theArtworks[i][2]),
              map: map,
              title: theArtworks[i][0]
            });



            google.maps.event.addListener(newMarker, 'click', (function(newMarker, i) {
              return function() {
                infowindow.setContent(
                  '<p style="text-align: center;"><a href="' + theArtworks[i][4] + '">' +
                  '<img style="width: 75px; height: 75px;" src="' + theArtworks[i][3] + '">' +
                  '<br/>' +
                  theArtworks[i][0] +
                  '</a></p>');
                infowindow.open(map, newMarker);
              }
            })(newMarker, i));

            bounds.extend(newMarker.getPosition());
            markers.push(newMarker);
          }
          map.fitBounds(bounds);
        }

        initialize();
      </script>
      <?php
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