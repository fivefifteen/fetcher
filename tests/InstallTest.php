<?php
use \PHPUnit\Framework\TestCase;
use \Fetcher\Command\Install;
use \Fetcher\Helper\File;
use \Fetcher\Helper\Format;
use \Fetcher\Helper\Request;

require_once('inc/helpers.php');

class InstallTest extends TestCase {
  static $playground = 'tests' . DIRECTORY_SEPARATOR . 'playground' . DIRECTORY_SEPARATOR . 'InstallTest';

  protected function setUp(): void {
    $mock = \Mockery::mock('alias:' . Request::class);
    $mocked_github_data = get_github_test_data();
    $mocked_npm_data = get_npm_test_data();

    $mock
      ->shouldReceive('get_json')
      ->with('https://api.github.com/repos/kodie/md5-file')
      ->andReturn($mocked_github_data['info']);

    $mock
      ->shouldReceive('get_json')
      ->with('https://api.github.com/repos/kodie/md5-file/branches')
      ->andReturn($mocked_github_data['branches']);

    $mock
      ->shouldReceive('get_json')
      ->with('https://api.github.com/repos/kodie/md5-file/commits')
      ->andReturn($mocked_github_data['commits']);

    $mock
      ->shouldReceive('get_json')
      ->with('https://api.github.com/repos/kodie/md5-file/tags')
      ->andReturn($mocked_github_data['tags']);

    $mock
      ->shouldReceive('get_json')
      ->with('https://registry.npmjs.org/md5-file')
      ->andReturn($mocked_npm_data['info']);

    $mock
      ->shouldReceive('get_json')
      ->with('./tests/files/fetch.json')
      ->andReturn(json_decode(file_get_contents(Format::build_path('tests', 'data', 'files', 'fetch.json')), true));

    $mock
      ->shouldReceive('make_request')
      ->withArgs(function ($arg) {
        $ext = pathinfo($arg, PATHINFO_EXTENSION);
        return $ext === 'zip' && basename($arg) !== 'test2.zip';
      })
      ->andReturn(file_get_contents(Format::build_path('tests', 'data', 'files', 'test.zip')));

    $mock
      ->shouldReceive('make_request')
      ->withArgs(function ($arg) {
        $ext = pathinfo($arg, PATHINFO_EXTENSION);
        return $ext === 'tgz';
      })
      ->andReturn(file_get_contents(Format::build_path('tests', 'data', 'files', 'test.tgz')));

    $mock
      ->shouldReceive('make_request')
      ->withArgs(function ($arg) {
        return basename($arg) === 'test2.zip';
      })
      ->andReturn(file_get_contents(Format::build_path('tests', 'data', 'files', 'test2.zip')));

    $mock
      ->shouldReceive('make_request')
      ->withArgs(function ($arg) {
        return !(
          str_starts_with($arg, 'https://example.com') ||
          str_starts_with($arg, 'https://github.com/kodie/md5-file') ||
          str_starts_with($arg, 'https://api.github.com/repos/kodie/md5-file') ||
          str_starts_with($arg, 'https://registry.npmjs.org/md5-file')
        );
      })
      ->andThrow(new \Error());

    File::delete_directory(self::$playground);
    File::create_directory(self::$playground);
  }

  public function tearDown(): void {
    Mockery::close();
    File::delete_directory(self::$playground);
  }

  public function testBasicInstall1() {
    $path = Format::build_path(self::$playground, 'testBasicInstall1');
    $install_path = Format::build_path($path, 'fetched');
    $cmd = new Install();

    $cmd->run(array(
      'install_directory' => $install_path,
      'packages'          => 'md5-file',
      'quiet'             => true
    ));

    $pkg_path = Format::build_path($install_path, 'md5-file');
    $test_file_exists = is_file(Format::build_path($pkg_path, 'test.js'));
    $files_count = File::count_files($pkg_path);

    $this->assertTrue($test_file_exists);
    $this->assertSame(3, $files_count);
  }

  public function testBasicInstall2() {
    $path = Format::build_path(self::$playground, 'testBasicInstall2');
    $install_path = Format::build_path($path, 'fetched');
    $cmd = new Install();

    $cmd->run(array(
      'install_directory' => $install_path,
      'packages'          => array('kodie/md5-file'),
      'quiet'             => true
    ));

    $pkg_path = Format::build_path($install_path, 'kodie', 'md5-file');
    $test_file_exists = is_file(Format::build_path($pkg_path, 'test.js'));
    $files_count = File::count_files($pkg_path);

    $this->assertTrue($test_file_exists);
    $this->assertSame(2, $files_count);
  }

  public function testBasicInstall3() {
    $path = Format::build_path(self::$playground, 'testBasicInstall3');
    $install_path = Format::build_path($path, 'fetched');
    $cmd = new Install();

    $cmd->run(array(
      'install_directory' => $install_path,
      'packages'          => 'https://example.com/download.zip',
      'quiet'             => true
    ));

    $pkg_path = Format::build_path($install_path, 'download');
    $test_file_exists = is_file(Format::build_path($pkg_path, 'test.js'));
    $files_count = File::count_files($pkg_path);

    $this->assertTrue($test_file_exists);
    $this->assertSame(3, $files_count);
  }

  public function testAliasInstall1() {
    $path = Format::build_path(self::$playground, 'testAliasInstall1');
    $install_path = Format::build_path($path, 'fetched');
    $cmd = new Install();

    $cmd->run(array(
      'install_directory' => $install_path,
      'packages'          => array('[someone/something]https://example.com/download.zip', '[some-package]kodie/md5-file'),
      'quiet'             => true
    ));

    $pkg_path = Format::build_path($install_path, 'someone', 'something');
    $test_file_exists = is_file(Format::build_path($pkg_path, 'test.js'));
    $files_count = File::count_files($pkg_path);

    $pkg_path2 = Format::build_path($install_path, 'some-package');
    $test_file_exists2 = is_file(Format::build_path($pkg_path2, 'test.js'));
    $files_count2 = File::count_files($pkg_path2);

    $this->assertTrue($test_file_exists);
    $this->assertSame(3, $files_count);
    $this->assertTrue($test_file_exists2);
    $this->assertSame(2, $files_count2);
  }

  public function testAliasInstall2() {
    $path = Format::build_path(self::$playground, 'testAliasInstall2');
    $install_path = Format::build_path($path, 'fetched');
    $cmd = new Install();

    $cmd->run(array(
      'install_directory' => $install_path,
      'packages'          => '[something.css]md5-file',
      'quiet'             => true
    ));

    $pkg_path = Format::build_path($install_path, 'something.css');
    $test_file_exists = is_file(Format::build_path($pkg_path, 'test.js'));
    $files_count = File::count_files($pkg_path);

    $this->assertTrue($test_file_exists);
    $this->assertSame(3, $files_count);
  }

  public function testNoExtractInstall1() {
    $path = Format::build_path(self::$playground, 'testNoExtractInstall1');
    $install_path = Format::build_path($path, 'fetched');
    $cmd = new Install();

    $cmd->run(array(
      'install_directory' => $install_path,
      'extract'           => false,
      'packages'          => 'https://example.com/download.zip',
      'quiet'             => true
    ));

    $test_file_exists = is_file(Format::build_path($install_path, 'download.zip'));
    $files_count = File::count_files($install_path);

    $this->assertTrue($test_file_exists);
    $this->assertSame(1, $files_count);
  }

  public function testSingleFileInstall1() {
    $path = Format::build_path(self::$playground, 'testSingleFileInstall1');
    $install_path = Format::build_path($path, 'fetched');
    $cmd = new Install();

    $cmd->run(array(
      'install_directory' => $install_path,
      'packages'          => 'https://example.com/test2.zip',
      'quiet'             => true
    ));

    $test_file_exists = is_file(Format::build_path($install_path, 'test2', 'test.css'));
    $files_count = File::count_files($install_path);

    $this->assertTrue($test_file_exists);
    $this->assertSame(1, $files_count);
  }

  public function testSingleFileInstall2() {
    $path = Format::build_path(self::$playground, 'testSingleFileInstall2');
    $install_path = Format::build_path($path, 'fetched');
    $cmd = new Install();

    $cmd->run(array(
      'install_directory' => $install_path,
      'packages'          => '[something.css]https://example.com/test2.zip',
      'quiet'             => true
    ));

    $test_file_exists = is_file(Format::build_path($install_path, 'something.css'));
    $files_count = File::count_files($install_path);

    $this->assertTrue($test_file_exists);
    $this->assertSame(1, $files_count);
  }

  public function testInstallViaConfig1() {
    $install_path = Format::build_path('playground', 'InstallTest', 'testInstallViaConfig1');
    $config_path = Format::build_path('files', 'fetch.json');
    $cmd = new Install();

    $cmd->run(array(
      'config_path'       => $config_path,
      'install_directory' => $install_path,
      'quiet'             => true,
      'working_directory' => './tests'
    ));

    $pkg_path = Format::build_path('./tests', $install_path, 'kodie', 'md5-file');
    $test_file_exists = is_file(Format::build_path($pkg_path, 'test.js'));
    $files_count = File::count_files($pkg_path);

    $pkg_path2 = Format::build_path('./tests', $install_path, 'someone', 'something');
    $test_file_exists2 = is_file(Format::build_path($pkg_path2, 'test.js'));
    $files_count2 = File::count_files($pkg_path2);

    $this->assertTrue($test_file_exists);
    $this->assertSame(1, $files_count);
    $this->assertTrue($test_file_exists2);
    $this->assertSame(1, $files_count2);
  }

  public function testSpecificExtInstall1() {
    $path = Format::build_path(self::$playground, 'testSpecificExtInstall1');
    $install_path = Format::build_path($path, 'fetched');
    $cmd = new Install();

    $cmd->run(array(
      'extensions'        => array('txt'),
      'install_directory' => $install_path,
      'packages'          => 'md5-file',
      'quiet'             => true
    ));

    $test_file_exists = is_file(Format::build_path($install_path, 'md5-file', 'test.txt'));
    $files_count = File::count_files($install_path);

    $this->assertTrue($test_file_exists);
    $this->assertSame(1, $files_count);
  }

  public function testSavingInstallChanges1() {
    $path = Format::build_path(self::$playground, 'testSavingChanges1');
    $cmd = new Install();

    $cmd->run(array(
      'packages'          => array('md5-file'),
      'quiet'             => true,
      'save_changes'      => true,
      'working_directory' => $path
    ));

    $test_file_exists = is_file(Format::build_path($path, 'fetch.json'));
    $test_dir_exists = is_dir(Format::build_path($path, 'fetched'));

    $this->assertTrue($test_file_exists);
    $this->assertTrue($test_dir_exists);
  }
}
?>