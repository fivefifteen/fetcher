<?php
namespace Fetcher\Helper;

class Format {
  static function build_path(...$path_parts) {
    $parts = array();

    array_walk_recursive($path_parts, function($part) use(&$parts) {
      $parts[] = trim($part, DIRECTORY_SEPARATOR);
    });

    return implode(DIRECTORY_SEPARATOR, $parts);
  }

  static function build_url(...$url_parts) {
    $parts = array();

    array_walk_recursive($url_parts, function($part) use(&$parts) {
      $parts[] = trim($part, '/');
    });

    return implode('/', $parts);
  }

  static function get_protected_value(object $obj, string $name) {
    $arr = (array) $obj;
    $prefix = chr(0) . '*' . chr(0);
    return $arr[$prefix . $name];
  }

  static function is_prefixed_version_string(string $str) {
    return strtolower(substr($str, 0, 1)) === 'v' && self::is_version_string(substr($str, 1));
  }

  static function is_version_range(string $str) {
    if (preg_match('/^[\<\>\~\^\*]/', $str) !== 0) {
      return true;
    }

    $two = array_values(array_filter(array_map('trim', preg_split('/[\-\|]/', $str))));
    if (count($two) === 2 && self::is_version_string($two[0]) && self::is_version_string($two[1])) {
      return true;
    }

    return false;
  }

  static function is_version_string(string $str) {
    return preg_match('#^(\d+\.)?(\d+\.)?(\d+)(-[a-z0-9]+)?$#i', $str) !== 0;
  }

  static function parse_comma_list(string|array|null $str) {
    if (is_array($str)) return $str;
    if (empty($str)) return array();
    return array_map('trim', explode(',', (string) $str));
  }

  static function parse_file_info_list(array $list) {
    return array_map(function ($fileinfo) {
      if ($fileinfo instanceof \splitbrain\PHPArchive\FileInfo) {
        $file = array();

        foreach((array) $fileinfo as $key => $value) {
          $fixed_key = preg_replace('/^' . chr(0) . '\*' . chr(0) . '/', '', $key);
          $file[$fixed_key] = $value;
        }
      }

      return $file;
    }, $list);
  }

  static function parse_package_string(string $package_str, string $key_str = null) {
    $name = null;
    $author = null;
    $provider = null;
    $version = 'latest';
    $alias_name = null;
    $alias_author = null;

    preg_match("/^\[([^\)]+)\]/", $package_str, $matches);

    if ($matches) {
      $parts = explode('/', $matches[1], 2);

      if (count($parts) === 2) {
        $alias_author = $parts[0];
        $alias_name = $parts[1];
      } else {
        $alias_name = $parts[0];
      }

      $package_str = substr($package_str, strlen($matches[0]));
    }

    preg_match("/^(?:([^:]*):)?(.*)$/", $package_str, $matches);

    if ($matches) {
      $provider = $matches[1];

      if ($provider === 'http' || $provider === 'https') {
        $provider = 'url';
      } else {
        $package_str = $matches[2];
      }
    }

    if (!in_array(strtolower($provider), array('url', 'file'))) {
      preg_match("/^(?:([^:\/]*)\/)?(.*)$/", $package_str, $matches);

      if ($matches) {
        $author = $matches[1];
        $package_str = $matches[2];
      }
    }

    if (in_array(strtolower($provider), array('url', 'file'))) {
      $name = $package_str;

      if (!$alias_name) {
        $alias_name = basename($name);
      }
    } elseif (
      self::is_version_string($package_str) ||
      self::is_prefixed_version_string($package_str) ||
      self::is_version_range($package_str) ||
      substr($package_str, 0, 1) === '#' ||
      substr($package_str, 0, 4) === 'dev-' ||
      substr($package_str, 0, 4) === 'tag-' ||
      $package_str === 'latest'
    ) {
      $version = $package_str;
    } else {
      preg_match("/^([^:\/@\s]+)@?(.*)$/", $package_str, $matches);

      if ($matches) {
        $name = $matches[1];

        if ($matches[2]) {
          $version = $matches[2];
        }
      }
    }

    if ($key_str) {
      preg_match("/^(?:([^:\/]*)\/)?(.*)$/", $key_str, $matches);

      if ($matches) {
        if ($name && $name !== $matches[2]) {
          $alias_name = $matches[2];

          if ($author !== $matches[1]) {
            $alias_author = $matches[1];
          }
        } else {
          $name = $matches[2];

          if (!$author) {
            $author = $matches[1];
          }
        }
      }
    }

    $info = array(
      'name'          => $name ? $name : null,
      'author'        => $author ? $author : null,
      'provider'      => $provider ? $provider : null,
      'version'       => $version ? $version : null,
      'alias_name'    => $alias_name ? $alias_name : null,
      'alias_author'  => $alias_author ? $alias_author : null
    );

    return $info;
  }

  static function parse_packages_list(array|string $packages_list) {
    $package_info_list = array();

    if (is_string($packages_list)) {
      $packages_list = explode(' ', $packages_list);
    }

    foreach($packages_list as $name => $version) {
      $name = is_numeric($name) ? null : $name;
      $info = self::parse_package_string($version, $name);
      $package_info_list[] = $info;
    }

    return $package_info_list;
  }
}
?>