<?php
/**
 * Database-backed Google Maps configuration in Settings > General.
 */

if (!defined('ABSPATH')) {
  exit;
}

function nsitu_register_google_maps_settings()
{
  register_setting('general', 'nsitu_google_maps_api_key', array(
    'type' => 'string',
    'default' => '',
    'sanitize_callback' => 'nsitu_sanitize_google_maps_api_key',
    'show_in_rest' => false,
  ));

  add_settings_section('nsitu_google_maps', 'Google Maps', '__return_false', 'general');
  add_settings_field(
    'nsitu_google_maps_api_key',
    'Google Maps API key',
    'nsitu_render_google_maps_key_field',
    'general',
    'nsitu_google_maps',
    array('label_for' => 'nsitu_google_maps_api_key')
  );
}
add_action('admin_init', 'nsitu_register_google_maps_settings');

function nsitu_sanitize_google_maps_api_key($value)
{
  if (is_string($value)) {
    $value = trim($value);
    if ($value === '' || preg_match('/\AAIza[A-Za-z0-9_-]{35}\z/', $value)) {
      return $value;
    }
  }

  add_settings_error(
    'nsitu_google_maps_api_key',
    'nsitu_google_maps_invalid_key',
    'Enter a valid Google Maps API key, or clear the field to remove it. Your previous key has been kept.'
  );

  return get_option('nsitu_google_maps_api_key', '');
}

function nsitu_render_google_maps_key_field()
{
  ?>
  <input
    type="text"
    id="nsitu_google_maps_api_key"
    name="nsitu_google_maps_api_key"
    value="<?php echo esc_attr(get_option('nsitu_google_maps_api_key', '')); ?>"
    class="regular-text"
    autocomplete="off"
    spellcheck="false"
    aria-describedby="nsitu-google-maps-key-description"
  >
  <p class="description" id="nsitu-google-maps-key-description">
    Used by the art map and artwork location embeds. This key is visible to visitors in their browser.
    Enable Maps JavaScript API and Maps Embed API, and restrict the key to your website and these APIs.
    Leave blank to hide the maps.
  </p>
  <?php
}
