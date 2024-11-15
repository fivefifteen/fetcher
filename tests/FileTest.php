<?php
use \PHPUnit\Framework\TestCase;
use \Fetcher\Helper\File;
use \Fetcher\Helper\Format;
use \Fetcher\Helper\Request;

class FileTest extends TestCase {
  static $playground = 'tests' . DIRECTORY_SEPARATOR . 'playground' . DIRECTORY_SEPARATOR . 'FileTest';

  protected function setUp(): void {
    $mock = \Mockery::mock('alias:' . Request::class);

    $mock
      ->shouldReceive('make_request')
      ->with('https://example.com/download.zip')
      ->andReturn(file_get_contents(Format::build_path('tests', 'data', 'files', 'test.zip')));

    File::delete_directory(self::$playground);
    File::create_directory(self::$playground);
  }

  public function tearDown(): void {
    \Mockery::close();
    File::delete_directory(self::$playground);
  }

  public function testCountFiles1() {
    $intended_count = 3;
    $path = Format::build_path(self::$playground, 'testCountFiles');

    File::create_directory($path);

    for ($x = 1; $x <= $intended_count; $x++) {
      $file_path = Format::build_path($path, "file-{$x}.txt");
      file_put_contents($file_path, "test-{$x}");
    }

    $result = File::count_files($path);

    $this->assertSame(3, $result);
  }

  public function testDirectoryCreation1() {
    $path = Format::build_path(self::$playground, 'testDirectoryCreation');

    File::create_directory($path);

    $dir_exists = is_dir($path);

    $this->assertTrue($dir_exists);
  }

  public function testDelete1() {
    $path = Format::build_path(self::$playground, 'testDelete1');

    File::create_directory($path);

    $dir_was_created = is_dir($path);

    File::delete($path);

    $dir_was_deleted = !is_dir($path);

    $this->assertTrue($dir_was_created);
    $this->assertTrue($dir_was_deleted);
  }

  public function testDelete2() {
    $path = Format::build_path(self::$playground, 'testDelete2');
    $file_path = Format::build_path($path, 'test.txt');

    File::create_directory($path);

    file_put_contents($file_path, 'test');

    $file_was_created = is_file($file_path);

    File::delete($file_path);

    $file_was_deleted = !is_file($file_path);

    $this->assertTrue($file_was_created);
    $this->assertTrue($file_was_deleted);
  }

  public function testDeleteDirectory1() {
    $path = Format::build_path(self::$playground, 'testDeleteDirectory');
    $file_path = Format::build_path($path, 'test.txt');
    $child_dir_path = Format::build_path($path, 'childDir');
    $child_dir_file_path = Format::build_path($child_dir_path, 'test2.txt');

    File::create_directory($child_dir_path);

    $dir_was_created = is_dir($child_dir_path);

    File::delete($path);

    $dir_was_deleted = !is_dir($path);

    $this->assertTrue($dir_was_created);
    $this->assertTrue($dir_was_deleted);
  }

  public function testDeleteFile1() {
    $path = Format::build_path(self::$playground, 'testDeleteFile');
    $file_path = Format::build_path($path, 'test.txt');

    File::create_directory($path);

    file_put_contents($file_path, 'test');

    $file_was_created = is_file($file_path);

    File::delete_file($file_path);

    $file_was_deleted = !is_file($file_path);

    $this->assertTrue($file_was_created);
    $this->assertTrue($file_was_deleted);
  }

  public function testDownload1() {
    $path = Format::build_path(self::$playground, 'testDownload');
    $file_path = Format::build_path($path, 'testDownload.zip');

    File::create_directory($path);
    File::download('https://example.com/download.zip', $file_path);

    $file_was_downloaded = is_file($file_path);

    $this->assertTrue($file_was_downloaded);
  }

  public function testExtract1() {
    $path = Format::build_path(self::$playground, 'testExtract');
    $file_path = Format::build_path('tests', 'data', 'files', 'test.zip');
    $test_file = Format::build_path($path, 'test.txt');

    File::create_directory($path);
    File::extract($file_path, $path, 1, '/\.github/i', '/.*\.(txt)$/i');

    $file_was_extracted = is_file($test_file);
    $file_count = File::count_files($path);

    $this->assertTrue($file_was_extracted);
    $this->assertSame(1, $file_count);
  }

  public function testArchiveFileCheck1() {
    $file_path = Format::build_path('tests', 'data', 'files', 'test.zip');
    $result = File::is_archive_file($file_path);

    $this->assertTrue($result);
  }

  public function testArchiveFileCheck2() {
    $file_path = Format::build_path('tests', 'data', 'files', 'test.txt');
    $result = File::is_archive_file($file_path);

    $this->assertFalse($result);
  }
}
?>