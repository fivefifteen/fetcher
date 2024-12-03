<?php
namespace Fetcher\Command;

use \Ahc\Cli\Output\Writer;
use \Fetcher\Package;
use \Fetcher\Helper\Format;

class Info extends Command {
  static $defaults = array(
    'package'   => null,
    'providers' => 'github,npm',
    'verbosity' => 0
  );

  public function __construct() {
    parent::__construct('info', 'Returns info about a package');

    $this
      ->argument('<package>', 'Package to get info for')
      ->option('-p --providers [providers]', 'The repository providers to search and what order to search them in', 'strval', self::$defaults['providers'])
      ->usage(
        '<bold>  $0 info</end> <comment>include-media</end> ## Get info about the `include-media` package<eol/>' .
        '<bold>  $0 n</end> <comment>-p github eduardoboucas/include-media</end> ## Get info about the `include-media` package from GitHub<eol/>'
      );
  }

  public function execute() {
    $this->run(array(
      'package'   => $this->package,
      'providers' => $this->providers,
      'verbosity' => $this->verbosity
    ));
  }

  public function run($args) {
    extract(array_merge(self::$defaults, $args));

    $writer = new Writer();

    $info = false;
    $package_args = Format::parse_package_string($package);

    if ($package_args['provider']) {
      $providers = array($package_args['provider']);
    } else {
      $providers = Format::parse_comma_list($providers);
    }

    foreach($providers as $provider) {
      $info = false;

      try {
        $package_obj = new Package($provider, $package_args['name'], $package_args['author']);
        $info = $package_obj->get_info();
      } catch (\Throwable $e) {
        // Errors here are ignored because there could be multiple Providers to try.

        if ($verbosity) {
          $writer->colors("<error>Quiet Error</end>: {$e->getMessage()}", true);
        }
      }

      if ($info) break;
    }

    if (!$info) {
      throw new \Error("Could not find a package by the name {$package}");
    }

    foreach($info as $key => $value) {
      $writer->colors("<info_key>$key</end>: <info_value>$value</end>\n");
    }
  }
}
?>