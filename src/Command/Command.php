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

    try {
      $config = Request::get_json($config_path);
    } catch (\Throwable $e) {
      if (basename($config_path) !== 'fetch.json') {
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