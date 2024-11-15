<?php
namespace Fetcher\Helper;

class Request {
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

    $context = stream_context_create(array(
      'http' => array(
        'user_agent' => 'Fetcher v' . $version
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