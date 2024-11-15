<?php
namespace Fetcher\Provider;

use \Fetcher\Version;

class Url extends Provider {
  static $archive_exclude = '';
  static $archive_strip = 0;

  public function get_download_url($version_name, $package_name, $package_author = null) {
    return $package_name;
  }

  public function get_latest_version_name($package_name, $package_author = null) {
    return 'latest';
  }

  public function get_package_info($package_name, $package_author = null) {
    return array(
      'name'      => $package_name,
      'provider'  => 'url'
    );
  }

  public function get_version($version_name, $package_name, $package_author = null) {
    $download_url = self::get_download_url($version_name, $package_name, $package_author);
    return new Version($version_name, $download_url);
  }

  public function get_version_names($package_name, $package_author = null) {
    return array('latest');
  }

  public function get_versions($package_name, $package_author = null) {
    return array(self::get_version('latest', $package_name, $package_author));
  }

  public function validate_parameters($package_name, $package_author = null) {}
}
?>