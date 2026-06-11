<?php
/**
 * Guards the invariant that the plugin version matches across the plugin header, the MEMBERFUL_VERSION constant, and
 * the readme Stable tag. Catches version drift before a release rather than at release time.
 *
 * @package Memberful
 */

/**
 *  Class VersionSyncTest
 */
class VersionSyncTest extends WP_UnitTestCase {

  public function test_version_matches_across_header_constant_and_readme() {
    $root        = dirname( __DIR__ );
    $plugin_file = $root . '/memberful-wp.php';
    $readme_file = $root . '/readme.txt';

    $header         = get_file_data( $plugin_file, array( 'Version' => 'Version' ) );
    $header_version = $header['Version'];

    preg_match( "/MEMBERFUL_VERSION', '([^']+)'/", file_get_contents( $plugin_file ), $constant_match );
    preg_match( '/^Stable tag:\s*(.+)$/m', file_get_contents( $readme_file ), $stable_tag_match );

    $this->assertNotEmpty( $header_version, 'Plugin header Version should be present.' );
    $this->assertSame( $header_version, trim( $constant_match[1] ), 'Header vs MEMBERFUL_VERSION constant.' );
    $this->assertSame( $header_version, trim( $stable_tag_match[1] ), 'Header vs readme Stable tag.' );
  }
}
