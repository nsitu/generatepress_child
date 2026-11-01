<?php

$randomArtworks = get_posts(array(
  'numberposts'	=> 1,
   'orderby'   => 'rand',
  'post_type' => 'artwork'
));

foreach ($randomArtworks as $randomArt){
  $theLink = get_permalink($randomArt);
  header("Location: ".$theLink);
}
?>
