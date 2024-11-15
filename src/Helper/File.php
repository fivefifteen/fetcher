<?php
namespace Fetcher\Helper;

use \Fetcher\Helper\Request;
use \splitbrain\PHPArchive\Tar;
use \splitbrain\PHPArchive\Zip;

class File {
  static function count_files($dir) {
    if (is_dir($dir)) {
      return count(array_diff(scandir($dir), array('.', '..')));
    }
  }

  static function create_directory($dir) {
    if (!is_dir($dir)) {
      return mkdir($dir, 0777, true);
    }
  }

  static function delete($file_or_dir) {
    if (is_dir($file_or_dir)) {
      return self::delete_directory($file_or_dir);
    } elseif (is_file($file_or_dir)) {
      return self::delete_file($file_or_dir);
    }
  }
 
  static function delete_directory($dir) {
    if (!is_dir($dir)) return false;

    $cwd = getcwd();
    $realpath = realpath($dir);
    if (!$dir || $realpath === $cwd || !str_starts_with($realpath, $cwd)) {
      throw new \Error("Safety net triggered while trying to delete {$dir}");
      exit(1);
    }

    $files = glob(preg_replace('/(\*|\?|\[)/', '[$1]', $dir) . '/{,.}*', GLOB_BRACE);

    foreach ($files as $file) {
      if ($file == $dir . '/.' || $file == $dir . '/..') continue;
      is_dir($file) ? self::delete_directory($file) : self::delete_file($file);
    }

    return @rmdir($dir);
  }

  static function delete_file($file) {
    if (is_file($file)) {
      return @unlink($file);
    }
  }

  static function download($url, $save_path) {
    $contents = Request::make_request($url);
    $bytes = @file_put_contents($save_path, $contents);

    if ($bytes === false) {
      throw new \Error("An error occured while attempting to write to $save_path");
    }

    return $bytes;
  }

  static function extract($file, $destination, $strip, $exclude, $include) {
    $archive = self::get_archive_instance($file);
    self::create_directory($destination);
    $archive->open($file);
    return $archive->extract($destination, $strip, $exclude, $include);
  }

  static function get_archive_file_list($file) {
    $archive = self::get_archive_instance($file);
    $archive->open($file);
    $contents = $archive->contents();
    return Format::parse_file_info_list($contents);
  }

  static function get_archive_instance($file) {
    switch(pathinfo($file, PATHINFO_EXTENSION)) {
      case 'gz':
      case 'tgz':
      case 'bz2':
      case 'tbz':
        return new Tar();
      case 'zip':
        return new Zip();
      default:
        throw new \Error("Invalid archive file $file");
    }
  }

  static function is_archive_file($file) {
    $ext = pathinfo($file, PATHINFO_EXTENSION);
    return in_array($ext, array('gz', 'tgz', 'bz2', 'tbz', 'zip'));
  }
}
?>