<?php
/**
 * Database-backed Mapbox configuration in Settings > General.
 */

if (!defined('ABSPATH')) {
  exit;
}

function nsitu_register_mapbox_settings()
{
  register_setting('general', 'nsitu_mapbox_public_token', array(
    'type' => 'string',
    'default' => '',
    'sanitize_callback' => 'nsitu_sanitize_mapbox_public_token',
    'show_in_rest' => false,
  ));

  add_settings_section('nsitu_mapbox', 'Mapbox', '__return_false', 'general');
  add_settings_field(
    'nsitu_mapbox_public_token',
    'Mapbox public access token',
    'nsitu_render_mapbox_token_field',
    'general',
    'nsitu_mapbox',
    array('label_for' => 'nsitu_mapbox_public_token')
  );
}
add_action('admin_init', 'nsitu_register_mapbox_settings');

function nsitu_sanitize_mapbox_public_token($value)
{
  if (is_string($value)) {
    $value = trim($value);
    if ($value === '' || preg_match('/\Apk\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\z/', $value)) {
      return $value;
    }
  }

  add_settings_error(
    'nsitu_mapbox_public_token',
    'nsitu_mapbox_invalid_token',
    'Enter a Mapbox public token beginning with pk., or clear the field to remove it. Your previous token has been kept.'
  );

  return get_option('nsitu_mapbox_public_token', '');
}

function nsitu_render_mapbox_token_field()
{
  ?>
  <input
    type="text"
    id="nsitu_mapbox_public_token"
    name="nsitu_mapbox_public_token"
    value="<?php echo esc_attr(get_option('nsitu_mapbox_public_token', '')); ?>"
    class="regular-text"
    autocomplete="off"
    spellcheck="false"
    aria-describedby="nsitu-mapbox-token-description"
  >
  <p class="description" id="nsitu-mapbox-token-description">
    Used by the leaflet-test page. This token is visible to visitors in their browser.
    Use a public token with appropriate URL restrictions. Leave blank to remove it.
  </p>
  <?php
}
