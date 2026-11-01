<?php

/**
 * Disable WooCommerce block styles (front-end). to avoid renderblocking bloat. 
 */
function slug_disable_woocommerce_block_styles()
{
  wp_dequeue_style('wc-block-style');
}
add_action('wp_enqueue_scripts', 'slug_disable_woocommerce_block_styles');



// wordpress 5.3 added auto scaling of images. I am disabling this because I use big images for zoom.
add_filter('big_image_size_threshold', '__return_false');


// Couldn't get GLB models to upload. 
// WARNING: ALLOW_UNFILTERED_UPLOADS is a major security risk - disabled for PHP 8.5 compatibility
// define('ALLOW_UNFILTERED_UPLOADS', true);

/*
function nsitu_mime_types($mime_types){
    $mime_types['gltf-binary'] = 'model/gltf-binary';
    return $mime_types;
}
add_filter('upload_mimes', 'nsitu_mime_types', 1, 1);
*/


delete_transient('wc_attribute_taxonomies');

define('DISALLOW_FILE_EDIT', false);


// this is leftover from a test of leaflet.js with mapbox
// take a look at /content-page.php where there is some page specific logic.
/*
add_action('wp_head', 'leafletscriptload');
function leafletscriptload(){
	echo '   <link rel="stylesheet" href="https://unpkg.com/leaflet@1.5.1/dist/leaflet.css" integrity="sha512-xwE/Az9zrjBIphAcBb3F6JVqxf46+CDLwfLMHloNu6KEQCAWi6HcDUbeOfBIptF7tcCzusKFjFw2yuvEpDL9wQ==" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.5.1/dist/leaflet.js" integrity="sha512-GffPMF3RvMeYyc1LWMHtK8EbPv0iNZ8/oTtHPx9/cc2ILxQ+u905qIwdpULaqDkyBKgOaB57QTMg7ztg8Jm2Og==" crossorigin=""></script>';

}*/


// NOTE: ACF Bi-Directional relationships.
// I am using  a bidirectional relationship e.g. for related artworks. and for artworks in art collections.
// I am using a plugin to do this:  https://fr.wordpress.org/plugins/post-2-post-for-acf/





// woocommerce remove additional information and reviews tabs.
add_filter('woocommerce_product_tabs', 'remove_unwanted_product_tabs', 98);
function remove_unwanted_product_tabs($tabs)
{
  unset($tabs['additional_information']);
  unset($tabs['reviews']);
  return $tabs;
}

// hide star rating from woocommerce shop loop
remove_action('woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5);

// hide woocommerce admin notices
add_filter('woocommerce_helper_suppress_admin_notices', '__return_true');

// setup extra large image size for use in theme.
add_action('after_setup_theme', 'wpdocs_theme_setup');
function wpdocs_theme_setup()
{
  add_image_size('extra-large', 2048); // 2048 pixels wide (and unlimited height)
  add_image_size('extra-large-horizontal', 3096); // 2048 pixels wide (and unlimited height)
}



function process_add_attribute($attribute)
{
  global $wpdb;



  //
  // check_admin_referer( 'woocommerce-add-new_attribute' );
  if (empty($attribute['attribute_type'])) {
    $attribute['attribute_type'] = 'text';
  }
  if (empty($attribute['attribute_orderby'])) {
    $attribute['attribute_orderby'] = 'menu_order';
  }
  if (empty($attribute['attribute_public'])) {
    $attribute['attribute_public'] = 0;
  }

  if (empty($attribute['attribute_name']) || empty($attribute['attribute_label'])) {
    return new WP_Error('error', __('Please, provide an attribute name and slug.', 'woocommerce'));
  } elseif (($valid_attribute_name = valid_attribute_name($attribute['attribute_name']))
    && is_wp_error($valid_attribute_name)
  ) {
    return $valid_attribute_name;
  } elseif (taxonomy_exists(wc_attribute_taxonomy_name($attribute['attribute_name']))) {
    return new WP_Error('error', sprintf(__('Slug "%s" is already in use. Change it, please.', 'woocommerce'), sanitize_title($attribute['attribute_name'])));
  }
  $wpdb->insert($wpdb->prefix . 'woocommerce_attribute_taxonomies', $attribute);
  do_action('woocommerce_attribute_added', $wpdb->insert_id, $attribute);
  flush_rewrite_rules();
  delete_transient('wc_attribute_taxonomies');
  return true;
}

function valid_attribute_name($attribute_name)
{
  if (strlen($attribute_name) >= 28) {
    return new WP_Error('error', sprintf(__('Slug "%s" is too long (28 characters max). Shorten it, please.', 'woocommerce'), sanitize_title($attribute_name)));
  } elseif (wc_check_if_attribute_name_is_reserved($attribute_name)) {
    return new WP_Error('error', sprintf(__('Slug "%s" is not allowed because it is a reserved term. Change it, please.', 'woocommerce'), sanitize_title($attribute_name)));
  }

  return true;
}


// I found this here, https://stackoverflow.com/questions/47518280/create-programmatically-a-woocommerce-product-variation-with-new-attribute-value/47766413
// Let's hope it works!
function create_product_variation($product_id, $variation_data)
{
  // Get the Variable product object (parent)
  $product = wc_get_product($product_id);

  $variation_post = array(
    'post_title'  => $product->get_title(),
    'post_name'   => 'product-' . $product_id . '-variation',
    'post_status' => 'publish',
    'post_parent' => $product_id,
    'post_type'   => 'product_variation',
    'guid'        => $product->get_permalink()
  );

  // Creating the product variation
  $variation_id = wp_insert_post($variation_post);

  // Get an instance of the WC_Product_Variation object
  $variation = new WC_Product_Variation($variation_id);

  // Iterating through the variations attributes
  foreach ($variation_data['attributes'] as $attribute => $term_name) {
    $taxonomy = 'pa_' . $attribute; // The attribute taxonomy

    // Check if the Term name exist and if not we create it.
    if (! term_exists($term_name, $taxonomy))
      wp_insert_term($term_name, $taxonomy); // Create the term

    $term_slug = get_term_by('name', $term_name, $taxonomy)->slug; // Get the term slug

    // Get the post Terms names from the parent variable product.
    $post_term_names =  wp_get_post_terms($product_id, $taxonomy, array('fields' => 'names'));

    // Check if the post term exist and if not we set it in the parent variable product.
    if (! in_array($term_name, $post_term_names))
      wp_set_post_terms($product_id, $term_name, $taxonomy, true);

    // Set/save the attribute data in the product variation
    update_post_meta($variation_id, 'attribute_' . $taxonomy, $term_slug);
  }

  ## Set/save all other data

  // SKU
  if (! empty($variation_data['sku']))
    $variation->set_sku($variation_data['sku']);

  // Prices
  if (empty($variation_data['sale_price'])) {
    $variation->set_price($variation_data['regular_price']);
  } else {
    $variation->set_price($variation_data['sale_price']);
    $variation->set_sale_price($variation_data['sale_price']);
  }
  $variation->set_regular_price($variation_data['regular_price']);

  // Stock
  if (! empty($variation_data['stock_qty'])) {
    $variation->set_stock_quantity($variation_data['stock_qty']);
    $variation->set_manage_stock(true);
    $variation->set_stock_status('');
  } else {
    $variation->set_manage_stock(false);
  }

  $variation->set_weight('1'); // weight (reseting)

  $variation->save(); // Save the data
}

// find a linked variable product fo the given artwork.
// if none exists, create one.
// if one does exist, update the description.
function getLinkedProduct($artworkId)
{
  $linkedProducts =   get_posts(array(
    'post_type' => 'product',
    'numberposts' => -1,
    'meta_query'  => array(
      'relation'  => 'AND',
      array('key' => 'which_artwork', 'value' => array($artworkId), 'compare' => 'IN')
      // TODO perhaps add verification here so that you only query for variable products?
    )
  ));


  if (count($linkedProducts) == 1) {
    // if you find one product, that's perfect!
    // maybe you want to update variations while you're here.

    return $linkedProducts[0]->ID;
  } elseif (count($linkedProducts) > 1) {
    // if you can find products, boil it down to one: delete any extras.
    $productIds = array_map(function ($o) {
      return $o->ID;
    },  $linkedProducts);
    while (count($productIds) > 1) {
      $extraProduct = array_shift($productIds);
      wp_delete_post($extraProduct, true);
    }
    return array_shift($linkedProducts);
  }
  if (count($linkedProducts) == 0) {

    // if you can't find a product, create one and add variations
    $theNewProduct = array(
      'post_author'  => 1,
      'post_content' => 'Print for ' . $artworkId,
      'post_status'  => 'publish',
      'post_title'   => 'Print for ' . $artworkId,
      'post_parent'  => '',
      'post_type'    => 'product'
    );
    $linkedProductId = wp_insert_post($theNewProduct);
    update_field('which_artwork', $artworkId, $linkedProductId);
    wp_set_object_terms($linkedProductId, 'variable', 'product_type', true);

    // grouped/variable/external/simple
    // start with a simple object, then expand if needed later
    $theSKU = 'artwork-alu-dibond-' . $artworkId . '-' . get_post_field('post_name', $artworkId);
    $metaUpdates = [
      ['_sku', $theSKU],
      ['_price', '100'],
      ['_regular_price', '100'],
      ['_sale_price', ''],
      ['_visibility', 'visible'],
      ['_stock_status', 'instock'],
      ['total_sales', '0'],
      ['_downloadable', 'no'],
      ['_virtual', 'no'],
      ['_purchase_note', ''],
      ['_featured', 'no'],
      ['_weight', '1'],
      ['_length', '3'],
      ['_width', '3'],
      ['_height', '3'],
      ['_sale_price_dates_from', ''],
      ['_sale_price_dates_to', ''],
      ['_sold_individually', ''],
      ['_manage_stock', 'no'],
      ['_backorders', 'no'],
      ['_stock', ''],
    ];
    foreach ($metaUpdates as $aMetaUpdate) {
      update_post_meta($linkedProductId, $aMetaUpdate[0], $aMetaUpdate[1]);
    }

    // get all applicable size attribute terms that match the current proportion.
    $theTerms = get_terms([
      'taxonomy' => 'pa_size-1-1',
      'hide_empty' => false
    ]);

    if (! is_wp_error($theTerms)) {

      // assign attribute terms to this product
      $theTermIds = array_map(function ($o) {
        return $o->term_id;
      }, $theTerms);
      wp_set_object_terms($linkedProductId, $theTermIds, 'pa_size-1-1');

      // assign attribute taxonomy to this product
      $thedata = ['pa_size-1-1' => [
        'name' => 'pa_size-1-1',
        'value' => '',
        'is_visible' => '1',
        'is_variation' => '1',
        'is_taxonomy' => '1'
      ]];
      update_post_meta($linkedProductId, '_product_attributes', $thedata);
      //js_dump($theTerms);

      // create product variations based on different sizes.
      $linkedProduct = wc_get_product($linkedProductId);

      foreach ($theTerms as $aTerm) {
        // Creating the product variation
        $variation_id = wp_insert_post([
          'post_title'  => $linkedProduct->get_title(),
          'post_name'   => 'product-' . $linkedProductId . '-variation',
          'post_status' => 'publish',
          'post_parent' => $linkedProductId,
          'post_type'   => 'product_variation',
          'guid'        => $linkedProduct->get_permalink()
        ]);
        update_post_meta($variation_id, 'attribute_pa_size-1-1', $aTerm->slug);
        $variation = new WC_Product_Variation($variation_id);
        $variation->set_sku($theSKU . '-' . $aTerm->slug);
        $variation->set_price($aTerm->description);
        $variation->set_regular_price($aTerm->description);
        $variation->set_manage_stock(false);

        list($theWidth, $theLength) = explode('x', $aTerm->slug);

        $variation->set_width($theWidth);
        $variation->set_length($theLength);
        $variation->set_height('3');

        $variation->set_weight('1');
        $variation->save(); // Save the data
      } // end foreach term

      $theTitleText = 'Print on Aluminum Dibond - ' . get_the_title($artworkId);
      $theContentText = 'This image is offered as an open edition print, and is  created on aluminum dibond. It is  shipped ready-to-hang. Format specifications are available via my <a href="https://www.whitewall.ca/photo-lab/alu-dibond/direct-printing-on-aluminium-backing">supplier</a>. ';
      $new_slug = $linkedProductId . '-' . sanitize_title($theTitleText);
      wp_update_post(array(
        'ID'           => $linkedProductId,
        'post_title'   => $theTitleText,
        'post_content' => $theContentText,
        'post_name'        => $new_slug
      ));

      // TODO: you could use the couch image here.
      set_post_thumbnail($linkedProductId, get_post_thumbnail_id($artworkId));

      return $linkedProductId;
    } else {
      js_dump('Could not find taxonomy');
      return false;
    }
  } // end if there are no zero products
} // end function getLinkedProduct


// These IDs correspond to images that i've uploaded into my whitewall.ca account.
// They cover a wide range of proportions
// Each image has the highest possible resolution for that proportion (625 Megapixels).
// These images serve to simplify querying for price information.
// I made them using a photoshop script.
function getWhitewallIds()
{
  return [
    ["shortside" => "7", "longside" => "13", "id" => "0%3A2d7765623031%3A56981a0434898%3A1a43f6"],
    ["shortside" => "7", "longside" => "12", "id" => "0%3A2d7765623031%3A569819c92c7a0%3A1a43f6"],
    ["shortside" => "7", "longside" => "11", "id" => "0%3A2d7765623031%3A5698198c08dd8%3A1a43f6"],
    ["shortside" => "7", "longside" => "10", "id" => "0%3A2d7765623031%3A56981955ae120%3A1a43f6"],
    ["shortside" => "7", "longside" => "9", "id" => "0%3A2d7765623031%3A5698191cbb368%3A1a43f6"],
    ["shortside" => "7", "longside" => "8", "id" => "0%3A2d7765623031%3A569818e127810%3A1a43f6"],
    ["shortside" => "6", "longside" => "13", "id" => "0%3A2d7765623031%3A5698176e9a2d0%3A1a43f6"],
    ["shortside" => "6", "longside" => "11", "id" => "0%3A2d7765623031%3A5698172fa8a68%3A1a43f6"],
    ["shortside" => "6", "longside" => "7", "id" => "0%3A2d7765623031%3A569816f59e408%3A1a43f6"],
    ["shortside" => "5", "longside" => "16", "id" => "0%3A2d7765623031%3A5698127fef418%3A1a43f6"],
    ["shortside" => "5", "longside" => "14", "id" => "0%3A2d7765623031%3A56981240fe380%3A1a43f6"],
    ["shortside" => "5", "longside" => "13", "id" => "0%3A2d7765623031%3A56981200d6258%3A1a43f6"],
    ["shortside" => "5", "longside" => "12", "id" => "0%3A2d7765623031%3A569811c3cda28%3A1a43f6"],
    ["shortside" => "5", "longside" => "11", "id" => "0%3A2d7765623031%3A56981187b80b0%3A1a43f6"],
    ["shortside" => "5", "longside" => "9", "id" => "0%3A2d7765623031%3A5698114ba32f0%3A1a43f6"],
    ["shortside" => "5", "longside" => "8", "id" => "0%3A2d7765623031%3A5698110e9be48%3A1a43f6"],
    ["shortside" => "5", "longside" => "7", "id" => "0%3A2d7765623031%3A569810d1cfaf0%3A1a43f6"],
    ["shortside" => "5", "longside" => "6", "id" => "0%3A2d7765623031%3A569810930d088%3A1a43f6"],
    ["shortside" => "4", "longside" => "15", "id" => "0%3A2d7765623031%3A56980e66aa768%3A1a43f6"],
    ["shortside" => "4", "longside" => "13", "id" => "0%3A2d7765623031%3A56980e27efde8%3A1a43f6"],
    ["shortside" => "4", "longside" => "11", "id" => "0%3A2d7765623031%3A56980de7caba0%3A1a43f6"],
    ["shortside" => "4", "longside" => "9", "id" => "0%3A2d7765623031%3A56980daa0fbf8%3A1a43f6"],
    ["shortside" => "4", "longside" => "7", "id" => "0%3A2d7765623031%3A56980d6746948%3A1a43f6"],
    ["shortside" => "4", "longside" => "5", "id" => "0%3A2d7765623031%3A56980d22b2ea8%3A1a43f6"],
    ["shortside" => "3", "longside" => "4", "id" => "0%3A2d7765623031%3A569807e0beb50%3A1a43f6"],
    ["shortside" => "3", "longside" => "5", "id" => "0%3A2d7765623031%3A569807a68dfa8%3A1a43f6"],
    ["shortside" => "3", "longside" => "7", "id" => "0%3A2d7765623031%3A5698077129088%3A1a43f6"],
    ["shortside" => "3", "longside" => "8", "id" => "0%3A2d7765623031%3A56980738e3070%3A1a43f6"],
    ["shortside" => "3", "longside" => "10", "id" => "0%3A2d7765623031%3A569807037fca8%3A1a43f6"],
    ["shortside" => "3", "longside" => "11", "id" => "0%3A2d7765623031%3A569806ce18290%3A1a43f6"],
    ["shortside" => "3", "longside" => "13", "id" => "0%3A2d7765623031%3A56980696c4960%3A1a43f6"],
    ["shortside" => "3", "longside" => "14", "id" => "0%3A2d7765623031%3A56980660a6180%3A1a43f6"],
    ["shortside" => "3", "longside" => "16", "id" => "0%3A2d7765623031%3A5698060550fd8%3A1a43f6"],
    ["shortside" => "2", "longside" => "3", "id" => "0%3A2d7765623031%3A5698003df9cf0%3A1a43f6"],
    ["shortside" => "2", "longside" => "5", "id" => "0%3A2d7765623031%3A5697ffff00f58%3A1a43f6"],
    ["shortside" => "2", "longside" => "7", "id" => "0%3A2d7765623031%3A5697ffbedad70%3A1a43f6"],
    ["shortside" => "2", "longside" => "9", "id" => "0%3A2d7765623031%3A5697ff80df2a0%3A1a43f6"],
    ["shortside" => "2", "longside" => "11", "id" => "0%3A2d7765623031%3A5697ff4508128%3A1a43f6"],
    ["shortside" => "2", "longside" => "13", "id" => "0%3A2d7765623031%3A5697fee1d9ef8%3A1a43f6"],
    ["shortside" => "2", "longside" => "15", "id" => "0%3A2d7765623031%3A5697fe7e12b90%3A1a43f6"],
    ["shortside" => "1", "longside" => "1", "id" => "0%3A2d7765623031%3A5697fd18cc138%3A1a43f6"],
    ["shortside" => "1", "longside" => "2", "id" => "0%3A2d7765623031%3A5697fcdcb5c08%3A1a43f6"],
    ["shortside" => "1", "longside" => "3", "id" => "0%3A2d7765623031%3A5697fc9eb9198%3A1a43f6"],
    ["shortside" => "1", "longside" => "4", "id" => "0%3A2d7765623031%3A5697fc6209f18%3A1a43f6"],
    ["shortside" => "1", "longside" => "5", "id" => "0%3A2d7765623031%3A5697fc0a31a98%3A1a43f6"],
    ["shortside" => "1", "longside" => "6", "id" => "0%3A2d7765623031%3A5697fbb1b3da8%3A1a43f6"],
    ["shortside" => "1", "longside" => "5", "id" => "0%3A2d7765623031%3A5697f9d783850%3A1a43f6"],
    ["shortside" => "1", "longside" => "6", "id" => "0%3A2d7765623031%3A5697f97cee110%3A1a43f6"],
    ["shortside" => "1", "longside" => "7", "id" => "0%3A2d7765623031%3A5697f93fe6c68%3A1a43f6"],
    ["shortside" => "1", "longside" => "8", "id" => "0%3A2d7765623031%3A5697f8e737e50%3A1a43f6"],
    ["shortside" => "1", "longside" => "9", "id" => "0%3A2d7765623031%3A5697f8ad06308%3A1a43f6"],
    ["shortside" => "1", "longside" => "10", "id" => "0%3A2d7765623031%3A5697f84ac9050%3A1a43f6"],
    ["shortside" => "1", "longside" => "11", "id" => "0%3A2d7765623031%3A5697f80cce908%3A1a43f6"],
    ["shortside" => "1", "longside" => "12", "id" => "0%3A2d7765623031%3A5697f7b368d28%3A1a43f6"],
    ["shortside" => "1", "longside" => "13", "id" => "0%3A2d7765623031%3A5697f77ef7878%3A1a43f6"],
    ["shortside" => "1", "longside" => "14", "id" => "0%3A2d7765623031%3A5697f729ae858%3A1a43f6"],
    ["shortside" => "1", "longside" => "15", "id" => "0%3A2d7765623031%3A5697f3ebf6900%3A1a43f6"]
  ];
}

function wwNearestProportion($x, $y)
{
  $nearestId = 0;
  $nearestDiff = 1;
  $whitewallIds = getWhitewallIds();
  for ($i = 0; $i < count($whitewallIds); $i++) {
    $wwProp = $whitewallIds[$i]['longside'] / $whitewallIds[$i]['shortside'];
    $thisProp = max($x, $y) / min($x, $y);
    $aDiff = abs($wwProp - $thisProp);
    if ($aDiff < $nearestDiff) {
      $nearestDiff = $aDiff;
      $nearestId = $i;
    }
  }
  return $whitewallIds[$nearestId];
}

// setup Woocommerce Size taxonomies corresponding to WW images
//  if they dont already exist.

add_action('init', 'setup_ww_size_taxonomies');
function setup_ww_size_taxonomies()
{
  global $wpdb;

  delete_transient('wc_attribute_taxonomies');

  // use the query directly instead of using wc_get_attribute_taxonomies() to skip transient cache
  $attribute_taxonomies = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "woocommerce_attribute_taxonomies WHERE attribute_name != '' ORDER BY attribute_name ASC;");
  $existingTaxonomies = array_map(function ($o) {
    return $o->attribute_name;
  },  $attribute_taxonomies);

  foreach (getWhitewallIds() as $wwID) {
    // for a square there is only one option.
    if ($wwID['shortside'] == $wwID['longside']) {
      $expectedTaxonomies = [
        [
          'name' =>  'size-' . $wwID['shortside'] . '-' . $wwID['longside'],
          'label' => 'Size'
        ]
      ];
    } else {
      $expectedTaxonomies = [
        [
          'name' =>  'size-' . $wwID['shortside'] . '-' . $wwID['longside'],
          'label' => 'Size'
        ],
        [
          'name' =>  'size-' . $wwID['longside'] . '-' . $wwID['shortside'],
          'label' => 'Size'
        ]
      ];
    }
    foreach ($expectedTaxonomies as $expectedTaxonomy) {
      if (! in_array($expectedTaxonomy['name'], $existingTaxonomies)) {
        process_add_attribute(
          array(
            'attribute_name' => $expectedTaxonomy['name'],
            'attribute_label' => $expectedTaxonomy['label'],
            'attribute_orderby' => 'name_num'
          )
        );
      }
    }
  }
}


// setup specific size attributes for artwork based products
add_action('wp_loaded', 'setup_ww_size_terms');
function setup_ww_size_terms()
{
  $theAttributes = get_sizes_and_prices(1, 1); // start with a square for now

  $theTaxonomy = 'pa_size-1-1';
  foreach ($theAttributes as $sA) {
    $theName = $sA['x'] . 'cm × ' . $sA['y'] . 'cm';
    $theSlug = $sA['x'] . 'x' . $sA['y'];
    $theDescription = $sA['price'];
    if (taxonomy_exists($theTaxonomy)) {
      if (! term_exists($theSlug, $theTaxonomy)) {
        $theInsert = wp_insert_term(
          $theName,
          $theTaxonomy,
          [
            'slug' => $theSlug,
            'description' => $theDescription
          ]
        );
      }
    }
  }
}


function get_sizes_and_prices($xProp, $yProp)
{
  // going to hard wire this for now.
  // in future, connect this to live WW data via node js server
  if ($xProp == 1 && $yProp == 1) {
    return  [
      ['x' => 20, 'y' => 20, 'price' => 70],
      ['x' => 30, 'y' => 30, 'price' => 150],
      ['x' => 45, 'y' => 45, 'price' => 260],
      ['x' => 60, 'y' => 60, 'price' => 360],
      ['x' => 75, 'y' => 75, 'price' => 525],
      ['x' => 90, 'y' => 90, 'price' => 710],
      ['x' => 105, 'y' => 105, 'price' => 890],
      ['x' => 120, 'y' => 120, 'price' => 1120]
    ];
  } else {
    return false;
  }
}
//This addresses an issue I posted about here: https://generatepress.com/forums/topic/gp-css-breaks-display-on-other-plugins/
// It also hides the featured video box in certain contexts
add_action('admin_head', 'fixGPStyleIssues');
function fixGPStyleIssues()
{
  echo '<style>
  div:not(.site-box) .complete:after{
           display: none !important;
       }
       .post-type-sa_estimate #featured-video-plus-box,
       .post-type-sa_invoice #featured-video-plus-box{
         display: none;
       }
       .post-type-sa_estimate #eg-meta-box{
         display: none;
       }
       .post-type-sa_estimate #generate_layout_options_meta_box{
         display: none;
       }

  </style>';
}

// add_action( 'admin_menu', 'clean_up_metaboxes', 999 );
// add_action( 'add_meta_boxes', 'clean_up_metaboxes', 999 );

add_action('do_meta_boxes', 'clean_up_metaboxes', 10000);
function clean_up_metaboxes()
{
  remove_meta_box('featured-video-plus-box', 'sa_invoice', 'normal');
  remove_meta_box('eg-meta-box', 'sa_invoice', 'normal');
  remove_meta_box('generate_layout_options_meta_box', 'sa_invoice', 'normal');
}

function _custom_si_pdf_service_scripts($old_scripts_and_styles)
{
  $scripts_and_styles = $old_scripts_and_styles; // remove this line if you don't want to remove the default styling and scripts

  ob_start(); // place your custom styles and javascript below 
?>
  <style type="text/css">
    /**
 			 * Change the styling of the header
 			 */
    .sa_invoice-template-default header#header {
      background-color: #ff6600;
    }

    #stripe_pop_form,
    section#payment {
      display: none !important;
    }
  </style>
<?php
  $scripts_and_styles .= ob_get_clean();
  return $scripts_and_styles;
}
add_filter('si_pdf_service_scripts', '_custom_si_pdf_service_scripts');



function setPrices()
{
  if (is_page('set-prices')) {
    // this page works in conjunction with a jQuery script that is pasted into
    // the browser console, and gathers price data from us.whitewall.com
    // it's admittedly hacky and insecure as it used the front end to import data,
    // but i dont yet know enough about the wordpress REST api to tackle this differently.

    header("Access-Control-Allow-Origin: *");
    if (
      isset($_REQUEST['theWidth']) && is_numeric($_REQUEST['theWidth']) &&
      isset($_REQUEST['theHeight']) && is_numeric($_REQUEST['theHeight']) &&
      isset($_REQUEST['thePrice']) && is_numeric($_REQUEST['thePrice']) &&
      isset($_REQUEST['theFormat']) &&
      isset($_REQUEST['pw']) && $_REQUEST['pw'] == "EyKdh5Jm5u9H8Knf2KUcHRBSsNrKTCtUCrj9tVf6dyD6NYqdR9eNNduuMsu9StPWwpHPrZm6jrX2he5EMXU2WmUmvyYjwSkVLKyNWXEtyyX5GgT7r5rtHLR36AxdeEHnyNgewbza5cdAhTrph9ZbYmBZ3nmE9RJSdMEtBF5uG75uuHDeZLABcmuyyrrb4SU3PJeNznu7T299ARFdHZGM57BY8RYQappVrrNFhUAq7dy5DjqFEdNSRKptA32gXMbE"
    ) {



      $theWidth = $_REQUEST['theWidth'];
      $theHeight = $_REQUEST['theHeight'];
      $thePrice = $_REQUEST['thePrice'];
      $theFormat = urldecode($_REQUEST['theFormat']);

      // check if the format already exists, and if not create it.
      $theMatches = get_posts(array("post_type" => "format", "name" => $theFormat));
      if (count($theMatches) > 0) {
        $theMatch = reset($theMatches);
        $format_ID = $theMatch->ID;
      } else {
        $my_format = array(
          'post_title'  => $theFormat,
          'post_type'    => 'format',
          'post_status'  => 'publish'
        );
        $format_ID = wp_insert_post($my_format);
      }

      // delete existing prices for this size and format.

      $existingPrices = get_posts(array(
        'numberposts'  => -1,
        'post_type'    => 'price',
        'meta_query'  => array(
          'relation'    => 'AND',
          array(
            'key'     => 'width',
            'value'      => $theWidth,
            'compare'   => '=',
          ),
          array(
            'key'      => 'height',
            'value'      => $theHeight,
            'compare'   => '=',
          ),
          array(
            'key'      => 'format',
            'value'      => $format_ID,
            'compare'   => '=',
          ),
        ),
      ));
      foreach ($existingPrices as $existingPrice) {
        wp_delete_post($existingPrice->ID);
      }

      $imgProportion = $theHeight / $theWidth;
      list($longProp, $shortProp) = simplifyFraction($theHeight, $theWidth);
      $imgRatio = $longProp . ':' . $shortProp;


      // vars
      $my_price = array(
        'post_title'  => $thePrice . ' - ' . $theWidth . 'x' . $theHeight . ' (' . $imgRatio . ') ' . $theFormat,
        'post_type'    => 'price',
        'post_status'  => 'publish'
      );

      // insert the post into the database
      $price_id = wp_insert_post($my_price);

      // save a basic text value
      update_field('price', $thePrice, $price_id);
      update_field('short', $theWidth, $price_id);
      update_field('long', $theHeight, $price_id);
      update_field('format', $format_ID, $price_id);
      update_field('proportion', $imgProportion, $price_id);
      update_field('ratio', $imgRatio, $price_id);

      echo 'Price Inserted.';
    } else {
      header('location: https://www.nsitu.ca');
    }

    exit;
  }
}
add_action('get_header', 'setPrices');


function generatepress_child_enqueue_scripts()
{
  if (is_rtl()) {
    wp_enqueue_style('generatepress-rtl', trailingslashit(get_template_directory_uri()) . 'rtl.css');
  }
}
add_action('wp_enqueue_scripts', 'generatepress_child_enqueue_scripts', 100);


/**
 * Send debug code to the Javascript console
 */
function js_dump($data)
{

  if (! headers_sent()) {
    $GLOBALS['js_dump_data'][] = "<script>console.log(" . json_encode($data) . ");</script>";
  } else {
    foreach ($GLOBALS['js_dump_data'] as $someData) {
      echo $someData;
    }
    $GLOBALS['js_dump_data'] = [];
    echo "<script>console.log(" . json_encode($data) . ");</script>";
  }

  if (is_array($data) || is_object($data)) {
  } else {

    // echo("<script>console.log('PHP: ".$data."');</script>");
  }
}


//Sprout invoices set invoice due date to 30 days instead of the default 14
function _default_due_in_days()
{
  return 30;
}
add_filter('si_default_due_in_days', '_default_due_in_days');

// Set default 13% HST tax rate on new invoices and estimates.
// Also set the default due date to 30 days from creation.
function _si_information_meta_box_args($args)
{
  if ('auto-draft' == $args['post']->post_status) { // only adjust drafts
    $args['tax'] = 13;
    $args['due_date'] = current_time('timestamp') + (DAY_IN_SECONDS * 30);
  }
  return $args;
}
add_filter('load_view_args_admin/meta-boxes/invoices/information.php', '_si_information_meta_box_args');
add_filter('load_view_args_admin/meta-boxes/estimates/information.php', '_si_information_meta_box_args');

add_filter('widget_text', 'do_shortcode');


// greatest common denominator
function gcd($a, $b)
{
  $a = abs($a);
  $b = abs($b);
  if ($a < $b) list($b, $a) = array($a, $b);
  if ($b == 0) return $a;
  $r = $a % $b;
  while ($r > 0) {
    $a = $b;
    $b = $r;
    $r = $a % $b;
  }
  return $b;
}
//simplify fraction
function simplifyFraction($num, $den)
{
  $g = gcd($num, $den);
  return array($num / $g, $den / $g);
}
