<?php
namespace Fetcher;

use \Fetcher\Helper\File;
use \Fetcher\Provider\Provider;

class Package {
  public $provider;
  public $name;
  public $author;
  public $provider_instance;

  public function __construct($provider, $name, $author = null) {
    $this->provider = $provider;
    $this->name = $name;
    $this->author = $author;
    $this->provider_instance = Provider::get_provider_by_slug($this->provider);
    $this->provider_instance->validate_parameters($this->name, $this->author);
  }

  public function get_info() {
    return $this->provider_instance->get_package_info($this->name, $this->author);
  }

  public function get_latest_version() {
    return $this->provider_instance->get_latest_version($this->name, $this->author);
  }

  public function get_version($version) {
    return $this->provider_instance->get_version($version, $this->name, $this->author);
  }

  public function get_versions() {
    return $this->provider_instance->get_versions($this->name, $this->author);
  }
}
?>