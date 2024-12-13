<?php
use \PHPUnit\Framework\TestCase;
use \Fetcher\Command\Uninstall;
use \Fetcher\Helper\File;
use \Fetcher\Helper\Format;
use \Fetcher\Helper\Request;

require_once('inc/helpers.php');

class UninstallTest extends TestCase {
  static $playground = 'tests' . DIRECTORY_SEPARATOR . 'playground' . DIRECTORY_SEPARATOR . 'UninstallTest';

  protected function setUp(): void {
    $install_path = Format::build_path(self::$playground, 'fetched');
    $config_path = Format::build_path(self::$playground, 'fetch.json');
    $pkg1_path = Format::build_path($install_path, 'someone', 'something');
    $pkg2_path = Format::build_path($install_path, 'someone', 'something-else');
    $pkg3_path = Format::build_path($install_path, 'cool-package');
    $pkg1_file1_path = Format::build_path($pkg1_path, 'test.txt');
    $pkg1_file2_path = Format::build_path($pkg1_path, 'inc', 'test.js');
    $pkg2_file1_path = Format::build_path($pkg2_path, 'test.txt');
    $pkg2_file2_path = Format::build_path($pkg2_path, 'inc', 'test.js');
    $pkg3_file1_path = Format::build_path($pkg3_path, 'test.txt');
    $pkg3_file2_path = Format::build_path($pkg3_path, 'inc', 'test.js');

    File::delete_directory(self::$playground);
    File::create_directory(Format::build_path($pkg1_path, 'inc'));
    File::create_directory(Format::build_path($pkg2_path, 'inc'));
    File::create_directory(Format::build_path($pkg3_path, 'inc'));

    file_put_contents($pkg1_file1_path, 'test');
    file_put_contents($pkg1_file2_path, 'test');
    file_put_contents($pkg2_file1_path, 'test');
    file_put_contents($pkg2_file2_path, 'test');
    file_put_contents($pkg3_file1_path, 'test');
    file_put_contents($pkg3_file2_path, 'test');

    copy(Format::build_path('tests', 'data', 'files', 'fetch.json'), $config_path);

    // This block of code shouldn't be necessary but without it we get a
    // "Call to a member function __call() on null" error
    $config_path = Format::build_path(self::$playground, 'fetch.json');
    $mock = \Mockery::mock('alias:' . Request::class);
    $mock
      ->shouldReceive('get_json')
      ->with($config_path)
      ->andReturn(json_decode(file_get_contents($config_path), true));
  }

  public function tearDown(): void {
    Mockery::close();
    File::delete_directory(self::$playground);
  }

  public function testBasicUninstall1() {
    $cmd = new Uninstall();

    $cmd->run(array(
      'packages'          => 'someone/something',
      'quiet'             => true,
      'working_directory' => self::$playground
    ));

    $test_dir_exists = is_dir(Format::build_path(self::$playground, 'fetched', 'someone', 'something'));
    $test_dir_exists2 = is_dir(Format::build_path(self::$playground, 'fetched', 'someone', 'something-else'));
    $test_dir_exists3 = is_dir(Format::build_path(self::$playground, 'fetched', 'cool-package'));

    $this->assertFalse($test_dir_exists);
    $this->assertTrue($test_dir_exists2);
    $this->assertTrue($test_dir_exists3);
  }

  public function testBasicMultiUninstall1() {
    $cmd = new Uninstall();

    $cmd->run(array(
      'packages'          => array('someone/something', 'someone/something-else'),
      'quiet'             => true,
      'working_directory' => self::$playground
    ));

    $test_dir_exists = is_dir(Format::build_path(self::$playground, 'fetched', 'someone'));
    $test_dir_exists2 = is_dir(Format::build_path(self::$playground, 'fetched', 'cool-package'));

    $this->assertFalse($test_dir_exists);
    $this->assertTrue($test_dir_exists2);
  }

  public function testUninstallViaConfig1() {
    $cmd = new Uninstall();

    $cmd->run(array(
      'quiet'             => true,
      'working_directory' => self::$playground
    ));

    $test_dir_exists = is_dir(Format::build_path(self::$playground, 'fetched', 'someone', 'something'));
    $test_dir_exists2 = is_dir(Format::build_path(self::$playground, 'fetched', 'someone', 'something-else'));
    $test_dir_exists3 = is_dir(Format::build_path(self::$playground, 'fetched', 'cool-package'));

    $this->assertFalse($test_dir_exists);
    $this->assertTrue($test_dir_exists2);
    $this->assertFalse($test_dir_exists3);
  }

  public function testFreshStartUninstall1() {
    $cmd = new Uninstall();

    $cmd->run(array(
      'fresh_start'       => true,
      'quiet'             => true,
      'working_directory' => self::$playground
    ));

    $test_file_exists = is_file(Format::build_path(self::$playground, 'fetch.json'));
    $test_dir_exists = is_dir(Format::build_path(self::$playground, 'fetched'));

    $this->assertTrue($test_file_exists);
    $this->assertFalse($test_dir_exists);
  }

  public function testFreshStartUninstall2() {
    $cmd = new Uninstall();

    $cmd->run(array(
      'fresh_start'       => true,
      'quiet'             => true,
      'save_changes'      => true,
      'working_directory' => self::$playground
    ));

    $test_file_exists = is_file(Format::build_path(self::$playground, 'fetch.json'));
    $test_dir_exists = is_dir(Format::build_path(self::$playground, 'fetched'));

    $this->assertFalse($test_file_exists);
    $this->assertFalse($test_dir_exists);
  }

  public function testSavingUninstallChanges1() {
    $cmd = new Uninstall();

    $cmd->run(array(
      'packages'          => 'someone/something',
      'quiet'             => true,
      'save_changes'      => true,
      'working_directory' => self::$playground
    ));

    $config_path = Format::build_path(self::$playground, 'fetch.json');
    $config = json_decode(file_get_contents($config_path), true);

    $entry_removed = !isset($config['fetcher']['dependencies']['someone/something']);
    $retained_entry = isset($config['fetcher']['dependencies']['cool-package']);

    $this->assertTrue($entry_removed);
    $this->assertTrue($retained_entry);
  }
}
?>