<?php
namespace Fetcher;

class Version {
  public $name;
  public $download_url;

  public function __construct($name, $download_url) {
    $this->name = $name;
    $this->download_url = $download_url;
  }
}
?>