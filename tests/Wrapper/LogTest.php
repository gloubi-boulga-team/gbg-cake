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

use Cake5\ORM\TableRegistry;
use Gbg\Cake5\Orm\QueryTools;
use Gbg\Cake5\TestCase;

class LogTest extends TestCase
{
    /**
     * @inheritDoc
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        static::initialize();
    }

    /**
     * @inheritDoc
     */
    public static function tearDownAfterClass(): void
    {
        parent::setUpBeforeClass();
        static::finalize();
    }

    public static function initialize(): void
    {
        Filesystem::removeDir(static::getCachePath());
        Log::setDefaultLogPath(static::getCachePath());
        Log::setMinLoglevel('debug');
    }

    public static function finalize(): void
    {
        Filesystem::removeDir(static::getCachePath());
    }

    protected static function getCachePath(string $path = null): string
    {
        if ($path) {
            return Filesystem::concat(WP_CONTENT_DIR, '_gbg_cake5_tests_log', $path);
        }
        return Filesystem::concat(WP_CONTENT_DIR, '_gbg_cake5_tests_log');
    }

    public function testPath(): void
    {
        Log::setDefaultLogPath(static::getCachePath());
        $this->assertEquals(static::getCachePath() . DIRECTORY_SEPARATOR, Log::getDefaultLogPath());
    }

    public function testConfig(): void
    {
        $config = [
            'gbgcaketest' => [
                'file' => 'test',
                'levels' => ['notice', 'debug', 'info', 'warning', 'error'],
                'className' => 'Cake5\Log\Engine\FileLog',
                'path' => static::getCachePath() . DIRECTORY_SEPARATOR,
                'scopes' => ['gbgcaketest', 'default']
            ]
        ];

        $error = false;
        try {
            Log::setConfig($config);
        } catch (\Exception $ex) {
            $error = true;
        }

        $this->assertEquals(false, $error);

        try {
            Log::setConfig($config);
        } catch (\Exception $ex) {
            $error = true;
        }
        $this->assertEquals(true, $error);

        $config = Log::getConfig();
        /** @var array<string, array<string>> $config */
        $this->assertEquals('Cake5\Log\Engine\FileLog', $config['gbgcaketest']['className']);
    }

    public function testLog(): void
    {
        $begin = date('Y-m-d H:i:s');
        $line = __LINE__ + 1;
        Log::debug('testReadWrite debug', ['gbgcaketest']);
        Log::notice('testReadWrite notice', ['gbgcaketest']);
        Log::info('testReadWrite info', ['gbgcaketest']);
        Log::warning('testReadWrite warning', ['gbgcaketest']);
        Log::error('testReadWrite error', ['gbgcaketest']);
        $end = date('Y-m-d H:i:s');

        $file = static::getCachePath('test.log');
        $this->assertEquals(true, file_exists($file));

        $content = file_get_contents(static::getCachePath('test.log'));
        $this->assertNotEmpty($content);

        $this->assertTrue(str_contains($content, 'testReadWrite'));

        if (
            !str_contains($content, sprintf('%s debug: %s:%s', $begin, __FILE__, $line))
            && !str_contains($content, sprintf('%s debug: %s:%s', $end, __FILE__, $line))
        ) {
            $this->assertTrue(false);
        } else {
            $this->assertTrue(true);
        }

        if (
            !str_contains($content, sprintf('%s notice: %s:%s', $begin, __FILE__, $line + 1))
            && !str_contains($content, sprintf('%s notice: %s:%s', $end, __FILE__, $line + 1))
        ) {
            $this->assertTrue(false);
        } else {
            $this->assertTrue(true);
        }

        if (
            !str_contains($content, sprintf('%s info: %s:%s', $begin, __FILE__, $line + 2))
            && !str_contains($content, sprintf('%s info: %s:%s', $end, __FILE__, $line + 2))
        ) {
            $this->assertTrue(false);
        } else {
            $this->assertTrue(true);
        }

        if (
            !str_contains($content, sprintf('%s warning: %s:%s', $begin, __FILE__, $line + 3))
            && !str_contains($content, sprintf('%s warning: %s:%s', $end, __FILE__, $line + 3))
        ) {
            $this->assertTrue(false, 'testReadWrite #6');
        } else {
            $this->assertTrue(true, 'testReadWrite #6');
        }

        $this->assertTrue(str_contains($content, sprintf('%s error: %s:%s', $begin, __FILE__, $line + 4))
            || str_contains($content, sprintf('%s error: %s:%s', $end, __FILE__, $line + 4)));

        Log::setMinLoglevel('info');
        $level = Log::getMinLoglevel();
        $this->assertEquals('info', $level);

        Log::debug('testReadWrite debug-fail', ['gbgcaketest']);
        if (!$content = file_get_contents(static::getCachePath('test.log'))) {
            $this->assertTrue(false);
        } else {
            $this->assertFalse(str_contains('testReadWrite debug-fail', $content));
        }
    }

    public function testLogDefault(): void
    {
        Log::setMinLoglevel('debug');

        $begin = date('Y-m-d H:i:s');
        $line = __LINE__ + 1;
        Log::debugDefault('testReadWrite debug', 'default');
        Log::noticeDefault('testReadWrite notice', 'default');
        Log::infoDefault('testReadWrite info', 'default');
        Log::warningDefault('testReadWrite warning', 'default');
        Log::errorDefault('testReadWrite error', 'default');
        $end = date('Y-m-d H:i:s');

        $file = static::getCachePath('test.log');
        $this->assertEquals(true, file_exists($file));

        $content = file_get_contents(static::getCachePath('test.log'));
        $this->assertNotEmpty($content);

        $this->assertTrue(str_contains($content, 'testReadWrite'));

        if (
            !str_contains($content, sprintf("%s debug: %s:%s", $begin, __FILE__, $line))
            && !str_contains($content, sprintf("%s debug: %s:%s", $end, __FILE__, $line))
        ) {
            $this->assertTrue(false);
        } else {
            $this->assertTrue(true);
        }
        $this->assertTrue(str_contains($content, "testReadWrite debug\ndefault"));

        if (
            !str_contains($content, sprintf("%s notice: %s:%s", $begin, __FILE__, $line + 1))
            && !str_contains($content, sprintf("%s notice: %s:%s", $end, __FILE__, $line + 1))
        ) {
            $this->assertTrue(false);
        } else {
            $this->assertTrue(true);
        }
        $this->assertTrue(str_contains($content, "testReadWrite notice\ndefault"));

        if (
            !str_contains($content, sprintf("%s info: %s:%s", $begin, __FILE__, $line + 2))
            && !str_contains($content, sprintf("%s info: %s:%s", $end, __FILE__, $line + 2))
        ) {
            $this->assertTrue(false);
        } else {
            $this->assertTrue(true);
        }
        $this->assertTrue(str_contains($content, "testReadWrite info\ndefault"));

        if (
            !str_contains($content, sprintf("%s warning: %s:%s", $begin, __FILE__, $line + 3))
            && !str_contains($content, sprintf("%s warning: %s:%s", $end, __FILE__, $line + 3))
        ) {
            $this->assertTrue(false);
        } else {
            $this->assertTrue(true);
        }
        $this->assertTrue(str_contains($content, "testReadWrite warning\ndefault"));

        if (
            !str_contains($content, sprintf("%s error: %s:%s", $begin, __FILE__, $line + 4))
            && !str_contains($content, sprintf("%s error: %s:%s", $end, __FILE__, $line + 4))
        ) {
            $this->assertTrue(false);
        } else {
            $this->assertTrue(true);
        }
        $this->assertTrue(str_contains($content, "testReadWrite error\ndefault"));

        Log::setMinLoglevel('info');
        $level = Log::getMinLoglevel();
        $this->assertEquals('info', $level);

        Log::debug('testReadWrite debug-fail', ['gbgcaketest']);
        if ($content = file_get_contents(static::getCachePath('test.log'))) {
            $this->assertFalse(str_contains('testReadWrite debug-fail', $content));
        } else {
            $this->assertTrue(false);
        }
    }

    public function testClearStats(): void
    {
        if (!$files = Filesystem::listFiles(static::getCachePath(), '*.log')) {
            $this->assertTrue(false);
        } else {
            if ($stats = Log::getFileStats('gbgcaketest')) {
                $this->assertTrue($stats['count'] >= 1);
                $this->assertEquals(count($files), $stats['count']);
            } else {
                $this->assertTrue(false);
            }
        }

        Log::clear('gbgcaketest');

        if ($files = Filesystem::listFiles(static::getCachePath(), '*.log')) {
            if ($stats = Log::getFileStats('gbgcaketest')) {
                $this->assertTrue($stats['count'] === 0);
                $this->assertEquals(count($files), $stats['count']);
            }
        }

        $stats = Log::getFileStats('gbgcaketestx');
        $this->assertTrue($stats === null);

        $result = Log::clear('gbgcaketest');
        $this->assertTrue($result === 0);
    }

    public function testLogOnTheFly(): void
    {
        Log::info('testLogOnTheFly info', ['testLogOnTheFly']);
        $files = Filesystem::listFiles(static::getCachePath(), 'testLogOnTheFly.log');
        $this->assertTrue(!empty($files));
    }

    public function testObjects(): void
    {
        // test a query
        $query = TableRegistry::getTableLocator()->get('Wp.Posts')->find()->where(['999' => '999']);

        Log::info($query, ['testObjects']);

        if ($content = file_get_contents(static::getCachePath('testObjects.log'))) {
            $this->assertStringContainsString(QueryTools::getQueryCompiledSql($query), $content);
        } else {
            $this->assertTrue(false);
        }

        // test an object
        $object = (object)['a' => 'b', 'c' => 'd'];
        Log::info($object, ['testObjects']);

        if ($content = file_get_contents(static::getCachePath('testObjects.log'))) {
            $this->assertStringContainsString(print_r($object, true), $content);
        } else {
            $this->assertTrue(false);
        }

        // test an exception
        $object = new \Exception('test exception message');
        Log::info($object, ['testObjects']);

        if ($content = file_get_contents(static::getCachePath('testObjects.log'))) {
            $this->assertStringContainsString('test exception message', $content);
        } else {
            $this->assertTrue(false);
        }

        // test an array
        $array = ['a' => 'b', 'c' => 'd', 'e' => 'f'];
        Log::info($array, ['testObjects']);
        if ($content = file_get_contents(static::getCachePath('testObjects.log'))) {
            $this->assertStringContainsString(print_r($array, true), $content);
        } else {
            $this->assertTrue(false);
        }

        // test an array of mixed
        $array = ['a' => 'b', 'c' => 'd', 'e' => (object)['f' => 'g', 'h' => 'i']];
        Log::info($array, ['testObjects']);
        if ($content = file_get_contents(static::getCachePath('testObjects.log'))) {
            $this->assertStringContainsString(print_r($array, true), $content);
        } else {
            $this->assertTrue(false);
        }

        // test a string
        $string = 'blabla';
        Log::info($string, ['testObjects']);

        if ($content = file_get_contents(static::getCachePath('testObjects.log'))) {
            $this->assertStringContainsString($string, $content);
        } else {
            $this->assertTrue(false);
        }
    }
}
