<?php
function get_api_test_data($provider_type, $data_types) {
  $mocked_data_path = implode(DIRECTORY_SEPARATOR, array('tests', 'data', 'api', $provider_type));
  $mocked_data = array();

  foreach($data_types as $mocked_data_type) {
    $mocked_data_file = $mocked_data_path . DIRECTORY_SEPARATOR . $mocked_data_type . '.json';
    $mocked_data[$mocked_data_type] = json_decode(file_get_contents($mocked_data_file), true);
  }

  return $mocked_data;
}

function get_github_test_data() {
  return get_api_test_data('github', array('info', 'branches', 'commits', 'tags'));
}

function get_npm_test_data() {
  return get_api_test_data('npm', array('info'));
}
?>