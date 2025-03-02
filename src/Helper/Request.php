<?php
namespace Fetcher\Helper;

class Request {
  static $auth = array();
  static $header = array();

  static function apply_auth_header($url) {
    $header = self::$header;

    $url_parts = parse_url($url);

    if ($url_parts && isset($url_parts['host'])) {
      $host = $url_parts['host'];

      if (isset(self::$auth[$host])) {
        $host_auth = self::$auth[$host];
        $username = null;
        $password = null;

        if (isset($host_auth['username'])) {
          $username = $host_auth['username'];
        }

        if (isset($host_auth['password'])) {
          $password = $host_auth['password'];
        }

        if ($username && $password) {
          $header[] = "Authorization: {$username} {$password}";
        }
      }
    }

    return $header;
  }

  static function get_json($url) {
    $response = self::make_request($url);
    $json = @json_decode($response, true);

    if ($json === null) {
      throw new \Error("$url did not return valid JSON");
    }

    return $json;
  }

  static function get_status_code($http_response_header) {
    $status_line = $http_response_header[0];
    preg_match('{HTTP\/\S*\s(\d{3})}', $status_line, $match);
    $status = $match[1];
    return $status;
  }

  static function make_request($url) {
    global $version;

    $header = self::apply_auth_header($url);

    $context = stream_context_create(array(
      'http' => array(
        'header'      => $header,
        'user_agent'  => 'Fetcher v' . $version
      )
    ));

    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
      if (substr($url, 4) === 'http') {
        $status_code = self::get_status_code($http_response_header);
        throw new \Error("$url returned a $status_code status code");
      } else {
        throw new \Error("Could not load $url");
      }
    }

    return $response;
  }
}
?>