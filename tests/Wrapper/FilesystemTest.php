<?php

/**
 * Gloubi Boulga WP CakePHP(tm) 5 adapter
 * Copyright (c) Gloubi Boulga Team (https://github.com/gloubi-boulga-team)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright 2024 - now | Gloubi Boulga Team (https://github.com/gloubi-boulga-team)
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 * @link      https://github.com/gloubi-boulga-team
 * @since     5.0
 */

declare(strict_types=1);

namespace Gbg\Cake5\Wrapper;

use Gbg\Cake5\TestCase;

/**
 * @coversDefaultClass \Gbg\Cake5\Wrapper\Filesystem
 */
class FilesystemTest extends TestCase
{
    /**
     * @inheritDoc
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        static::createTestFiles();
    }

    /**
     * @inheritDoc
     */
    public static function tearDownAfterClass(): void
    {
        parent::setUpBeforeClass();
        static::removeTestFiles();
    }

    /**
     * Get root path
     *
     * @return string
     */
    protected static function getRootPath(): string
    {
        return Filesystem::concat(WP_CONTENT_DIR, '_gbg_cake5_filesystem_tests');
    }

    /**
     * Get max depth of created folders
     * Do not change this value or expected calculated values will change and assertions will fail
     *
     * @return int
     */
    protected static function getMaxDepth(): int
    {
        return 5;
    }

    /**
     * Get max file count by folder
     * Do not change this value or expected calculated values will change and assertions will fail
     *
     * @return int
     */
    protected static function getFileCount(): int
    {
        return 4;
    }

    /**
     * Get max sub dir count
     * Do not change this value or expected calculated values will change and assertions will fail
     *
     * @return int
     */
    protected static function getDirCount(): int
    {
        return 3;
    }

    /**
     * Create sub folders
     *
     * @param string $curPath
     * @param int $curDepth
     *
     * @return array<int, string>
     */
    protected static function createSubFolders(string $curPath, int $curDepth): array
    {
        $return = [];

        for ($i = 0; $i < static::getDirCount(); $i++) {
            $newPath = Filesystem::concat($curPath, 'testpath-' . $i);
            $return[] = $newPath;
            Filesystem::ensureDir($newPath);

            if ($curDepth < static::getMaxDepth()) {
                $return = array_merge(
                    $return,
                    static::createSubFolders($newPath, $curDepth + 1)
                );
            }
        }

        return $return;
    }

    /**
     * Create test files
     *
     * @return void
     */
    protected static function createTestFiles(): void
    {
        $folders = static::createSubFolders(static::getRootPath(), 1);

        foreach ($folders as $folder) {
            for ($i = 0; $i < static::getFileCount(); $i++) {
                file_put_contents(Filesystem::concat($folder, "testfile$i.txt"), 'x');
            }
        }
    }

    /**
     * Remove test files
     *
     * @return void
     */
    protected static function removeTestFiles(): void
    {
        Filesystem::removeDir(static::getRootPath(), true);
    }

    /**
     * @test Filesystem::concat
     *
     * @return void
     */
    public function testConcat(): void
    {
        $ds = DIRECTORY_SEPARATOR;

        $tests = [
            ['args' => ['x', 'y', 'z'], 'result' => 'x' . $ds . 'y' . $ds . 'z'],
            ['args' => ['', null, 'x', '/', 'y', '/'], 'result' => 'x' . $ds . $ds . 'y' . $ds],
            ['args' => ['', null, '/x', '/', '/y/', '/'], 'result' => $ds . 'x' . $ds . $ds . 'y' . $ds],
        ];

        foreach ($tests as $k => $test) {
            $result = call_user_func_array('Gbg\Cake5\Wrapper\Filesystem::concat', $test['args']);
            $this->assertEquals($test['result'], $result, 'testConcat #' . $k);
        }
    }

    /**
     * @test Filesystem::concatSlash
     *
     * @return void
     */
    public function testConcatSlash(): void
    {
        $ds = '/';

        $tests = [
            ['args' => ['x0', 'y', 'z'], 'result' => 'x0' . $ds . 'y' . $ds . 'z'],
            ['args' => ['', null, 'x1', '/', 'y', '/'], 'result' => 'x1' . $ds . $ds . 'y' . $ds],
            ['args' => ['', null, '/x2', '/', 'y', '/'], 'result' => $ds . 'x2' . $ds . $ds . 'y' . $ds],
        ];

        foreach ($tests as $k => $test) {
            $result = call_user_func_array('Gbg\Cake5\Wrapper\Filesystem::concatSlash', $test['args']);
            $this->assertEquals($test['result'], $result, 'testConcatSlash #' . $k);
        }
    }

    /**
     * @test Filesystem::explodePath
     *
     * @return void
     */
    public function testExplodePath(): void
    {
        $tests = [
            ['args' => ['x/y/z'], 'result' => ['x', 'y', 'z']],
            ['args' => ['/x/y//z/'], 'result' => ['x', 'y', 'z']],
            ['args' => [''], 'result' => []],
        ];

        foreach ($tests as $k => $test) {
            $result = call_user_func_array('Gbg\Cake5\Wrapper\Filesystem::explodePath', $test['args']);
            $this->assertEquals($test['result'], $result, 'testExplodePath #' . $k);
        }
    }

    /**
     * @test Filesystem::normalizePath
     *
     * @return void
     */
    public function testNormalizePath(): void
    {
        $tests = [
            ['args' => ['x/y/z\\a\\a\\b\\', '/'], 'result' => 'x/y/z/a/a/b/'],
            ['args' => ['/x/y/z\\\\a\\a\\b\\', '/'], 'result' => '/x/y/z//a/a/b/'],
            ['args' => ['/x/y/z\\\\\\a\\a\\b\\', '/'], 'result' => '/x/y/z///a/a/b/'],
            ['args' => ['x/y/z\\a\\a\\b\\', '\\'], 'result' => 'x\\y\\z\\a\\a\\b\\'],
            ['args' => ['/x/y/z\\\\a\\a\\b\\', '\\'], 'result' => '\\x\\y\\z\\\\a\\a\\b\\'],
            ['args' => ['/x/y/z\\\\\\a\\a\\b\\', '\\'], 'result' => '\\x\\y\\z\\\\\\a\\a\\b\\'],
        ];

        foreach ($tests as $k => $test) {
            $result = call_user_func_array('Gbg\Cake5\Wrapper\Filesystem::normalize', $test['args']);
            $this->assertEquals($test['result'], $result, 'testNormalizePath #' . $k);
        }
    }

    /**
     * @test Filesystem::list
     *
     * @return void
     */
    public function testList(): void
    {
        $rootPath = static::getRootPath();
        $fileCount = static::getFileCount();
        $dirCount = static::getDirCount();
        $maxDepth = static::getMaxDepth();

        // check number of * folders -----------------------------------------------------------------------------------

        $dirs = Filesystem::listDirs($rootPath, '*') ?: [];
        $this->assertEquals($dirCount, count($dirs));

        $dirs = Filesystem::listDirs($rootPath, '*', 1) ?: [];
        $this->assertEquals($dirCount, count($dirs));

        $dirs = Filesystem::listDirs($rootPath, '*', 2) ?: [];
        $expected = pow($dirCount, 2) + 3;
        $this->assertEquals($expected, count($dirs));

        $dirs = Filesystem::listDirs($rootPath, '*', 4) ?: [];
        $expected = pow($dirCount, 4) + pow($dirCount, 3) + pow($dirCount, 2) + 3;
        $this->assertEquals($expected, count($dirs));

        $dirs = Filesystem::listDirs($rootPath, '*', 5) ?: [];
        $expected = pow($dirCount, 5) + pow($dirCount, 4) + pow($dirCount, 3) + pow($dirCount, 2) + 3;
        $this->assertEquals($expected, count($dirs));

        $dirs = Filesystem::listDirs($rootPath, '*', 6) ?: [];
        $this->assertEquals($expected, count($dirs));

        $dirs = Filesystem::listDirs($rootPath, '*', -1) ?: [];
        $this->assertEquals($expected, count($dirs));

        // check number of filtered folders ----------------------------------------------------------------------------

        $dirs = Filesystem::listDirs($rootPath, '*path-0', 1) ?: [];
        $this->assertEquals(1, count($dirs));

        $dirs = Filesystem::listDirs($rootPath, '*path-0', 2) ?: [];
        $this->assertEquals(4, count($dirs));

        $dirs = Filesystem::listDirs($rootPath, '*path-0', 4) ?: [];
        $this->assertEquals(40, count($dirs));

        $dirs = Filesystem::listDirs($rootPath, '*', 5) ?: [];
        $this->assertEquals(363, count($dirs));

        $dirs = Filesystem::listDirs($rootPath, '*', 6) ?: [];
        $this->assertEquals(363, count($dirs));

        // check number of files ---------------------------------------------------------------------------------------

        $files = Filesystem::listFiles($rootPath, '*', 1) ?: [];
        $this->assertEmpty($files);

        $files = Filesystem::listFiles($rootPath, '*', 2) ?: [];
        $this->assertCount($fileCount * $dirCount, $files);

        $files = Filesystem::listFiles($rootPath, '*', 3) ?: [];
        $this->assertCount(48, $files);

        $files = Filesystem::listFiles($rootPath, '*', 4) ?: [];
        $this->assertCount(156, $files);

        $files = Filesystem::listFiles($rootPath, '*', 5) ?: [];
        $this->assertCount(480, $files);

        $files = Filesystem::listFiles($rootPath, '*', 6) ?: [];
        $this->assertCount(1452, $files);

        $files = Filesystem::listFiles($rootPath, '*', 7) ?: [];
        $this->assertCount(1452, $files);

        $files = Filesystem::listFiles($rootPath, '*file0*', 2) ?: [];
        $this->assertCount(3, $files);

        $files = Filesystem::listFiles($rootPath, '*file0*', 5) ?: [];
        $this->assertCount(120, $files);

        $files = Filesystem::listFiles($rootPath, '*file0*', 6) ?: [];
        $this->assertCount(363, $files);

        $files = Filesystem::listFiles($rootPath, '*file0*', 7) ?: [];
        $this->assertCount(363, $files);

        $files = Filesystem::listFiles($rootPath, 'testfile0.txt', 7) ?: [];
        $this->assertCount(363, $files);

        // check number of objects -------------------------------------------------------------------------------------
        $items = Filesystem::list($rootPath, '*', null, 2) ?: [];
        $this->assertCount(24, $items);

        $items = Filesystem::list($rootPath, '*', null, 4) ?: [];
        $this->assertCount(276, $items);

        $items = Filesystem::list($rootPath, '*0*', null, 4) ?: [];
        $this->assertCount(79, $items);

        // -------------------------------------------------------------------------------------

        $items = Filesystem::list($rootPath, '*0*', null, 4) ?: [];
        $this->assertCount(79, $items);
    }

    /**
     * @test Filesystem::removeDir
     *
     * @return void
     */
    public function testEmptyRemoveDir(): void
    {
        $rootPath = static::getRootPath();
        $path = Filesystem::concat($rootPath, 'testpath-0');

        $items = Filesystem::list($path, '*', null, PHP_INT_MAX);
        $countBefore = count(Filesystem::list($path, '*', null, PHP_INT_MAX));

        Filesystem::emptyDir($path, false);
        $countAfter = count(Filesystem::list($path, '*', null, PHP_INT_MAX));

        $this->assertEquals(604, $countBefore);
        $this->assertEquals(600, $countAfter);

        Filesystem::removeDir($path, false);
        $countAfter = count(Filesystem::list($path, '*', null, PHP_INT_MAX));
        $this->assertEquals(600, $countAfter);

        Filesystem::removeDir($path, true);
        $countAfter = count(Filesystem::list($path, '*', null, PHP_INT_MAX));
        $this->assertEquals(0, $countAfter);
    }

    /**
     * @test Filesystem::isOlderThan
     *
     * @return void
     */
    public function testIsFileOlderThan(): void
    {
        $rootPath = static::getRootPath();
        $path = Filesystem::concat($rootPath, 'testpath-1');

        $items = Filesystem::listFiles($path, '*', 50) ?: [];
        $item = $items[0];

        $filemtime = filemtime($item);
        $result = Filesystem::isFileOlderThan($item, '20 second');
        $this->assertEquals(false, $result);

        $result = Filesystem::isFileOlderThan($item, '0.1 second');
        $this->assertEquals(true, $result);

        $result = Filesystem::isFileOlderThan('*434+-811-171+17*7-7*1-7-71-7', '20 second');
        $this->assertEquals(true, $result);

        $result = Filesystem::isFileOlderThan($item, '20 second', 'xyz');
        $this->assertEquals(null, $result);
    }

    public function testEnsure(): void
    {
        $dir = Filesystem::ensureRelative(__DIR__);
        $expected = Filesystem::concat('wp-content', 'plugins', 'gbg-cake5', 'tests', 'Wrapper');
        $this->assertEquals($expected, $dir);

        $dir = Filesystem::ensureRelative('plugins/xyz/');
        $expected = str_replace('/', DIRECTORY_SEPARATOR, 'plugins/xyz/');
        $this->assertEquals($expected, $dir);

        $dir = Filesystem::ensureAbsolute(__DIR__);
        $expected = Filesystem::concat(WP_CONTENT_DIR, 'plugins', 'gbg-cake5', 'tests', 'Wrapper');
        $this->assertEquals($expected, $dir);

        $dir = Filesystem::ensureAbsolute('plugins/xyz');
        $expected = Filesystem::concat(ABSPATH, 'plugins/xyz');
        $this->assertEquals($expected, $dir);

        $dir = Filesystem::ensureAbsolute('/plugins/xyz');
        $expected = Filesystem::concat(ABSPATH, 'plugins/xyz');
        $this->assertEquals($expected, $dir);

        $dir = Filesystem::ensureAbsolute('/plugins/xyz/');
        $expected = Filesystem::concat(ABSPATH, 'plugins/xyz/');
        $this->assertEquals($expected, $dir);

        $dir = Filesystem::ensureAbsolute('plugins/xyz/');
        $expected = Filesystem::concat(ABSPATH, 'plugins/xyz/');
        $this->assertEquals($expected, $dir);
    }

    public function testCreateEmptyDir(): void
    {
        $i = 0;
        while (is_dir(__DIR__ . '/test' . $i)) {
            $i++;
        }
        $this->assertEquals(false, is_dir(__DIR__ . '/test' . $i));
        Filesystem::ensureDir(__DIR__ . '/test' . $i);
        $this->assertEquals(true, is_dir(__DIR__ . '/test' . $i));

        $result = Filesystem::removeDir(__DIR__ . '/test' . $i);
        $this->assertEquals(true, $result);
        $this->assertEquals(false, is_dir(__DIR__ . '/test' . $i));

        $i = 0;
        while (is_dir(__DIR__ . '/test' . $i)) {
            $i++;
        }
    }

    public function testHtdeny(): void
    {
        $i = 0;
        while (is_dir(__DIR__ . '/test' . $i)) {
            $i++;
        }
        Filesystem::ensureDir(__DIR__ . '/test' . $i);
        Filesystem::htDeny(__DIR__ . '/test' . $i);
        $this->assertEquals(true, is_file(__DIR__ . '/test' . $i . '/.htaccess'));
        Filesystem::removeDir(__DIR__ . '/test' . $i);
    }

    public function testGetFileStats(): void
    {
        $i = 0;
        while (is_dir(__DIR__ . '/test' . $i)) {
            $i++;
        }
        Filesystem::ensureDir(__DIR__ . '/test' . $i);

        $newest = time();
        $newestStr = date('Y-m-d H:i:s', $newest);
        $oldest = strtotime('-1 year');
        $oldestStr = date('Y-m-d H:i:s', $oldest);

        file_put_contents(__DIR__ . '/test' . $i . '/testfile1.txt', 'x');
        file_put_contents(__DIR__ . '/test' . $i . '/testfile2.txt', 'xy');
        file_put_contents(__DIR__ . '/test' . $i . '/testfile3.txt', 'xyz');

        touch(__DIR__ . '/test' . $i . '/testfile1.txt', $oldest);
        touch(__DIR__ . '/test' . $i . '/testfile3.txt', $newest);

        $stats = Filesystem::getFileStats(__DIR__ . '/test' . $i, '*');
        $this->assertEquals(3, $stats['count']);
        $this->assertEquals($oldest, $stats['oldest']);
        $this->assertEquals($newest, $stats['newest']);

        Filesystem::removeDir(__DIR__ . '/test' . $i);

        $this->assertEquals(
            ['count' => 0, 'totalSize' => null, 'oldest' => null, 'newest' => null, 'biggest' => null],
            Filesystem::getFileStats('/test' . $i, '*')
        );
    }

    public function testNormalize(): void
    {
        $x = Filesystem::normalize('x/y/z\\a\\a\\b\\', '/');
        $this->assertEquals('x/y/z/a/a/b/', $x);
    }
}
