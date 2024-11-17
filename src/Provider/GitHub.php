<?php
namespace Fetcher\Provider;

use \Composer\Semver\Semver;
use \Fetcher\Helper\Format;
use \Fetcher\Helper\Request;
use \Fetcher\Version;

class GitHub extends Provider {
  static $archive_exclude = '/\.github/i';
  static $archive_strip = 1;
  static $api_base = 'https://api.github.com';
  static $download_base = 'https://github.com';
  public $package_data;
  private $git_branches_data;
  private $git_commits_data;
  private $git_tags_data;

  private function get_alt_download_url($version_name, $package_name, $package_author) {
    $alt_download_url = false;

    if (Format::is_version_string($version_name)) {
      $alt_download_url = self::get_download_url("v{$version_name}", $package_name, $package_author);
    } elseif (Format::is_prefixed_version_string($version_name)) {
      $alt_download_url = self::get_download_url(substr($version_name, 1), $package_name, $package_author);
    }

    return $alt_download_url;
  }

  private function get_branches_data($package_name, $package_author) {
    if (!$this->git_branches_data) {
      $url = Format::build_url(self::$api_base, 'repos', $package_author, $package_name, 'branches');
      $this->git_branches_data = Request::get_json($url);
    }

    return $this->git_branches_data;
  }

  private function get_commits_data($package_name, $package_author) {
    if (!$this->git_commits_data) {
      $url = Format::build_url(self::$api_base, 'repos', $package_author, $package_name, 'commits');
      $this->git_commits_data = Request::get_json($url);
    }

    return $this->git_commits_data;
  }

  public function get_download_url($version_name, $package_name, $package_author) {
    $download_url_parts = array();
    $version_type = self::get_version_type($version_name);

    switch($version_type) {
      case 'branch':
        $download_url_parts = array('archive', 'refs', 'heads', substr($version_name, 4));
        break;
      case 'commit':
        $download_url_parts = array('archive', substr($version_name, 1));
        break;
      case 'tag':
        $version_name = preg_replace('/^tag-/', '', $version_name);
        $download_url_parts = array('archive', 'refs', 'tags', $version_name);
        break;
    }

    $download_url_base = array(self::$download_base, $package_author, $package_name);
    $download_url_parts = array_merge($download_url_base, $download_url_parts);

    return Format::build_url($download_url_parts) . '.zip';
  }

  public function get_latest_version_name($package_name, $package_author) {
    $tags_data = self::get_tags_data($package_name, $package_author);
    $latest_tag_data = reset($tags_data);
    return $latest_tag_data['name'];
  }

  public function get_package_data($package_name, $package_author) {
    if (!$this->package_data) {
      $url = Format::build_url(self::$api_base, 'repos', $package_author, $package_name);
      $this->package_data = Request::get_json($url);
    }

    return $this->package_data;
  }

  public function get_package_info($package_name, $package_author) {
    $package_data = self::get_package_data($package_name, $package_author);
    $latest_version_name = self::get_latest_version_name($package_name, $package_author);

    return array(
      'name'            => $package_data['name'],
      'description'     => $package_data['description'],
      'author'          => $package_data['owner']['login'],
      'latest_version'  => $latest_version_name,
      'homepage'        => $package_data['homepage'] ? $package_data['homepage'] : $package_data['html_url'],
      'license'         => $package_data['license']['name'],
      'provider'        => 'github'
    );
  }

  private function get_tags_data($package_name, $package_author) {
    if (!$this->git_tags_data) {
      $url = Format::build_url(self::$api_base, 'repos', $package_author, $package_name, 'tags');
      $this->git_tags_data = Request::get_json($url);
    }

    return $this->git_tags_data;
  }

  public function get_version($version_name, $package_name, $package_author) {
    if (strtolower($version_name) === 'latest') {
      return self::get_latest_version($package_name, $package_author);
    } else if (Format::is_version_range($version_name)) {
      $version_names = self::get_version_names($package_name, $package_author);

      $version_names = array_filter($version_names, function($ver_name) {
        return Format::is_version_string($ver_name) || Format::is_prefixed_version_string($ver_name);
      });

      $valid_versions = Semver::satisfiedBy($version_names, $version_name);
      $version_names = Semver::rsort($valid_versions);
      $version_name = reset($version_names);
    }

    $download_url = self::get_download_url($version_name, $package_name, $package_author);

    if ($alt_download_url = self::get_alt_download_url($version_name, $package_name, $package_author)) {
      $download_url = array($download_url, $alt_download_url);
    }

    return new Version($version_name, $download_url);
  }

  public function get_version_names($package_name, $package_author) {
    $tags_data = self::get_tags_data($package_name, $package_author);
    $tag_names = array_column($tags_data, 'name');

    $version_names = array_filter($tag_names, function($tag_name) {
      return Format::is_version_string($tag_name) || Format::is_prefixed_version_string($tag_name);
    });

    $version_names = Semver::rsort($version_names);

    $tag_names = array_diff($tag_names, $version_names);

    return array_merge($version_names, $tag_names);
  }

  private function get_version_type($version_name) {
    $type = 'tag';

    if (substr($version_name, 0, 1) === '#') {
      $type = 'commit';
    } elseif (substr($version_name, 0, 4) === 'dev-') {
      $type = 'branch';
    }

    return $type;
  }

  public function get_versions($package_name, $package_author) {
    $tag_names = self::get_version_names($package_name, $package_author);

    $branches_data = $this->get_branches_data($package_name, $package_author);
    $branch_names = array_map(function($b_name) {
      return "dev-{$b_name}";
    }, array_column($branches_data, 'name'));
  
    $commits_data = $this->get_commits_data($package_name, $package_author);
    $commit_names = array_map(function($c_name) {
      return '#' . substr($c_name, 0, 7);
    }, array_column($commits_data, 'sha'));

    $version_names = array_merge($tag_names, $branch_names, $commit_names);

    return array_map(function($version_name) use($package_name, $package_author) {
      $download_url = self::get_download_url($version_name, $package_name, $package_author);
      return new Version($version_name, $download_url);
    }, $version_names);
  }

  public function validate_parameters($package_name, $package_author) {
    if (!$package_author) {
      throw new \Error('Package author is required for GitHub packages');
    }
  }
}
?>