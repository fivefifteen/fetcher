<?php
namespace Fetcher\Provider;

use \Fetcher\Version;
use \Fetcher\Provider\GitHub as GitHubProvider;
use \Fetcher\Provider\Npm as NpmProvider;
use \Fetcher\Provider\Url as UrlProvider;

abstract class Provider {
  static $archive_exclude = '';
  static $archive_strip = 0;

  abstract protected function get_download_url($version_name, $package_name, $package_author);
  abstract protected function get_latest_version_name($package_name, $package_author);
  abstract protected function get_package_info($package_name, $package_author);
  abstract protected function get_version($version_name, $package_name, $package_author);
  abstract protected function get_versions($package_name, $package_author);
  abstract protected function validate_parameters($package_name, $package_author);

  public function get_latest_version($package_name, $package_author = null) {
    $version_name = $this->get_latest_version_name($package_name, $package_author);
    $download_url = $this->get_download_url($version_name, $package_name, $package_author);
    return new Version($version_name, $download_url);
  }

  static function get_provider_by_slug($slug) {
    switch(strtolower($slug)) {
      case 'github':
        return new GitHubProvider;
      case 'npm':
        return new NpmProvider;
      case 'url':
      case 'file':
        return new UrlProvider;
      default:
        throw new \Error("$slug is not a valid provider");
    }
  }
}
?>