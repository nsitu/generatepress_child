<?php
/** Exercise Settings API integration on a disposable WordPress site only. */
if (getenv('NSITU_TEST_SITE') !== '1') {
  WP_CLI::error('Run only on a disposable test site with NSITU_TEST_SITE=1.');
}

require_once ABSPATH . 'wp-admin/includes/template.php';
$source = $args[0] ?? '';
if (!is_file($source . '/inc/mapbox-settings.php') || !is_file($source . '/inc/google-maps-settings.php')) {
  WP_CLI::error('Pass the absolute theme checkout path as the first argument after this test file.');
}
require_once $source . '/inc/mapbox-settings.php';
require_once $source . '/inc/google-maps-settings.php';
nsitu_register_mapbox_settings();
nsitu_register_google_maps_settings();

function nsitu_test_assert($condition, $message)
{
  if (!$condition) {
    throw new RuntimeException($message);
  }
}

// Synthetic fixtures assembled to avoid resembling real issued credentials.
$fixtures = array(
  'nsitu_mapbox_public_token' => 'pk.' . 'synthetic_payload' . '.' . 'synthetic_signature',
  'nsitu_google_maps_api_key' => 'AI' . 'za' . str_repeat('A', 35),
);
$originals = array();
foreach ($fixtures as $option => $valid) {
  $originals[$option] = get_option($option, null);
}

try {
  foreach ($fixtures as $option => $valid) {
    delete_option($option);
    nsitu_test_assert(get_option($option) === '', "$option: missing default");
    update_option($option, "  $valid  ");
    nsitu_test_assert(get_option($option) === $valid, "$option: save/trim failed");
    foreach (array('invalid', array('unexpected'), '</script><script>alert(1)</script>') as $invalid) {
      update_option($option, $invalid);
      nsitu_test_assert(get_option($option) === $valid, "$option: invalid value replaced saved value");
    }
    nsitu_test_assert(count(get_settings_errors($option)) >= 3, "$option: errors missing");
  }
  ob_start();
  do_settings_sections('general');
  $html = ob_get_clean();
  $settings = get_registered_settings();
  foreach ($fixtures as $option => $valid) {
    nsitu_test_assert(strpos($html, 'name="' . $option . '"') !== false, "$option: field missing");
    nsitu_test_assert(strpos($html, 'value="' . $valid . '"') !== false, "$option: saved value missing");
    nsitu_test_assert($settings[$option]['group'] === 'general', "$option: wrong settings group");
    nsitu_test_assert($settings[$option]['show_in_rest'] === false, "$option: REST settings exposure");
    update_option($option, '');
    nsitu_test_assert(get_option($option) === '', "$option: clearing failed");
  }
  nsitu_test_assert(get_role('administrator')->has_cap('manage_options'), 'Administrators cannot save settings');
  nsitu_test_assert(!get_role('subscriber')->has_cap('manage_options'), 'Subscribers can save settings');
} finally {
  foreach ($originals as $option => $original) {
    delete_option($option);
    if ($original !== null) {
      update_option($option, $original);
    }
  }
}

WP_CLI::success('Map settings persist, validate, render, and clear through the WordPress Settings API.');
