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

    public function testSanitizeDoubleSeparators(): void
    {
        $tests = [
            '/x/y/.'                             => '/x/y/.',
            '\\\\x\y\.'                          => '//x/y/.',
            '\\x\y\.'                            => '/x/y/.',
            'http://x/y/.'                       => 'http://x/y/.',
            'http://x/y///.'                      => 'http://x/y/.',
            'http://x/y////////////////////z/.'  => 'http://x/y/z/.',
            'ssh2://x//////y/z//////./'          => 'ssh2://x/y/z/./',
            '/x//y/.'                            => '/x/y/.',
            '//x//y/.'                           => '//x/y/.',
            '//x//y///////////z/'                => '//x/y/z/',
            '\\x\\\\y\\.'                        => '/x/y/.',
            '\\\\x\\\\y\\.'                      => '//x/y/.',
            '\\\\x\\\\y\\\\\\\\\\\\\\\\\\\\\\z/' => '//x/y/z/',
            '\\x\\\\y\\\\\\\\\\\\\\\\\\\\\\z/'   => '/x/y/z/',
            '\\\\\\\\\\\\\\\\\\\\\\\\\\x\\\\y\\z/' => '//x/y/z/',
        ];

        //Filesystem::$debug = true;
        foreach ($tests as $test => $expected) {
            $test1 = str_replace(['\\', '/'], '/', Filesystem::normalize($test, '/'));
            $this->assertSame($expected, Filesystem::sanitizeDoubleSeparators($test1, '/'), 'test ' . $test);
            $test2 = str_replace(['\\', '/'], '\\', Filesystem::normalize($test, '\\'));
            $expected = str_replace(['\\', '/'], '\\', $expected);
            $this->assertSame($expected, Filesystem::sanitizeDoubleSeparators($test2, '\\'), 'test ' . $test);
        }
    }

    /**
     * @test Filesystem::concat
     *
     * @return void
     */
    public function testConcat(): void
    {
        $tests = [
            ['args' => ['x', 'y', 'z'], 'result' => 'x' . DS . 'y' . DS . 'z'],
            ['args' => ['', null, 'x', '/', '/', 'y', '/'], 'result' => 'x' . DS . 'y' . DS],
            ['args' => ['', null, '/x', '/', '/', '/', '/y/', '/'], 'result' => DS . 'x' . DS . 'y' . DS],
            ['args' => ['/', null, '/x', '/', '/', '/', '/y/', '/'], 'result' => DS . DS . 'x' . DS . 'y' . DS],
            ['args' => ['/', '', '/', '//x', '/', '/', '/', '/y/', '/'], 'result' => DS . DS . 'x' . DS . 'y' . DS],
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
            ['args' => ['', null, 'x1', '/', '/', 'y', '/'], 'result' => 'x1' . $ds . 'y' . $ds],
            ['args' => ['', null, '/x2', '/', '/', '/', 'y', '/'], 'result' => $ds . 'x2' . $ds . 'y' . $ds],
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
            ['args' => ['', '/'], 'result' => ''],
            ['args' => ['https://toto/titi//tata//x', '/'], 'result' => 'https://toto/titi/tata/x'],
            ['args' => ['https2://toto/titi//tata//x', '/'], 'result' => 'https2://toto/titi/tata/x'],
            ['args' => ['https2:///toto/titi//tata//x', '/'], 'result' => 'https2://toto/titi/tata/x'],
            ['args' => ['https2:////toto/titi//tata//x', '/'], 'result' => 'https2://toto/titi/tata/x'],
            ['args' => ['://toto/titi////tata//x', '/'], 'result' => '://toto/titi/tata/x'],
            ['args' => [':///toto/titi////tata//x', '/'], 'result' => '://toto/titi/tata/x'],
            ['args' => [':////toto/titi////tata//x', '/'], 'result' => '://toto/titi/tata/x'],
            ['args' => ['://///toto/titi////tata//x', '/'], 'result' => '://toto/titi/tata/x'],
            ['args' => ['///toto/titi////tata//x', '/'], 'result' => '//toto/titi/tata/x'],
            ['args' => ['/////toto/titi////tata//x', '/'], 'result' => '//toto/titi/tata/x'],
            ['args' => ['x/y/z\\a\\a\\b\\', '/'], 'result' => 'x/y/z/a/a/b/'],
            ['args' => ['/x/y/z\\\\a\\a\\b\\', '/'], 'result' => '/x/y/z/a/a/b/'],
            ['args' => ['/x/y/z\\\\\\a\\a\\b\\', '/'], 'result' => '/x/y/z/a/a/b/'],
            ['args' => ['x/y/z\\a\\a\\b\\', '\\'], 'result' => 'x\\y\\z\\a\\a\\b\\'],
            ['args' => ['/x/y/z\\\\a\\a\\b\\', '\\'], 'result' => '\\x\\y\\z\\a\\a\\b\\'],
            ['args' => ['/x/y/z\\\\\\a\\a\\b\\', '\\'], 'result' => '\\x\\y\\z\\a\\a\\b\\'],
            ['args' => ['C:\\test//toto//titi//.', '\\'], 'result' => 'C:\\test\\toto\\titi\\.'],
            ['args' => ['C:\\\\test//toto//titi//.', '\\'], 'result' => 'C:\\\\test\\toto\\titi\\.'],
            ['args' => ['\\\\server\\\\Path//toto//titi//', '\\'], 'result' => '\\\\server\\Path\\toto\\titi\\'],
            ['args' => ['\\\\\\server\\\\Path//toto//titi//', '\\'], 'result' => '\\\\server\\Path\\toto\\titi\\'],
            ['args' => ['\\\\\\\\\\server\\\\Path//toto//titi//', '\\'], 'result' => '\\\\server\\Path\\toto\\titi\\'],

            ['args' => ['C:\\test//toto//titi//.', '/'], 'result' => 'C:/test/toto/titi/.'],
            ['args' => ['C:\\\\test//toto//titi//.', '/'], 'result' => 'C://test/toto/titi/.'],
            ['args' => ['\\\\server\\\\Path//toto//titi//', '/'], 'result' => '//server/Path/toto/titi/'],
            ['args' => ['\\\\\\server\\\\Path//toto//titi//', '/'], 'result' => '//server/Path/toto/titi/'],
            ['args' => ['\\\\\\\\\\server\\\\Path//toto//titi//', '/'], 'result' => '//server/Path/toto/titi/'],

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

        $result = Filesystem::emptyDir('/not-existing-path', false);
        $this->assertSame(false, $result);

        $result = Filesystem::emptyDir('/not-existing-path', true);
        $this->assertSame(false, $result);

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

        // test empty dir with .htaccess and subdir
        $path2 = Filesystem::concat($rootPath, 'testpath-999');
        Filesystem::ensureDir($path2);
        Filesystem::ensureDir($path2 . '/subdir');
        Filesystem::htDeny($path2);
        Filesystem::htDeny($path2 . '/subdir');
        file_put_contents($path2 . '/xyz', 'xyz');

        $countBefore = count(Filesystem::list($path2, '*', null, PHP_INT_MAX));
        $this->assertSame(2, $countBefore);
        Filesystem::emptyDir($path2, true);
        $this->assertSame(false, is_dir($path2 . '/subdir'));
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

    public function testEnsureRelative(): void
    {

        // simple test without base path

        $this->assertSame('', Filesystem::ensureRelative(''));
        $this->assertSame('', Filesystem::ensureRelative('', '', '/'));
        $this->assertSame('.', Filesystem::ensureRelative('.', '', '\\'));
        $this->assertSame('a', Filesystem::ensureRelative('a', '', '\\'));
        $this->assertSame('\\', Filesystem::ensureRelative('/', '', '\\'));
        $this->assertSame('/', Filesystem::ensureRelative('/', '', '/'));

        // simple test with base path ``
        $tests = [
            '' => '',
            '/' => '/',
            'bla' => 'bla',
            '/bla' => '/bla',
            '/bla/' => '/bla/',
            '/blablebli' => '/blablebli',
            'bla/ble/bli' => 'bla/ble/bli',
            '/bla/ble/bli' => '/bla/ble/bli',
            '/bla//ble/bli' => '/bla/ble/bli',
            '/bla/ble/bli/' => '/bla/ble/bli/',
            'bla/ble/bli/' => 'bla/ble/bli/',
        ];

        foreach ($tests as $test => $expected) {
            $this->assertSame($expected, Filesystem::ensureRelative($test, '', '/'), 'test ' . $test);
            $expected = str_replace('/', '\\', $expected);
            $this->assertSame($expected, Filesystem::ensureRelative($test, '', '\\'), 'test ' . $test);
        }

        // simple test with base path `/`

        $tests = [
            '' => '',
            '/' => '',
            'bla' => 'bla',
            '/bla' => 'bla',
            '/bla/' => 'bla/',
            '//bla/' => '/bla/',
            '/blablebli' => 'blablebli',
            'bla/ble/bli' => 'bla/ble/bli',
            '/bla/ble/bli' => 'bla/ble/bli',
            '/bla/ble/bli/' => 'bla/ble/bli/',
            '/bla//ble/bli/' => 'bla/ble/bli/',
            '//bla/ble/bli/' => '/bla/ble/bli/',
            'bla/ble/bli/' => 'bla/ble/bli/',
            '😝☮️/嗨مرحباً/Sa^lut*/مرحباً' => '😝☮️/嗨مرحباً/Sa^lut*/مرحباً',
            '/😝☮️/嗨مرحباً/Sa^lut*/مرحباً' => '😝☮️/嗨مرحباً/Sa^lut*/مرحباً',
            '\\\\😝☮️/嗨مرحباً/Sa^lut*/مرحباً' => '/😝☮️/嗨مرحباً/Sa^lut*/مرحباً',
        ];

        foreach ($tests as $test => $expected) {
            $this->assertSame($expected, Filesystem::ensureRelative($test, '/', '/'), 'test ' . $test);
            $expected = str_replace('/', '\\', $expected);
            $this->assertSame($expected, Filesystem::ensureRelative($test, '/', '\\'), 'test ' . $test);
        }

        // simple test with base path `/bla`

        $tests = [
            '' => '',
            '/' => '/',
            'bla' => 'bla',
            '/bla' => '',
            '/bla/' => '/',
            '/bla//' => '/',
            '//bla/' => '//bla/',
            '/blablebli' => '/blablebli',
            'bla/ble/bli' => 'bla/ble/bli',
            '/bla/ble/bli' => '/ble/bli',
            '/bla/ble/bli/' => '/ble/bli/',
            '/bla//ble/bli/' => '/ble/bli/',
            '//bla/ble/bli/' => '//bla/ble/bli/',
            '////bla/ble/bli/' => '//bla/ble/bli/',
            '///////bla/ble/bli/' => '//bla/ble/bli/',
            '\\\\bla/ble/bli/' => '//bla/ble/bli/',
            '\\bla/ble/bli/' => '/ble/bli/',
            'bla/ble/bli/' => 'bla/ble/bli/',
            '/bla/😝☮️/嗨مرحباً/Sa^lut*/مرحباً' => '/😝☮️/嗨مرحباً/Sa^lut*/مرحباً',
            '\\bla\\😝☮️/嗨مرحباً/Sa^lut*/مرحباً' => '/😝☮️/嗨مرحباً/Sa^lut*/مرحباً',
            '😝☮️/嗨مرحباً/Sa^lut*/مرحباً' => '😝☮️/嗨مرحباً/Sa^lut*/مرحباً',
            '\\\\\\😝☮️/嗨مرحباً/Sa^lut*/مرحباً' => '//😝☮️/嗨مرحباً/Sa^lut*/مرحباً',
            '/😝☮️/嗨مرحباً/Sa^lut*/مرحباً' => '/😝☮️/嗨مرحباً/Sa^lut*/مرحباً',
            '\\😝☮️/嗨مرحباً/Sa^lut*/مرحباً' => '/😝☮️/嗨مرحباً/Sa^lut*/مرحباً',
        ];

        foreach ($tests as $test => $expected) {
            $this->assertSame($expected, Filesystem::ensureRelative($test, '/bla', '/'), 'test ' . $test);
            $expected = str_replace('/', '\\', $expected);
            $this->assertSame($expected, Filesystem::ensureRelative($test, '/bla', '\\'), 'test ' . $test);
        }

        // simple test with base path `/bla/ble`

        $tests = [
            '' => '',
            '/' => '/',
            'bla' => 'bla',
            '/bla' => '/bla',
            '/bla/' => '/bla/',
            '/bla//' => '/bla/',
            '//bla/' => '//bla/',
            '/blablebli' => '/blablebli',
            '/bla/blebli' => '/bla/blebli',
            '/bla/ble/bli' => '/bli',
            'bla/ble/bli' => 'bla/ble/bli',
            '/bla/ble/bli/' => '/bli/',
            '/bla//ble/bli/' => '/bli/',
            '/bla//ble///bli/' => '/bli/',
            '//bla/ble/bli/' => '//bla/ble/bli/',
            '\\\\bla/ble/bli/' => '//bla/ble/bli/',
            '\\bla/ble/bli/' => '/bli/',
            'bla/ble/bli/' => 'bla/ble/bli/',
            '/bla/ble/😝☮️/嗨مرحباً/Sa^lut*/مرحباً' => '/😝☮️/嗨مرحباً/Sa^lut*/مرحباً',
            '\\bla\\ble/😝☮️\\嗨مرحباً/Sa^lut*/مرحباً' => '/😝☮️/嗨مرحباً/Sa^lut*/مرحباً',
            '😝☮️/嗨مرحباً/Sa^lut*/مرحباً' => '😝☮️/嗨مرحباً/Sa^lut*/مرحباً',
            '/😝☮️/嗨مرحباً/Sa^lut*/مرحباً' => '/😝☮️/嗨مرحباً/Sa^lut*/مرحباً',
            '\\😝☮️/嗨مرحباً/Sa^lut*/مرحباً' => '/😝☮️/嗨مرحباً/Sa^lut*/مرحباً',
        ];

        foreach ($tests as $test => $expected) {
            $this->assertSame($expected, Filesystem::ensureRelative($test, '/bla/ble', '/'), 'test ' . $test);
            $expected = str_replace('/', '\\', $expected);
            $this->assertSame($expected, Filesystem::ensureRelative($test, '/bla/ble', '\\'), 'test ' . $test);
        }

        $tests = [
            '' => '',
            '/' => '/',
            'bla' => 'bla',
            '/bla' => '/bla',
            '/bla/' => '/bla/',
            '/bla//' => '/bla/',
            '//bla/' => '//bla/',
            '/blablebli' => '/blablebli',
            '/bla/blebli' => '/bla/blebli',
            '/bla☮️/ble😝/bli' => '/bli',
            '/bla☮️/ble😝/bli/' => '/bli/',
            '/bla☮️/ble😝//bli/' => '/bli/',
            '/////bla☮️/ble😝//bli/' => '//bla☮️/ble😝/bli/',
            '\\\\/bla☮️/ble😝//bli/' => '//bla☮️/ble😝/bli/',
            '\\bla/ble/bli/' => '/bla/ble/bli/',
            'bla/ble/bli/' => 'bla/ble/bli/',
            '/bla☮️/ble😝//😝☮️/嗨مرحباً/Sa^lut*/مرحباً' => '/😝☮️/嗨مرحباً/Sa^lut*/مرحباً',
            '\\bla\\ble/😝☮️\\嗨مرحباً/Sa^lut*/مرحباً' => '/bla/ble/😝☮️/嗨مرحباً/Sa^lut*/مرحباً',
            '😝☮️/嗨مرحباً/Sa^lut*/مرحباً' => '😝☮️/嗨مرحباً/Sa^lut*/مرحباً',
            '/😝☮️/嗨مرحباً/Sa^lut*/مرحباً' => '/😝☮️/嗨مرحباً/Sa^lut*/مرحباً',
            '\\😝☮️/嗨مرحباً/Sa^lut*/مرحباً' => '/😝☮️/嗨مرحباً/Sa^lut*/مرحباً',
        ];

        foreach ($tests as $test => $expected) {
            $this->assertSame($expected, Filesystem::ensureRelative($test, '/bla☮️/ble😝', '/'), 'test ' . $test);
            $expected = str_replace('/', '\\', $expected);
            $this->assertSame($expected, Filesystem::ensureRelative($test, '/bla☮️/ble😝', '\\'), 'test ' . $test);
        }

        $tests = [
            '' => '',
            '/' => '/',
            'c:\\a\b\cbla' => 'c:/a/b/cbla',
            'c:\\a\b\c\\/bla' => 'bla',
            '/bla/' => '/bla/',
            '/bla//' => '/bla/',
            '//bla/' => '//bla/',
            '/blablebli' => '/blablebli',
            '/bla/blebli' => '/bla/blebli',
            'c:\\a\b\c\\/bla☮️/ble😝/bli' => 'bla☮️/ble😝/bli',
            'c:\\a\b\c\\/bla☮️/ble😝//😝☮️/嗨مرحباً/Sa^lut*/مرحباً' => 'bla☮️/ble😝/😝☮️/嗨مرحباً/Sa^lut*/مرحباً',
            'c:\\a\b\c\\\\bla\\ble/😝☮️\\嗨مرحباً/Sa^lut*/مرحباً' => 'bla/ble/😝☮️/嗨مرحباً/Sa^lut*/مرحباً',
            '😝☮️/嗨مرحباً/Sa^lut*/مرحباً' => '😝☮️/嗨مرحباً/Sa^lut*/مرحباً',
            '/😝☮️/嗨مرحباً/Sa^lut*/مرحباً' => '/😝☮️/嗨مرحباً/Sa^lut*/مرحباً',
            '\\😝☮️/嗨مرحباً/Sa^lut*/مرحباً' => '/😝☮️/嗨مرحباً/Sa^lut*/مرحباً',
        ];

        foreach ($tests as $test => $expected) {
            $this->assertSame($expected, Filesystem::ensureRelative($test, 'c:\\a\b\c\\', '/'), 'test ' . $test);
            $expected = str_replace('/', '\\', $expected);
            $this->assertSame($expected, Filesystem::ensureRelative($test, 'c:\\a\b\c\\', '\\'), 'test ' . $test);
        }
    }


    public function testEnsureAbsolute(): void
    {

        // simple test without base path

        $this->assertSame(Filesystem::normalize(ABSPATH), Filesystem::ensureAbsolute(''));
        $this->assertSame(Filesystem::normalize(ABSPATH), Filesystem::ensureAbsolute('/'));
        $this->assertSame(Filesystem::normalize(ABSPATH), Filesystem::ensureAbsolute('//'));

        $this->assertSame(Filesystem::normalize(ABSPATH), Filesystem::ensureAbsolute('//////'));
        $this->assertSame(Filesystem::normalize('/www/website/'), Filesystem::ensureAbsolute('', '/www/website/'));
        $this->assertSame(Filesystem::normalize('/www/website/'), Filesystem::ensureAbsolute('///', '/www/website/'));

        $this->assertSame('', Filesystem::ensureAbsolute('', '', '/'));
        $this->assertSame('.', Filesystem::ensureAbsolute('.', '', '\\'));
        $this->assertSame('a', Filesystem::ensureAbsolute('a', '', '\\'));
        $this->assertSame('\\', Filesystem::ensureAbsolute('/', '', '\\'));
        $this->assertSame('/', Filesystem::ensureAbsolute('/', '', '/'));

        // simple test with base path ``
        $tests = [
            '' => '',
            '/' => '/',
            'bla' => 'bla',
            '/bla' => '/bla',
            '/bla/' => '/bla/',
            '/blablebli' => '/blablebli',
            'bla/ble/bli' => 'bla/ble/bli',
            '/bla/ble/bli' => '/bla/ble/bli',
            '/bla//ble/bli' => '/bla/ble/bli',
            '/bla/ble/bli/' => '/bla/ble/bli/',
            'bla/ble/bli/' => 'bla/ble/bli/',
        ];

        foreach ($tests as $test => $expected) {
            $this->assertSame($expected, Filesystem::ensureRelative($test, '', '/'), 'test ' . $test);
            $expected = str_replace('/', '\\', $expected);
            $this->assertSame($expected, Filesystem::ensureRelative($test, '', '\\'), 'test ' . $test);
        }

        // simple test with base path `/`

        $tests = [
            '' => '/',
            '/' => '/',
            'bla' => '/bla',
            '/bla' => '/bla',
            '/bla/' => '/bla/',
            '//bla/' => '//bla/',
            '/blablebli' => '/blablebli',
            'bla/ble/bli' => '/bla/ble/bli',
            '/bla/ble/bli' => '/bla/ble/bli',
            '/bla/ble/bli/' => '/bla/ble/bli/',
            '/bla//ble//bli/' => '/bla/ble/bli/',
            '//bla/ble/bli/' => '//bla/ble/bli/',
            'bla/ble/bli/' => '/bla/ble/bli/',
            '😝☮️/嗨مرحباً/Sa^lut*/مرحباً' => '/😝☮️/嗨مرحباً/Sa^lut*/مرحباً',
            '/😝☮️/嗨مرحباً/Sa^lut*/مرحباً' => '/😝☮️/嗨مرحباً/Sa^lut*/مرحباً',
            '\\😝☮️/嗨مرحباً/Sa^lut*/مرحباً' => '/😝☮️/嗨مرحباً/Sa^lut*/مرحباً',
        ];

        foreach ($tests as $test => $expected) {
            $this->assertSame($expected, Filesystem::ensureAbsolute($test, '/', '/'), 'test ' . $test);
            $expected = str_replace('/', '\\', $expected);
            $this->assertSame($expected, Filesystem::ensureAbsolute($test, '/', '\\'), 'test ' . $test);
        }

        // simple test with base path `/bla`

        $tests = [
            '' => '/bla',
            '/' => '/bla/',
            'bla' => '/bla/bla',
            '/bla' => '/bla',
            '/bla/' => '/bla/',
            '/bla//' => '/bla/',
            '//bla/' => '/bla/bla/',
            '/blablebli' => '/bla/blablebli',
            'bla/ble/bli' => '/bla/bla/ble/bli',
            '/bla/ble/bli' => '/bla/ble/bli',
            '/bla/ble/bli/' => '/bla/ble/bli/',
            '/bla//ble/bli/' => '/bla/ble/bli/',
            '//bla/ble/bli/' => '/bla/bla/ble/bli/',
            '\\\\bla/ble/bli/' => '/bla/bla/ble/bli/',
            '\\bla/ble/bli/' => '/bla/ble/bli/',
            'bla/ble/bli/' => '/bla/bla/ble/bli/',
            '/bla/😝☮️/嗨مرحباً/Sa^lut*/مرحباً' => '/bla/😝☮️/嗨مرحباً/Sa^lut*/مرحباً',
            '\\bla\\😝☮️/嗨مرحباً/Sa^lut*/مرحباً' => '/bla/😝☮️/嗨مرحباً/Sa^lut*/مرحباً',
            '😝☮️/嗨مرحباً/Sa^lut*/مرحباً' => '/bla/😝☮️/嗨مرحباً/Sa^lut*/مرحباً',
            '/😝☮️/嗨مرحباً/Sa^lut*/مرحباً' => '/bla/😝☮️/嗨مرحباً/Sa^lut*/مرحباً',
            '\\😝☮️/嗨مرحباً/Sa^lut*/مرحباً' => '/bla/😝☮️/嗨مرحباً/Sa^lut*/مرحباً',
        ];

        foreach ($tests as $test => $expected) {
            $this->assertSame($expected, Filesystem::ensureAbsolute($test, '/bla', '/'), 'test ' . $test);
            $expected = str_replace('/', '\\', $expected);
            $this->assertSame($expected, Filesystem::ensureAbsolute($test, '/bla', '\\'), 'test ' . $test);
        }

        // simple test with base path `/bla/ble`

        $tests = [
            '' => '/bla/ble',
            '/' => '/bla/ble/',
            'bla' => '/bla/ble/bla',
            '/bla' => '/bla/ble/bla',
            '/bla/' => '/bla/ble/bla/',
            '/bla//' => '/bla/ble/bla/',
            '//bla/' => '/bla/ble/bla/',
            '/blablebli' => '/bla/ble/blablebli',
            '/bla/blebli' => '/bla/ble/bla/blebli',
            '/bla/ble/bli' => '/bla/ble/bli',
            'bla/ble/bli' => '/bla/ble/bla/ble/bli',
            '/bla/ble/bli/' => '/bla/ble/bli/',
            '/bla//ble/bli/' => '/bla/ble/bli/',
            '//bla/ble/bli/' => '/bla/ble/bla/ble/bli/',
            '\\\\bla/ble/bli/' => '/bla/ble/bla/ble/bli/',
            '\\bla/ble/bli/' => '/bla/ble/bli/',
            'bla/ble/bli/' => '/bla/ble/bla/ble/bli/',
            '/bla/ble/😝☮️/嗨مرحباً/Sa^lut*/مرحباً' => '/bla/ble/😝☮️/嗨مرحباً/Sa^lut*/مرحباً',
            '\\bla\\ble/😝☮️\\嗨مرحباً/Sa^lut*/مرحباً' => '/bla/ble/😝☮️/嗨مرحباً/Sa^lut*/مرحباً',
            '😝☮️/嗨مرحباً/Sa^lut*/مرحباً' => '/bla/ble/😝☮️/嗨مرحباً/Sa^lut*/مرحباً',
            '/😝☮️/嗨مرحباً/Sa^lut*/مرحباً' => '/bla/ble/😝☮️/嗨مرحباً/Sa^lut*/مرحباً',
            '\\😝☮️/嗨مرحباً/Sa^lut*/مرحباً' => '/bla/ble/😝☮️/嗨مرحباً/Sa^lut*/مرحباً',
        ];

        foreach ($tests as $test => $expected) {
            $this->assertSame($expected, Filesystem::ensureAbsolute($test, '/bla/ble', '/'), 'test ' . $test);
            $expected = str_replace('/', '\\', $expected);
            $this->assertSame($expected, Filesystem::ensureAbsolute($test, '/bla/ble', '\\'), 'test ' . $test);
        }

        $tests = [
            '' => '/bla☮️/ble😝',
            '/' => '/bla☮️/ble😝/',
            'bla' => '/bla☮️/ble😝/bla',
            '/bla' => '/bla☮️/ble😝/bla',
            '/bla/' => '/bla☮️/ble😝/bla/',
            '/bla//' => '/bla☮️/ble😝/bla/',
            '//bla/' => '/bla☮️/ble😝/bla/',
            '/blablebli' => '/bla☮️/ble😝/blablebli',
            '/bla/blebli' => '/bla☮️/ble😝/bla/blebli',
            '/bla☮️/ble😝/bli' => '/bla☮️/ble😝/bli',
            '/bla☮️/ble😝/bli/' => '/bla☮️/ble😝/bli/',
            '/bla☮️/ble😝//bli/' => '/bla☮️/ble😝/bli/',
            '/////bla☮️/ble😝//bli/' => '/bla☮️/ble😝/bla☮️/ble😝/bli/',
            '\\\\/bla☮️/ble😝//bli/' => '/bla☮️/ble😝/bla☮️/ble😝/bli/',
            '\\bla/ble/bli/' => '/bla☮️/ble😝/bla/ble/bli/',
            'bla/ble/bli/' => '/bla☮️/ble😝/bla/ble/bli/',
            '/bla☮️/ble😝//😝☮️/嗨مرحباً/Sa^lut*/مرحباً' => '/bla☮️/ble😝/😝☮️/嗨مرحباً/Sa^lut*/مرحباً',
            '\\bla\\ble/😝☮️\\嗨مرحباً/Sa^lut*/مرحباً' => '/bla☮️/ble😝/bla/ble/😝☮️/嗨مرحباً/Sa^lut*/مرحباً',
            '😝☮️/嗨مرحباً/Sa^lut*/مرحباً' => '/bla☮️/ble😝/😝☮️/嗨مرحباً/Sa^lut*/مرحباً',
            '/😝☮️/嗨مرحباً/Sa^lut*/مرحباً' => '/bla☮️/ble😝/😝☮️/嗨مرحباً/Sa^lut*/مرحباً',
            '\\😝☮️/嗨مرحباً/Sa^lut*/مرحباً' => '/bla☮️/ble😝/😝☮️/嗨مرحباً/Sa^lut*/مرحباً',
        ];

        foreach ($tests as $test => $expected) {
            $this->assertSame($expected, Filesystem::ensureAbsolute($test, '/bla☮️/ble😝', '/'), 'test ' . $test);
            $expected = str_replace('/', '\\', $expected);
            $this->assertSame($expected, Filesystem::ensureAbsolute($test, '/bla☮️/ble😝', '\\'), 'test ' . $test);
        }

        $tests = [
            '' => '//😭bla☮️/ble😝',
            '/' => '//😭bla☮️/ble😝/',
            'bla' => '//😭bla☮️/ble😝/bla',
            '/bla' => '//😭bla☮️/ble😝/bla',
            '/bla/' => '//😭bla☮️/ble😝/bla/',
            '/bla//' => '//😭bla☮️/ble😝/bla/',
            '//bla/' => '//😭bla☮️/ble😝/bla/',
            '/blablebli' => '//😭bla☮️/ble😝/blablebli',
            '/bla/blebli' => '//😭bla☮️/ble😝/bla/blebli',

            '/bla☮️/ble😝/bli' => '//😭bla☮️/ble😝/bla☮️/ble😝/bli',
            '/bla☮️/ble😝/bli/' => '//😭bla☮️/ble😝/bla☮️/ble😝/bli/',
            '/bla☮️/ble😝//bli/' => '//😭bla☮️/ble😝/bla☮️/ble😝/bli/',

            '//😭bla☮️/ble😝/bli' => '//😭bla☮️/ble😝/bli',
            '//😭bla☮️/ble😝bli/' => '//😭bla☮️/ble😝/😭bla☮️/ble😝bli/',
            '//😭bla☮️/ble😝//bli/' => '//😭bla☮️/ble😝/bli/',

            '/////bla☮️/ble😝//bli/' => '//😭bla☮️/ble😝/bla☮️/ble😝/bli/',
            '\\\\/bla☮️/ble😝//bli/' => '//😭bla☮️/ble😝/bla☮️/ble😝/bli/',
            '\\bla/ble/bli/' => '//😭bla☮️/ble😝/bla/ble/bli/',
            'bla/ble/bli/' => '//😭bla☮️/ble😝/bla/ble/bli/',
            '/bla☮️/ble😝//😝☮️/嗨مرحباً/Sa^lut*/مرحباً' => '//😭bla☮️/ble😝/bla☮️/ble😝/😝☮️/嗨مرحباً/Sa^lut*/مرحباً',
            '\\bla\\ble/😝☮️\\嗨مرحباً/Sa^lut*/مرحباً' => '//😭bla☮️/ble😝/bla/ble/😝☮️/嗨مرحباً/Sa^lut*/مرحباً',
            '😝☮️/嗨مرحباً/Sa^lut*/مرحباً' => '//😭bla☮️/ble😝/😝☮️/嗨مرحباً/Sa^lut*/مرحباً',
            '/😝☮️/嗨مرحباً/Sa^lut*/مرحباً' => '//😭bla☮️/ble😝/😝☮️/嗨مرحباً/Sa^lut*/مرحباً',
            '\\😝☮️/嗨مرحباً/Sa^lut*/مرحباً' => '//😭bla☮️/ble😝/😝☮️/嗨مرحباً/Sa^lut*/مرحباً',
        ];

        foreach ($tests as $test => $expected) {
            $this->assertSame($expected, Filesystem::ensureAbsolute($test, '//😭bla☮️/ble😝', '/'), 'test ' . $test);
            $expected = str_replace('/', '\\', $expected);
            $this->assertSame($expected, Filesystem::ensureAbsolute($test, '//😭bla☮️/ble😝', '\\'), 'test ' . $test);
        }

        $tests = [
            '' => 'c://a/b/c/',
            '/' => 'c://a/b/c/',
            'c:\\\\a\b\cbla' => 'c://a/b/c/c:/a/b/cbla',
            'c:\\\\a\b\c\\/bla' => 'c://a/b/c/bla',
            '/bla/' => 'c://a/b/c/bla/',
            '/bla//' => 'c://a/b/c/bla/',
            '//bla/' => 'c://a/b/c/bla/',
            '/blablebli' => 'c://a/b/c/blablebli',
            '/bla/blebli' => 'c://a/b/c/bla/blebli',
            'c:\\\\a\b\c\\/bla☮️/ble😝/bli' => 'c://a/b/c/bla☮️/ble😝/bli',
            'c:\\\\a\b\c\\/bla☮️/ble😝//😝☮️/嗨مرحباً/Sa^lut*/مرحباً' => 'c://a/b/c/bla☮️/ble😝/😝☮️/嗨مرحباً/Sa^lut*/مرحباً',
            'c:\\\\a\b\c\\\\bla\\ble/😝☮️\\嗨مرحباً/Sa^lut*/مرحباً' => 'c://a/b/c/bla/ble/😝☮️/嗨مرحباً/Sa^lut*/مرحباً',
            '😝☮️/嗨مرحباً/Sa^lut*/مرحباً' => 'c://a/b/c/😝☮️/嗨مرحباً/Sa^lut*/مرحباً',
            '/😝☮️/嗨مرحباً/Sa^lut*/مرحباً' => 'c://a/b/c/😝☮️/嗨مرحباً/Sa^lut*/مرحباً',
            '\\😝☮️/嗨مرحباً/Sa^lut*/مرحباً' => 'c://a/b/c/😝☮️/嗨مرحباً/Sa^lut*/مرحباً',
        ];

        foreach ($tests as $test => $expected) {
            $this->assertSame($expected, Filesystem::ensureAbsolute($test, 'c:\\\\a\b\c\\', '/'), 'test ' . $test);
            $expected = str_replace('/', '\\', $expected);
            $this->assertSame($expected, Filesystem::ensureAbsolute($test, 'c:\\\\a\b\c\\', '\\'), 'test ' . $test);
        }
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

        // test `listFiles` option
        $stats = Filesystem::getFileStats(__DIR__ . '/test' . $i, '*', ['listFiles' => true]);
        $this->assertSame(true, !empty($stats['list']));
        // @phpstan-ignore-next-line
        $this->assertSame(3, count($stats['list']));

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
