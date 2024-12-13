<?php
namespace Fetcher\Command;

use \Ahc\Cli\Output\Writer;
use \Fetcher\Package;
use \Fetcher\Helper\File;
use \Fetcher\Helper\Format;
use \Fetcher\Helper\Request;

class Install extends Command {
  static $defaults = array(
    'config_path'       => 'fetch.json',
    'extensions'        => null,
    'extract'           => true,
    'install_directory' => 'fetched',
    'fresh_start'       => false,
    'ignore_errors'     => false,
    'packages'          => null,
    'providers'         => 'github,npm',
    'quiet'             => false,
    'save_changes'      => false,
    'verbosity'         => 0,
    'working_directory' => '.'
  );

  public function __construct() {
    parent::__construct('install', 'Installs defined packages. If no packages are defined, packages listed in the dependencies section of the config JSON file will be used.');

    $this
      ->argument('[packages...]', 'Packages to install')
      ->option('-c --config [path]', 'Path to a config JSON file', 'strval', self::$defaults['config_path'])
      ->option('-d --install-directory [path]', 'Directory path where packages should be installed to', 'strval', self::$defaults['install_directory'])
      ->option('-e --extensions', 'A comma seperated list of extensions to extract from the packages')
      ->option('-f --fresh-start', 'Deletes the entire fetched directory before running installation')
      ->option('-i --ignore-errors', 'Ignore any errors that may occur and continue with installation')
      ->option('-p --providers [providers]', 'The repository providers to search and what order to search them in', 'strval', self::$defaults['providers'])
      ->option('-q --quiet', 'Run but don\'t output anything in the terminal')
      ->option('-s --save', 'Saves the installed packages to the config JSON file')
      ->option('-w --working-directory', 'Sets the working directory that all paths will be relative to', 'strval', self::$defaults['working_directory'])
      ->option('-x --no-extract', 'Extract downloaded package archive files')
      ->usage(
        '<bold>  $0 install</end> <comment>--config content/themes/my-theme/compile.json</end> ## Install packages defined in a custom config file<eol/>' .
        '<bold>  $0 install</end> <comment>--providers npm include-media</end> ## Install the latest version of the `include-media` package from npm<eol/>' .
        '<bold>  $0 install</end> <comment>--providers npm include-media@1.4.10</end> ## Install a specific version of the `include-media` package from npm<eol/>' .
        '<bold>  $0 i</end> <comment>-p github eduardoboucas/include-media</end> ## Install the latest version of the `include-media` package from GitHub<eol/>'
      );
  }

  public function execute() {
    $this->run(array(
      'config_path'       => $this->config,
      'install_directory' => $this->installDirectory,
      'extensions'        => $this->extensions,
      'extract'           => $this->extract,
      'fresh_start'       => $this->freshStart,
      'ignore_errors'     => $this->ignoreErrors,
      'packages'          => $this->packages,
      'providers'         => $this->providers,
      'quiet'             => $this->quiet,
      'save_changes'      => $this->save,
      'verbosity'         => $this->verbosity,
      'working_directory' => $this->workingDirectory
    ));
  }

  public function run($args) {
    extract(array_merge(self::$defaults, $args));

    $writer = new Writer();
    $errors = false;

    $config_path = Format::build_path($working_directory, $config_path);
    $config = self::load_config($config_path, $quiet);

    if (!$packages && isset($config['dependencies'])) {
      $packages = $config['dependencies'];
    }

    if (isset($config['config'])) {
      $imported_config = $config['config'];

      if (!$install_directory && isset($imported_config['install_directory'])) {
        $install_directory = $imported_config['install_directory'];
      }

      if (!$extensions && isset($imported_config['extensions'])) {
        $extensions = $imported_config['extensions'];
      }

      if (isset($imported_config['providers'])) {
        $providers = $imported_config['providers'];
      }

      if (isset($imported_config['extract'])) {
        $extract = $imported_config['extract'];
      }
    }

    $packages = Format::parse_packages_list($packages ?: array());
    $providers = Format::parse_comma_list($providers);
    $extensions = Format::parse_comma_list($extensions);

    if (!$packages) {
      throw new \Error('No packages were defined');
    }

    $directory = Format::build_path($working_directory, $install_directory);

    if ($fresh_start) {
      File::delete_directory($directory);
    }

    $temp_dir = Format::build_path($directory, '.tmp');

    File::delete_directory($temp_dir);
    File::create_directory($temp_dir);

    $installed_packages = array();

    foreach($packages as $pkg_idx => $package_info) {
      if (!$package_info['name']) {
        if (!$quiet) {
          $writer->colors("<warn>Warning</end>: <info>An unnamed package was skipped at index</end> <subject>{$pkg_idx}</end>", true);
        }

        continue;
      }

      $package_string = ($package_info['author'] || $package_info['alias_author'] ? ($package_info['author'] ?: $package_info['alias_author']) . '/' : null) . ($package_info['alias_name'] ?: $package_info['name']);
      $writer_package_str = "<pkg_name>{$package_string}</end>";
      $writer_version_str = null;
      $package_providers = $providers;
      $download_destination = $temp_dir;
      $download_filename = $package_info['name'];
      $download_path = null;
      $download_url = null;
      $package = null;
      $version = null;

      if ($package_info['alias_name']) {
        $download_filename = $package_info['alias_name'];
      }

      if ($package_info['alias_author']) {
        $download_filename = "{$package_info['alias_author']}-$download_filename";
      } elseif ($package_info['author']) {
        $download_filename = "{$package_info['author']}-$download_filename";
      }

      $download_destination = Format::build_path($download_destination, $download_filename);  

      if ($download_dest_ext = pathinfo($download_destination, PATHINFO_EXTENSION)) {
        $download_destination = substr($download_destination, 0, -(strlen($download_dest_ext) + 1));
      }

      if ($package_info['provider']) {
        $package_providers = array($package_info['provider']);
      }

      foreach($package_providers as $provider) {
        try {
          $package = new Package($provider, $package_info['name'], $package_info['author']);
          $version = $package->get_version($package_info['version']);

          if (!$version) continue;

          $writer_version_str = "(<pkg_version>{$version->name}</end>)";
          $download_path = null;

          foreach((array) $version->download_url as $url) {
            $download_url = $url;
            $download_ext = pathinfo($download_url, PATHINFO_EXTENSION);
            $download_path = "{$download_destination}-{$version->name}.{$download_ext}";

            if (!$quiet) {
              $action = $provider === 'file' ? 'copying' : 'downloading';
              $action_msg = "{$writer_package_str} {$writer_version_str}: <from>{$download_url}</end> -> <to>{$download_path}</end>";
              Format::write_action($writer, $action, $action_msg);
            }

            try {
              File::download($download_url, $download_path);
            } catch (\Throwable $e) {
              $download_path = null;
              $download_url = null;

              // Errors here are ignored because there could be multiple URLs to try.
              // One failed download doesn't mean a failed installation.

              if (!$quiet && $verbosity) {
                $writer->colors("<error>Quiet Error</end>: {$e->getMessage()}", true);
              }
            }

            if ($download_path) break;
          }
        } catch (\Throwable $e) {
          $download_path = null;
          $download_url = null;

          // Errors here are ignored because there could be multiple Providers to try.
          // One failed Package/Provider initialization doesn't mean a failed installation.

          if (!$quiet && $verbosity) {
            $writer->colors("<error>Quiet Error</end>: {$e->getMessage()}", true);
          }
        }

        if ($download_path) break;
      }

      if ($download_path) {
        $destination_path = Format::build_path($directory);

        if ($package_info['alias_name']) {
          if ($package_info['alias_author']) {
            $destination_path = Format::build_path($destination_path, $package_info['alias_author']);
          }

          $destination_path = Format::build_path($destination_path, $package_info['alias_name']);
        } elseif ($package_info['name']) {
          if ($package_info['author']) {
            $destination_path = Format::build_path($destination_path, $package_info['author']);
          }

          $destination_path = Format::build_path($destination_path, $package_info['name']);
        }

        if ($extract && File::is_archive_file($download_path)) {
          if (in_array($provider, array('url', 'file')) && $destination_path_ext = pathinfo($destination_path, PATHINFO_EXTENSION)) {
            $destination_path = substr($destination_path, 0, -(strlen($destination_path_ext) + 1));
          }

          if (!$quiet) {
            $action_msg = "{$writer_package_str} {$writer_version_str}: <from>{$download_path}</end> -> <to>{$destination_path}</end>";
            Format::write_action($writer, 'extracting', $action_msg);
          }

          File::delete($destination_path);
          File::create_directory($destination_path);

          $archive_strip = $package->provider_instance::$archive_strip;
          $archive_exclude = $package->provider_instance::$archive_exclude;
          $archive_include = '';

          if ($extensions) {
            $archive_include = '/.*\.(' . implode('|', $extensions) . ')$/i';
          }

          if (!$archive_strip) {
            $archive_files = File::get_archive_file_list($download_path);
            $archive_files_count = count($archive_files);

            $top_level_directories_count = count(array_unique(array_map(function ($file) {
              return strtok($file['path'], DIRECTORY_SEPARATOR);
            }, $archive_files)));

            if ($archive_files_count > 1 && $top_level_directories_count === 1) {
              $archive_strip = 1;
            }
          }

          $extracted = File::extract($download_path, $destination_path, $archive_strip, $archive_exclude, $archive_include);

          if (
            count($extracted) === 1 &&
            $package_info['alias_name'] &&
            ($alias_ext = pathinfo($package_info['alias_name'], PATHINFO_EXTENSION)) &&
            ($extracted_file_name = Format::get_protected_value($extracted[0], 'path')) &&
            $alias_ext === ($extracted_file_ext = pathinfo($extracted_file_name, PATHINFO_EXTENSION))
          ) {
            $temp_destination_path = "{$destination_path}_tmp";
            $temp_file_path = Format::build_path($temp_destination_path, $extracted_file_name);
            $new_file_path = Format::build_path(dirname($destination_path), $package_info['alias_name']);

            rename($destination_path, $temp_destination_path);
            rename($temp_file_path, $new_file_path);

            File::delete_directory($temp_destination_path);
          }
        } else {
          if (!($destination_ext = pathinfo($destination_path, PATHINFO_EXTENSION))) {
            if ($download_ext = pathinfo($download_path, PATHINFO_EXTENSION)) {
              $destination_path .= ".{$download_ext}";
            }
          }

          $destination_dir = dirname($destination_path);

          File::delete($destination_path);
          File::create_directory($destination_dir);

          if (!$quiet) {
            $action_msg = "{$writer_package_str} {$writer_version_str}: <from>{$download_path}</end> -> <to>{$destination_path}</end>";
            Format::write_action($writer, 'moving', $action_msg);
          }

          rename($download_path, $destination_path);
        }

        $installed_packages[$package_string] = "{$package->provider}:{$version->name}";
      } else {
        $errors = true;

        if (!$quiet) {
          $writer->colors("<error>Error</end>: Unable to install {$package_string}", true);
        }

        if (!$ignore_errors) {
          break;
        }
      }
    }

    if ($save_changes && $installed_packages) {
      if (!$quiet) {
        $writer->colors("<info>Updating</end> <file>{$config_path}</end>", true);
      }

      if ($config) {
        $config_json = Request::get_json($config_path) ?: array();
      } else {
        $config = array();
        $config_json = array();
      }

      $existing_dependencies = isset($config['dependencies']) ? $config['dependencies'] : array();
      $config['dependencies'] = array_merge($existing_dependencies, $installed_packages);
      $config_json['fetcher'] = $config;

      file_put_contents($config_path, json_encode($config_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    File::delete_directory($temp_dir);

    $completion_msg = 'Done!';

    if ($errors) {
      $completion_msg .= ' <warn>...but with errors</end>';

      if (!File::count_files($directory)) {
        File::delete_directory($directory);
      }
    }

    if (!$quiet) {
      $writer->colors($completion_msg, true);
    }
  }
}
?>