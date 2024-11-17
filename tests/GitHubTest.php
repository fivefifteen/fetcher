<?php
use \PHPUnit\Framework\TestCase;
use \Fetcher\Helper\Request;
use \Fetcher\Provider\GitHub as GitHubProvider;

require_once('inc/helpers.php');

class GitHubTest extends TestCase {
  protected function setUp(): void {
    $mock = \Mockery::mock('alias:' . Request::class);
    $mocked_data = get_github_test_data();

    $mock
      ->shouldReceive('get_json')
      ->with('https://api.github.com/repos/kodie/md5-file')
      ->andReturn($mocked_data['info']);

    $mock
      ->shouldReceive('get_json')
      ->with('https://api.github.com/repos/kodie/md5-file/branches')
      ->andReturn($mocked_data['branches']);

    $mock
      ->shouldReceive('get_json')
      ->with('https://api.github.com/repos/kodie/md5-file/commits')
      ->andReturn($mocked_data['commits']);

    $mock
      ->shouldReceive('get_json')
      ->with('https://api.github.com/repos/kodie/md5-file/tags')
      ->andReturn($mocked_data['tags']);
  }

  public function tearDown(): void {
    \Mockery::close();
  }

  public function testGetDownloadUrl1() {
    $provider = new GitHubProvider;
    $download_url = $provider->get_download_url('dev-master', 'md5-file', 'kodie');

    $this->assertSame('https://github.com/kodie/md5-file/archive/refs/heads/master.zip', $download_url);
  }

  public function testGetDownloadUrl2() {
    $provider = new GitHubProvider;
    $download_url = $provider->get_download_url('#f4ed4ca', 'md5-file', 'kodie');

    $this->assertSame('https://github.com/kodie/md5-file/archive/f4ed4ca.zip', $download_url);
  }

  public function testGetDownloadUrl3() {
    $provider = new GitHubProvider;
    $download_url = $provider->get_download_url('3.2.3', 'md5-file', 'kodie');

    $this->assertSame('https://github.com/kodie/md5-file/archive/refs/tags/3.2.3.zip', $download_url);
  }

  public function testGetDownloadUrl4() {
    $provider = new GitHubProvider;
    $download_url = $provider->get_download_url('tag-v3.2.3', 'md5-file', 'kodie');

    $this->assertSame('https://github.com/kodie/md5-file/archive/refs/tags/v3.2.3.zip', $download_url);
  }

  public function testGetGetLatestVersionName1() {
    $provider = new GitHubProvider;
    $version_name = $provider->get_latest_version_name('md5-file', 'kodie');

    $this->assertSame('v5.0.0', $version_name);
  }

  public function testGetVersion1() {
    $provider = new GitHubProvider;
    $version = $provider->get_version('3.1.1', 'md5-file', 'kodie');

    $this->assertSame('3.1.1', $version->name);
    $this->assertSame('https://github.com/kodie/md5-file/archive/refs/tags/3.1.1.zip', $version->download_url[0]);
    $this->assertSame('https://github.com/kodie/md5-file/archive/refs/tags/v3.1.1.zip', $version->download_url[1]);
  }

  public function testGetVersion2() {
    $provider = new GitHubProvider;
    $version = $provider->get_version('v3.2.3', 'md5-file', 'kodie');

    $this->assertSame('v3.2.3', $version->name);
    $this->assertSame('https://github.com/kodie/md5-file/archive/refs/tags/v3.2.3.zip', $version->download_url[0]);
    $this->assertSame('https://github.com/kodie/md5-file/archive/refs/tags/3.2.3.zip', $version->download_url[1]);
  }

  public function testGetVersions1() {
    $provider = new GitHubProvider;
    $versions = $provider->get_versions('md5-file', 'kodie');

    $this->assertSame('#e8201ed', $versions[42]->name);
    $this->assertSame('https://github.com/kodie/md5-file/archive/refs/heads/v2.zip', $versions[15]->download_url);
  }
}
?>