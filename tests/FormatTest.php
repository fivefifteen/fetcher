<?php
use \PHPUnit\Framework\TestCase;
use \Fetcher\Helper\Format;

class FormatTest extends TestCase {
  protected function setUp(): void {}

  public function testPathBuilding1() {
    $url = Format::build_path('.', 'path', array('to', 'somewhere'), 'and', array('some/'), 'file.php');

    $this->assertSame('./path/to/somewhere/and/some/file.php', $url);
  }

  public function testUrlBuilding1() {
    $url = Format::build_url('https://example.com', 'path', array('to', 'somewhere'), 'and', array('some/'), 'file.php');

    $this->assertSame('https://example.com/path/to/somewhere/and/some/file.php', $url);
  }

  public function testCommaListParsing1() {
    $list = Format::parse_comma_list('some,words , in, a ,     list');

    $this->assertSame(array('some','words','in','a','list'), $list);
  }

  public function testPackagesListFormatting1() {
    $packages = array(
      'github:kodie/filebokz@^0.1',
      'md5-file',
      'something/somewhere'
    );

    $pkgs = Format::parse_packages_list($packages);

    $this->assertSame('md5-file', $pkgs[1]['name']);
  }

  public function testPackagesListFormatting2() {
    $packages = array(
      'kodie/filebokz'      => 'github:^0.1',
      'md5-file'            => 'latest',
      'something/somewhere' => '0.2.4'
    );

    $pkgs = Format::parse_packages_list($packages);

    $this->assertSame('somewhere', $pkgs[2]['name']);
  }

  public function testVersionStringChecking1() {
    $response = Format::is_version_string('1');

    $this->assertTrue($response);
  }

  public function testVersionStringChecking2() {
    $response = Format::is_version_string('1.2');

    $this->assertTrue($response);
  }

  public function testVersionStringChecking3() {
    $response = Format::is_version_string('1.2.3');

    $this->assertTrue($response);
  }

  public function testVersionStringChecking4() {
    $response = Format::is_version_string('1.2.3-beta1');

    $this->assertTrue($response);
  }

  public function testVersionStringChecking5() {
    $response = Format::is_version_string('nope');

    $this->assertFalse($response);
  }

  public function testVersionStringChecking_DontMatchRanges1() {
    $response = Format::is_version_string('^0.1');

    $this->assertFalse($response);
  }

  public function testVersionStringChecking_DontMatchRanges2() {
    $response = Format::is_version_string('1.0.1||2.0');

    $this->assertFalse($response);
  }

  public function testVersionStringChecking_DontMatchRanges3() {
    $response = Format::is_version_string('>=1.0.1||<2.0');

    $this->assertFalse($response);
  }

  public function testVersionRangeChecking1() {
    $response = Format::is_version_range('^0.1');

    $this->assertTrue($response);
  }

  public function testVersionRangeChecking2() {
    $response = Format::is_version_range('1.0.1||2.0');

    $this->assertTrue($response);
  }

  public function testVersionRangeChecking3() {
    $response = Format::is_version_range('>=1.0.1||<2.0');

    $this->assertTrue($response);
  }

  public function testVersionRangeChecking4() {
    $response = Format::is_version_range('1.0.1-2.0');

    $this->assertTrue($response);
  }

  public function testVersionRangeChecking5() {
    $response = Format::is_version_range('^1.0');

    $this->assertTrue($response);
  }

  public function testPrefixedVersionChecking1() {
    $response = Format::is_prefixed_version_string('v1.2.3');

    $this->assertTrue($response);
  }

  public function testPrefixedVersionChecking2() {
    $response = Format::is_prefixed_version_string('1.2.3');

    $this->assertFalse($response);
  }

  public function testVersionParsing1() {
    $info = Format::parse_package_string('github:kodie/filebokz@^0.1', 'filebokz');

    $this->assertSame('github', $info['provider']);
    $this->assertSame('kodie', $info['author']);
    $this->assertSame('filebokz', $info['name']);
    $this->assertSame('^0.1', $info['version']);
    $this->assertSame(null, $info['alias_name']);
    $this->assertSame(null, $info['alias_author']);
  }

  public function testVersionParsing2() {
    $info = Format::parse_package_string('github:1.2.4', 'kodie/package');

    $this->assertSame('github', $info['provider']);
    $this->assertSame('kodie', $info['author']);
    $this->assertSame('package', $info['name']);
    $this->assertSame('1.2.4', $info['version']);
    $this->assertSame(null, $info['alias_name']);
    $this->assertSame(null, $info['alias_author']);
  }

  public function testVersionParsing3() {
    $info = Format::parse_package_string('github:kodie/package@#d3eg34', 'not-kodie/not-package');

    $this->assertSame('github', $info['provider']);
    $this->assertSame('kodie', $info['author']);
    $this->assertSame('package', $info['name']);
    $this->assertSame('#d3eg34', $info['version']);
    $this->assertSame('not-package', $info['alias_name']);
    $this->assertSame('not-kodie', $info['alias_author']);
  }

  public function testVersionParsing4() {
    $info = Format::parse_package_string('github:dev-main', 'kodie/package');

    $this->assertSame('github', $info['provider']);
    $this->assertSame('kodie', $info['author']);
    $this->assertSame('package', $info['name']);
    $this->assertSame('dev-main', $info['version']);
    $this->assertSame(null, $info['alias_name']);
    $this->assertSame(null, $info['alias_author']);
  }

  public function testVersionParsing5() {
    $info = Format::parse_package_string('npm:md5-file@0.1.2', 'package');

    $this->assertSame('npm', $info['provider']);
    $this->assertSame(null, $info['author']);
    $this->assertSame('md5-file', $info['name']);
    $this->assertSame('0.1.2', $info['version']);
    $this->assertSame('package', $info['alias_name']);
    $this->assertSame(null, $info['alias_author']);
  }

  public function testVersionParsing6() {
    $info = Format::parse_package_string('npm:md5-file@0.1.2');

    $this->assertSame('npm', $info['provider']);
    $this->assertSame(null, $info['author']);
    $this->assertSame('md5-file', $info['name']);
    $this->assertSame('0.1.2', $info['version']);
    $this->assertSame(null, $info['alias_name']);
    $this->assertSame(null, $info['alias_author']);
  }

  public function testVersionParsing7() {
    $info = Format::parse_package_string('url:https://example.com/download.zip', 'something');

    $this->assertSame('url', $info['provider']);
    $this->assertSame(null, $info['author']);
    $this->assertSame('https://example.com/download.zip', $info['name']);
    $this->assertSame('latest', $info['version']);
    $this->assertSame('something', $info['alias_name']);
    $this->assertSame(null, $info['alias_author']);
  }

  public function testVersionParsing8() {
    $info = Format::parse_package_string('https://example.com/download2.zip', 'someone/something');

    $this->assertSame('url', $info['provider']);
    $this->assertSame(null, $info['author']);
    $this->assertSame('https://example.com/download2.zip', $info['name']);
    $this->assertSame('latest', $info['version']);
    $this->assertSame('something', $info['alias_name']);
    $this->assertSame('someone', $info['alias_author']);
  }

  public function testVersionParsing9() {
    $info = Format::parse_package_string('file:../../archive.tgz', 'something-else');

    $this->assertSame('file', $info['provider']);
    $this->assertSame(null, $info['author']);
    $this->assertSame('../../archive.tgz', $info['name']);
    $this->assertSame('latest', $info['version']);
    $this->assertSame('something-else', $info['alias_name']);
    $this->assertSame(null, $info['alias_author']);
  }

  public function testVersionParsing10() {
    $info = Format::parse_package_string('^4.0', 'person/package');

    $this->assertSame(null, $info['provider']);
    $this->assertSame('person', $info['author']);
    $this->assertSame('package', $info['name']);
    $this->assertSame('^4.0', $info['version']);
    $this->assertSame(null, $info['alias_name']);
    $this->assertSame(null, $info['alias_author']);
  }

  public function testVersionParsing11() {
    $info = Format::parse_package_string('npm:package@^4.0', 'person/my-package');

    $this->assertSame('npm', $info['provider']);
    $this->assertSame(null, $info['author']);
    $this->assertSame('package', $info['name']);
    $this->assertSame('^4.0', $info['version']);
    $this->assertSame('my-package', $info['alias_name']);
    $this->assertSame('person', $info['alias_author']);
  }

  public function testVersionParsing12() {
    $info = Format::parse_package_string('npm:latest', 'person/package');

    $this->assertSame('npm', $info['provider']);
    $this->assertSame('person', $info['author']);
    $this->assertSame('package', $info['name']);
    $this->assertSame('latest', $info['version']);
    $this->assertSame(null, $info['alias_name']);
    $this->assertSame(null, $info['alias_author']);
  }

  public function testVersionParsing13() {
    $info = Format::parse_package_string('[my-package]npm:package@latest');

    $this->assertSame('npm', $info['provider']);
    $this->assertSame(null, $info['author']);
    $this->assertSame('package', $info['name']);
    $this->assertSame('latest', $info['version']);
    $this->assertSame('my-package', $info['alias_name']);
    $this->assertSame(null, $info['alias_author']);
  }

  public function testVersionParsing14() {
    $info = Format::parse_package_string('[my-name/my-package]npm:package@latest');

    $this->assertSame('npm', $info['provider']);
    $this->assertSame(null, $info['author']);
    $this->assertSame('package', $info['name']);
    $this->assertSame('latest', $info['version']);
    $this->assertSame('my-package', $info['alias_name']);
    $this->assertSame('my-name', $info['alias_author']);
  }

  public function testVersionParsing15() {
    $info = Format::parse_package_string('[my-name/my-package]github:author/package@~4.2', 'another-name/another-package');

    $this->assertSame('github', $info['provider']);
    $this->assertSame('author', $info['author']);
    $this->assertSame('package', $info['name']);
    $this->assertSame('~4.2', $info['version']);
    $this->assertSame('another-package', $info['alias_name']);
    $this->assertSame('another-name', $info['alias_author']);
  }

  public function testVersionParsing16() {
    $info = Format::parse_package_string('github:v4.0.0', 'author/package');

    $this->assertSame('github', $info['provider']);
    $this->assertSame('author', $info['author']);
    $this->assertSame('package', $info['name']);
    $this->assertSame('v4.0.0', $info['version']);
    $this->assertSame(null, $info['alias_name']);
    $this->assertSame(null, $info['alias_author']);
  }
}
?>