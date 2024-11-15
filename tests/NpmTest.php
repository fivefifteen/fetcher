<?php
use \PHPUnit\Framework\TestCase;
use \Fetcher\Helper\Request;
use \Fetcher\Provider\Npm as NpmProvider;

require_once('inc/helpers.php');

class NpmTest extends TestCase {
  protected function setUp(): void {
    $mock = \Mockery::mock('alias:' . Request::class);
    $mocked_data = get_npm_test_data();

    $mock
      ->shouldReceive('get_json')
      ->with('https://registry.npmjs.org/md5-file')
      ->andReturn($mocked_data['info']);
  }

  public function tearDown(): void {
    \Mockery::close();
  }

  public function testGetDownloadUrl1() {
    $provider = new NpmProvider;
    $download_url = $provider->get_download_url('1.1.5', 'md5-file');

    $this->assertSame('https://registry.npmjs.org/md5-file/-/md5-file-1.1.5.tgz', $download_url);
  }

  public function testGetDownloadUrl2() {
    $provider = new NpmProvider;
    $download_url = $provider->get_download_url('1.1.5', 'md5-file', 'kodie');

    $this->assertSame('https://registry.npmjs.org/@kodie/md5-file/-/@kodie/md5-file-1.1.5.tgz', $download_url);
  }

  public function testGetGetLatestVersionName1() {
    $provider = new NpmProvider;
    $version_name = $provider->get_latest_version_name('md5-file');

    $this->assertSame('5.0.0', $version_name);
  }

  public function testGetVersion1() {
    $provider = new NpmProvider;
    $version = $provider->get_version('3.1.1', 'md5-file');

    $this->assertSame('3.1.1', $version->name);
    $this->assertSame('https://registry.npmjs.org/md5-file/-/md5-file-3.1.1.tgz', $version->download_url);
  }

  public function testGetVersions1() {
    $provider = new NpmProvider;
    $versions = $provider->get_versions('md5-file');

    $this->assertSame('1.1.7', $versions[21]->name);
    $this->assertSame('https://registry.npmjs.org/md5-file/-/md5-file-2.0.2.tgz', $versions[15]->download_url);
  }
}
?>