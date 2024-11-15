<?php
use \PHPUnit\Framework\TestCase;
use \Fetcher\Helper\Format;
use \Fetcher\Provider\Url as UrlProvider;

class UrlTest extends TestCase {
  protected function setUp(): void {}

  public function testGetDownloadUrl() {
    $provider = new UrlProvider;
    $download_url = $provider->get_download_url('anything-can-be-entered-here', 'https://example.com/download.zip');

    $this->assertSame('https://example.com/download.zip', $download_url);
  }

  public function testGetGetLatestVersionName1() {
    $provider = new UrlProvider;
    $version_name = $provider->get_latest_version_name('https://example.com/download.zip');

    $this->assertSame('latest', $version_name);
  }

  public function testGetVersion1() {
    $provider = new UrlProvider;
    $version = $provider->get_version('latest', 'https://example.com/download.zip');

    $this->assertSame('latest', $version->name);
    $this->assertSame('https://example.com/download.zip', $version->download_url);
  }

  public function testGetVersions1() {
    $provider = new UrlProvider;
    $versions = $provider->get_versions('https://example.com/download.zip');

    $this->assertSame(1, count($versions));
    $this->assertSame('latest', $versions[0]->name);
    $this->assertSame('https://example.com/download.zip', $versions[0]->download_url);
  }
}
?>