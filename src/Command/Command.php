<?php
namespace Fetcher\Command;

use \Ahc\Cli\Output\Writer;
use \Fetcher\Helper\Format;
use \Fetcher\Helper\Request;

abstract class Command extends \Ahc\Cli\Input\Command {
  public function __construct($command, $description) {
    parent::__construct($command, $description);
  }

  public function load_config($config_path, $quiet = false) {
    $writer = new Writer();
    $config = false;
    $json = false;
    $is_default_check = basename($config_path) === 'fetch.json';
    $alt_config_path = Format::build_path(dirname($config_path), 'composer.json');

    try {
      if (file_exists($config_path)) {
        $json = Request::get_json($config_path);
      } elseif ($is_default_check && file_exists($alt_config_path)) {
        $json = Request::get_json($alt_config_path);
      }

      if ($json) {
        if (isset($json['fetcher'])) {
          $config = $json['fetcher'];
        } elseif (isset($json['extra']) && isset($json['extra']['fetcher'])) {
          $config = $json['extra']['fetcher'];
        }
      }

      if (!$config) {
        throw new \Error("A config file was found but it didn't have any valid data for Fetcher");
      }
    } catch (\Throwable $e) {
      if (!$is_default_check) {
        throw $e;
      }
    }

    if (!$quiet && $config) {
      $writer->colors("<info>Loaded</end> <subject>{$config_path}</end>", true);
    }

    return $config;
  }
}
?>