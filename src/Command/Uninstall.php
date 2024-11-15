<?php
namespace Fetcher\Command;

use \Ahc\Cli\IO\Interactor;
use \Ahc\Cli\Output\Writer;
use \Fetcher\Helper\File;
use \Fetcher\Helper\Format;

class Uninstall extends Command {
  static $defaults = array(
    'config_path'       => 'fetch.json',
    'install_directory' => 'fetched',
    'fresh_start'       => false,
    'packages'          => null,
    'quiet'             => false,
    'save_changes'      => false,
    'skip_prompts'      => false,
    'verbosity'         => 0,
    'working_directory' => '.'
  );

  public function __construct() {
    parent::__construct('uninstall', 'Uninstalls a package');

    $this
      ->argument('[packages...]', 'Packages to uninstall')
      ->option('-c --config [path]', 'Path to a config JSON file', 'strval', self::$defaults['config_path'])
      ->option('-d --install-directory [path]', 'Directory path where packages should be uninstalled from', 'strval', self::$defaults['install_directory'])
      ->option('-f --fresh-start', 'Deletes the entire fetched directory and optionally deletes all dependencies from fetch.json')
      ->option('-q --quiet', 'Run but don\'t output anything in the terminal (implies --skip-prompts)')
      ->option('-s --save', 'Saves the installed packages to the config JSON file')
      ->option('-w, --working-directory', 'Sets the working directory that all paths will be relative to', 'strval', self::$defaults['working_directory'])
      ->option('-y --skip-prompts', 'Skips the confirmation prompt and continues with deletion')
      ->usage(
        '<bold>  $0 uninstall</end> <comment>include-media</end> ## Uninstall the `include-media` package<eol/>' .
        '<bold>  $0 u</end> <comment>-c content/themes/my-theme/compile.json -s include-media</end> ## Uninstall the `include-media` package and remove it from a custom config file<eol/>'
      );
  }

  public function execute() {
    $this->run(array(
      'config_path'       => $this->config,
      'install_directory' => $this->installDirectory,
      'fresh_start'       => $this->freshStart,
      'packages'          => $this->packages,
      'quiet'             => $this->quiet,
      'save_changes'      => $this->save,
      'skip_prompts'      => $this->skipPrompts,
      'verbosity'         => $this->verbosity,
      'working_directory' => $this->workingDirectory
    ));
  }

  public function run($args) {
    extract(array_merge(self::$defaults, $args));

    $writer = new Writer();

    $config_path = Format::build_path($working_directory, $config_path);
    $config = self::load_config($config_path, $quiet);

    if (!$packages && isset($config['dependencies'])) {
      $packages = $config['dependencies'];
    }

    if (isset($config['settings']) && isset($config['settings']['fetcher'])) {
      $settings = $config['settings']['fetcher'];

      if (!$install_directory && isset($settings['install_directory'])) {
        $install_directory = $settings['install_directory'];
      }
    }

    $directory = Format::build_path($working_directory, $install_directory);

    if ($quiet) $skip_prompts = true;

    $temp_dir = Format::build_path($directory, '.tmp');
    File::delete_directory($temp_dir);

    $directories_to_delete = array();
    $packages_to_uninstall = array();

    if (!$fresh_start) {
      $packages = Format::parse_packages_list($packages);

      foreach($packages as $pkg_idx => $package_info) {
        if (!$package_info['name']) {
          if (!$quiet) {
            $writer->colors("<yellow>Warning</end>: An unnamed package was skipped at index {$pkg_idx}", true);
          }

          continue;
        }

        $package_string = ($package_info['author'] || $package_info['alias_author'] ? ($package_info['author'] ?: $package_info['alias_author']) . '/' : null) . ($package_info['alias_name'] ?: $package_info['name']);
        $package_directory = Format::build_path($directory, $package_string);
        $author_directory = null;

        if (is_dir($package_directory)) {
          if ($package_info['author']) {
            $author_directory = dirname($package_directory);
          }

          $packages_to_uninstall[] = array(
            'name'              => $package_string,
            'directory'         => $package_directory,
            'author_directory'  => $author_directory
          );
        } else {
          if (!$quiet) {
            $writer->colors("<yellow>Warning</end>: No directories found for {$package_string}", true);
          }
        }
      }

      $author_directories = array_filter(array_unique(array_column($packages_to_uninstall, 'author_directory')));

      foreach($author_directories as $author_directory) {
        $file_count = File::count_files($author_directory);
        $package_count = count(array_filter($packages_to_uninstall, function($package) use($author_directory) {
          return $package['author_directory'] === $author_directory;
        }));

        if ($file_count === $package_count) {
          $directories_to_delete[] = $author_directory;
        }
      }

      $directories_to_delete = array_reduce($packages_to_uninstall, function($directories, $pkg) {
        if (!$pkg['author_directory'] || ($pkg['author_directory'] && !in_array($pkg['author_directory'], $directories))) {
          $directories[] = $pkg['directory'];
        }
        return $directories;
      }, $directories_to_delete);

      $all_sub_directories = glob("$directory/**");
      $directory_comparisons = array_intersect($all_sub_directories, $directories_to_delete);

      $directories_to_delete_count = count($directories_to_delete);
      $all_sub_directories_count = count($all_sub_directories);
      $directory_comparisons_count = count($directory_comparisons);

      if ($directories_to_delete_count === $all_sub_directories_count && $all_sub_directories_count === $directory_comparisons_count) {
        $fresh_start = true;
      }
    }

    if ($fresh_start) {
      $directories_to_delete = array($directory);
    }

    $directories_to_delete = array_filter($directories_to_delete, 'is_dir');

    if ($directories_to_delete) {
      $confirm = false;

      if ($skip_prompts) {
        $confirm = true;
      } else {
        $interactor = new Interactor;
        $confirm_msg = "The following directories will be deleted:\n\n";
        $confirm_msg .= implode("\n", $directories_to_delete);
        $confirm_msg .= "\n\nAre you sure?";
        $confirm = $interactor->confirm($confirm_msg, 'n');
      }

      if ($confirm) {
        foreach($directories_to_delete as $dir) {
          if (!$quiet) {
            $writer->colors("<red>Deleting</end> {$dir}...", true);
          }

          File::delete_directory($dir);
        }
      } else {
        $directories_to_delete = array();
      }
    } else {
      if (!$quiet) {
        $writer->colors('Nothing to uninstall', true);
      }
    }

    if ($save_changes && $config) {
      if (!$quiet) {
        $writer->colors("Updating {$config_path}...", true);
      }

      $existing_dependencies = isset($config['dependencies']) ? $config['dependencies'] : array();
      $package_names = array_column($packages_to_uninstall, 'name');

      if ($fresh_start) {
        $package_names = array_keys($existing_dependencies);
      }

      foreach($package_names as $package_name) {
        if (isset($existing_dependencies[$package_name])) {
          unset($existing_dependencies[$package_name]);
        }
      }

      $config['dependencies'] = $existing_dependencies;

      file_put_contents($config_path, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

      if (empty($config['dependencies'])) {
        $config_key_count = count(array_keys($config));

        if (
          $fresh_start ||
          $config_key_count === 1 ||
          (
            $config_key_count === 2 &&
            isset($config['settings']) &&
            isset($config['settings']['fetcher']) &&
            count(array_keys($config['settings'])) === 1
          )
        ) {
          $confirm_config_delete = false;

          if ($skip_prompts) {
            $confirm_config_delete = true;
          } else {
            $interactor = new Interactor;
            $confirm_config_delete = $interactor->confirm("Delete {$config_path}?", 'n');
          }

          if ($confirm_config_delete) {
            if (!$quiet) {
              $writer->colors("<red>Deleting</end> {$config_path}...", true);
            }

            File::delete_file($config_path);
          } else {
            $save_changes = false;
          }
        }
      }
    }

    if (!$quiet && ($directories_to_delete || ($save_changes && $config))) {
      $writer->colors('Done!', true);
    }
  }
}
?>