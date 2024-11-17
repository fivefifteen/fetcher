<?php
namespace Fetcher\Command;

use \Ahc\Cli\Output\Writer;
use \Fetcher\Package;
use \Fetcher\Helper\Format;

class Versions extends Command {
  static $defaults = array(
    'limit'     => 20,
    'package'   => null,
    'providers' => 'github,npm',
    'verbosity' => 0
  );

  public function __construct() {
    parent::__construct('versions', 'Returns available versions for a package');

    $this
      ->argument('<package>', 'Package to get info for')
      ->option('-l --limit [limit]', 'The maximum number of package versions to show', 'strval', self::$defaults['limit'])
      ->option('-p --providers [providers]', 'The repository providers to search and what order to search them in', 'strval', self::$defaults['providers'])
      ->usage(
        '<bold>  $0 versions</end> <comment>include-media</end> ## Get the latest 20 versions of the `include-media` package<eol/>' .
        '<bold>  $0 v</end> <comment>-l 5 -p github eduardoboucas/include-media</end> ## Get the latest 5 versions of the `include-media` package available on GitHub (this includes non-tagged commits)<eol/>'
      );
  }

  public function execute() {
    $this->run(array(
      'limit'     => $this->limit,
      'package'   => $this->package,
      'providers' => $this->providers,
      'verbosity' => $this->verbosity
    ));
  }

  public function run($args) {
    extract(array_merge(self::$defaults, $args));

    $writer = new Writer();

    $versions = false;
    $package_args = Format::parse_package_string($package);

    if ($package_args['provider']) {
      $providers = array($package_args['provider']);
    } else {
      $providers = Format::parse_comma_list($providers);
    }

    foreach($providers as $provider) {
      $versions = false;

      try {
        $package_obj = new Package($provider, $package_args['name'], $package_args['author']);
        $versions = $package_obj->get_versions();
      } catch (\Throwable $e) {
        // Errors here are ignored because there could be multiple Providers to try.

        if ($verbosity) {
          $writer->colors("<red>Quiet Error</end>: {$e->getMessage()}", true);
        }
      }

      if ($versions) break;
    }

    if (!$versions) {
      throw new \Error("Could not find any versions for a package by the name {$package}");
    }

    $writer->table(array_map(function($version) {
      $version_name = $version->name;
      $download_url = $version->download_url;

      if (is_array($download_url)) {
        $download_url = $download_url[0];
      }

      return array(
        'Version'       => $version_name,
        'Download URL'  => $download_url
      );
    }, array_slice($versions, 0, intval($limit))));
  }
}
?>