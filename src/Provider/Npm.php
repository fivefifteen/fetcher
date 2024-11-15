<?php
namespace Fetcher\Provider;

use \Composer\Semver\Semver;
use \Fetcher\Helper\Format;
use \Fetcher\Helper\Request;
use \Fetcher\Version;

class Npm extends Provider {
  static $archive_exclude = '';
  static $archive_strip = 1;
  static $api_base = 'https://registry.npmjs.org';
  public $package_data;

  private function format_package_string($package_name, $package_author = null) {
    return $package_author ? "@$package_author/$package_name" : $package_name;
  }

  public function get_download_url($version_name, $package_name, $package_author = null) {
    $package_string = self::format_package_string($package_name, $package_author);
    return Format::build_url(self::$api_base, $package_string, '-', $package_string . '-' . $version_name . '.tgz');
  }

  public function get_latest_version_name($package_name, $package_author = null) {
    $package_data = self::get_package_data($package_name, $package_author);
    return $package_data['dist-tags']['latest'];
  }

  public function get_package_data($package_name, $package_author = null) {
    if (!$this->package_data) {
      $package_string = self::format_package_string($package_name, $package_author);
      $url = Format::build_url(self::$api_base, $package_string);
      $this->package_data = Request::get_json($url);
    }

    return $this->package_data;
  }

  public function get_package_info($package_name, $package_author = null) {
    $package_data = self::get_package_data($package_name, $package_author);
    $latest_version_name = self::get_latest_version_name($package_name, $package_author);

    return array(
      'name'            => $package_data['name'],
      'description'     => $package_data['description'],
      'author'          => $package_data['versions'][$latest_version_name]['_npmUser']['name'],
      'latest_version'  => $latest_version_name,
      'homepage'        => $package_data['homepage'],
      'license'         => $package_data['license'],
      'provider'        => 'npm'
    );
  }

  public function get_version($version_name, $package_name, $package_author = null) {
    if (strtolower($version_name) === 'latest') {
      return self::get_latest_version($package_name, $package_author);
    } else if (Format::is_version_range($version_name)) {
      $version_names = self::get_version_names($package_name, $package_author);
      $valid_versions = Semver::satisfiedBy($version_names, $version_name);
      $version_names = Semver::rsort($valid_versions);
      $version_name = reset($version_names);

      print_r(compact('version_names', 'valid_versions', 'version_name'));
    }

    if (Format::is_version_string($version_name)) {
      $download_url = self::get_download_url($version_name, $package_name, $package_author);
      return new Version($version_name, $download_url);
    }

    return false;
  }

  public function get_version_names($package_name, $package_author = null) {
    $package_data = self::get_package_data($package_name, $package_author);
    $version_names = Semver::rsort(array_keys($package_data['versions']));
    return $version_names;
  }

  public function get_versions($package_name, $package_author = null) {
    $version_names = self::get_version_names($package_name, $package_author);

    return array_map(function($version_name) use($package_name, $package_author) {
      $download_url = self::get_download_url($version_name, $package_name, $package_author);
      return new Version($version_name, $download_url);
    }, $version_names);
  }

  public function validate_parameters($package_name, $package_author = null) {}
}
?>