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

class CacheTest extends TestCase
{
    /**
     * @inheritDoc
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        static::initCacher();
    }

    /**
     * @inheritDoc
     */
    public static function tearDownAfterClass(): void
    {
        parent::setUpBeforeClass();
        Filesystem::removeDir(static::getCachePath());
    }

    /**
     * Get cache path
     *
     * @param string|null $path
     *
     * @return string
     */
    protected static function getCachePath(string $path = null): string
    {
        if ($path) {
            return Filesystem::concat(WP_CONTENT_DIR, '_gbg_cake5_tests_cache', $path);
        }
        return Filesystem::concat(WP_CONTENT_DIR, '_gbg_cake5_tests_cache');
    }

    /**
     * Init Cache config
     *
     * @throws \Exception
     */
    protected static function initCacher(): void
    {
        $config = Cache::getConfig();
        $config['Gbg/Cake5.testsCache'] = [
            'path'     => static::getCachePath(),
            'duration' => '+1 hour',
            'scope'    => 'app'
        ];

        Cache::setConfig($config);
    }

    /**
     * Change file time to test garbage collect
     *
     * @param string $file
     * @param string $duration
     *
     * @return bool
     */
    protected static function changeFileTime(string $file, string $duration): bool
    {
        if (($content = file_get_contents($file)) === false) {
            return false;
        }

        $content = explode(PHP_EOL, $content);
        $content[0] = strtotime($duration);
        file_put_contents($file, implode(PHP_EOL, $content));

        return true;
    }

    /**
     * Test cache
     *
     * @return void
     * @throws \Exception
     */
    public function testCache(): void
    {
        if (!$cache = Cache::get('Gbg/Cake5.testsCache')) {
            throw new \Exception('Cache not found');
        }

        $cache->write('key1', ['value1']);
        $cache->write('key2', ['value2']);

        $currentFile1 = static::getCachePath('file___1hour___gbg-cake5-tests-cache___key1___app');
        $currentFile2 = static::getCachePath('file___1hour___gbg-cake5-tests-cache___key2___app');

        $currentFileX = static::getCachePath('x.txt');
        file_put_contents($currentFileX, 'x');

        $value1 = $cache->read('key1');
        static::changeFileTime($currentFile1, '-5 hours');
        $value2 = $cache->read('key2');

        $this->assertEquals(true, file_exists($currentFile1));
        $this->assertEquals(true, file_exists($currentFile2));

        $this->assertEquals(['value1'], $value1);
        $this->assertEquals(['value2'], $value2);

        $cache->delete('key1');
        $this->assertEquals(false, file_exists($currentFile1));
        $this->assertEquals(true, file_exists($currentFile2));

        $cache->write('key1', ['value1'])->write('key2', ['value2']);

        $this->assertEquals(true, file_exists($currentFile1));
        $this->assertEquals(true, file_exists($currentFile2));

        $cache->clear();

        $this->assertEquals(false, file_exists($currentFile1));
        $this->assertEquals(false, file_exists($currentFile2));

        $cache->write('key1', ['value1'])->write('key2', ['value2']);

        $this->assertEquals(true, file_exists($currentFile1));
        $this->assertEquals(true, file_exists($currentFile2));

        $cache->write('key1', ['value1'])->write('key2', ['value2']);

        $this->assertEquals(true, file_exists($currentFile1));
        $this->assertEquals(true, file_exists($currentFile2));

        Cache::garbageCollect();

        $this->assertEquals(true, file_exists($currentFile1));
        $this->assertEquals(true, file_exists($currentFile2));

        static::changeFileTime($currentFile1, '-5 hours');

        Cache::garbageCollect();

        $this->assertEquals(false, file_exists($currentFile1));
        $this->assertEquals(true, file_exists($currentFile2));

        static::changeFileTime($currentFile2, '-5 hours');
        Cache::garbageCollect();

        $this->assertEquals(false, file_exists($currentFile1));
        $this->assertEquals(false, file_exists($currentFile2));
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testCacheConfig(): void
    {
        $config = Cache::getConfig();
        $this->assertArrayHasKey('Gbg/Cake5.testsCache', $config);

        if (!$cache = Cache::get('Gbg/Cake5.testsCache')) {
            $this->fail('Cache not found');
        }
        $this->assertEquals('file___1hour___gbg-cake5-tests-cache___app', $cache->getConfigKey());

        // test dynamic cache
        if (!$cache = Cache::get('Gbg/Cake5.testsCache')) {
            $this->fail('Cache not found');
        }
        $this->assertEquals('file___1hour___gbg-cake5-tests-cache___app', $cache->getConfigKey());

        $config = Cache::getConfig();
        $config['Gbg/Cake5.testsCache'] = [
            'path'     => static::getCachePath(),
            'duration' => '1 hour',
            'scope'    => 'app'
        ];

        Cache::setConfig($config);

        Cache::addConfig('Gbg/Cake5.testsCache', [
            'path'     => static::getCachePath(),
            'duration' => '1 hour',
            'scope'    => 'app'
        ]);

        if (!$cache = Cache::get('Gbg/Cake5.testsCache')) {
            $this->fail('Cache not found');
        }
        $this->assertEquals('file___1hour___gbg-cake5-tests-cache___app', $cache->getConfigKey());

        $exceptionRaised = false;
        try {
            Cache::addConfig('test-exception1', [
                'path'     => static::getCachePath(),
                'duration' => '+1 year',
                'className' => 'non-existing-class',
                'scope'    => 'app'
            ]);
        } catch (\Exception $ex) {
            $exceptionRaised = true;
        }

        $this->assertEquals(true, $exceptionRaised);
        $this->assertEquals(null, Cache::get('test-exception1'));

        $exceptionRaised = false;
        try {
            Cache::addConfig('test-exception2', [
                'path'     => static::getCachePath(),
                'duration' => '',
                'scope'    => 'app'
            ]);
        } catch (\Exception $ex) {
            $exceptionRaised = true;
        }
        $this->assertEquals(true, $exceptionRaised);
        $this->assertEquals(null, Cache::get('test-exception2'));

        // test constructor exception
        $exceptionRaised = false;
        try {
            $cache = new Cache('test-exception3', [
                'path'     => static::getCachePath(),
                'duration' => '1 hour',
                'scope'    => 'session'
            ]);
        } catch (\Exception $ex) {
            $exceptionRaised = true;
        }
        $this->assertEquals(true, $exceptionRaised);
        $this->assertEquals(null, Cache::get('test-exception3'));

        // test session cache without id
        $cache = $exceptionRaised = false;
        try {
            $cache = Cache::get('Gbg/Cake5.testsCache', [
                'path'     => static::getCachePath(),
                'duration' => '1 hour',
                'scope'    => 'session'
            ]);
            $cache?->write('x', 'y');
        } catch (\Exception $ex) {
            $exceptionRaised = true;
        }
        $this->assertEquals(true, $exceptionRaised);

        if ($cache) {
            $exceptionRaised = false;
            try {
                $value = $cache->read('x');
            } catch (\Exception $ex) {
                $exceptionRaised = true;
            }
            $this->assertEquals(true, $exceptionRaised);
        }

        wp_set_current_user(1, 'toto');
        $cache = Cache::get('Gbg/Cake5.testsCache', [
            'path'     => static::getCachePath(),
            'duration' => '100',
            'scope'    => 'user'
        ]);
        // @todo : create a user at beginning of test
        $this->assertEquals('user_' . get_current_user_id(), $cache?->getSuffix());

        $this->assertEquals(static::getCachePath(), $cache?->getPath());
        $this->assertEquals('Gbg/Cake5.testsCache', $cache?->getName());
        $this->assertEquals('file___100___gbg-cake5-tests-cache', $cache?->getPrefix());
    }

    public function testCacheGet(): void
    {
        if ($cache = Cache::get('Non-existing')) {
            $this->fail('Cache found (but should not)');
        }
        $this->assertTrue(true);
    }

    public function testReadWrite(): void
    {
        if (!$cache = Cache::get('Gbg/Cake5.testsCache')) {
            $this->fail('Cache not found');
        }
        $cache->write('key1', 'value1');
        $this->assertEquals('value1', $cache->read('key1'));
        $this->assertEquals(null, $cache->read('key2'));
        $cache->write('key2', 'value2');
        $this->assertEquals('value2', $cache->read('key2'));
        $cache->write('', 'valueX');
        $this->assertEquals(null, $cache->read(''));

        $array = [
            'a' => 'b',
            'c' => 'd',
            'e' => [
                'f' => 'g',
                'h' => [
                    'i' => 'j'
                ],
            ],
        ];

        $cache->write('array', $array);
        $this->assertEquals($array, $cache->read('array'));
        $this->assertEquals(['i' => 'j'], $cache->read('array.e.h'));

        $cache->write('array.e.h', ['k' => 'l']);
        $this->assertEquals(['k' => 'l'], $cache->read('array.e.h'));

        $cache->write('array.c', ['k' => 'l']);
        $this->assertEquals(['k' => 'l'], $cache->read('array.c'));

        $cache->write('var1', 'x');
        $cache->write('var1.sub', 'y');
        $this->assertEquals(['sub' => 'y'], $cache->read('var1'));
    }

    public function testCacheStats(): void
    {
        if (!$cache = Cache::get('Gbg/Cake5.testsCache')) {
            $this->fail('Cache not found');
        }
        $cache->write('key1', 'value1');
        $cache->write('key2', 'value2');
        $cache->write('key3', str_pad('value3', 100000, '*'));

        $files = Filesystem::list($cache->getPath());
        $files = array_values(array_filter($files, function ($file) {
            return !str_ends_with($file, 'x.txt');
        }));
        touch($files[0], strtotime('2020-01-01 00:00:00'));

        $cacheStats = $cache->getStats();
        // @todo : calculate rather than use fixed values
        $this->assertEquals(count($files), $cacheStats['count']);
        $this->assertEquals('2020-01-01 00:00:00', $cacheStats['oldestStd']);
        $this->assertEquals('98KB', $cacheStats['biggest']);
    }

    public function testRemember(): void
    {
        if (!$cache = Cache::get('Gbg/Cake5.testsCache')) {
            $this->fail('Cache not found');
        }

        $key = 'fhskhjdfkjhsdfqsjhdfkjqhdfkshjdlkqjfhgkqjfhgjkqdfhg';

        $passed = false;
        $value = $cache->remember($key, function () use (&$passed) {
            $passed  = true;
            return 'value';
        });
        $this->assertEquals(true, $passed);

        $passed = false;
        $value = $cache->remember($key, function () use (&$passed) {
            $passed  = true;
            return 'value';
        });
        $this->assertEquals(false, $passed);
        $this->assertEquals('value', $value);

        // test error
        $error = false;
        try {
            $value = $cache->remember(
                'a.b',
                function () {
                    return 'value';
                }
            );
        } catch (\Exception $ex) {
            $error = true;
        }
        $this->assertEquals(true, $error);
    }

    public function testGarbageCollect(): void
    {
        if (!$cache = Cache::get('Gbg/Cake5.testsCache')) {
            $this->fail('Cache not found');
        }
        Cache::garbageCollect();

        $files = Filesystem::list($cache->getPath());
        $files = array_values(array_filter($files, function ($file) {
            return !str_ends_with($file, 'x.txt');
        }));

        $collected = [];
        for ($i = 0; $i <= 1; $i++) {
            if ($content = file_get_contents($files[$i])) {
                $collected[] = $files[$i];
                $content = Text::explodeNl($content);
                $content[0] = strtotime('2001-01-01 00:00:00');
                file_put_contents($files[$i], implode("\n", $content));
            }
        }
        $value = Cache::garbageCollect();
        $this->assertEquals(count($collected), $value['deletedCount']);

        for ($i = 0; $i < count($collected); $i++) {
            $this->assertEquals(false, file_exists($collected[$i]));
        }
    }

    public function testStaticClear(): void
    {
        $config = Cache::getConfig();
        $config += [
            'Gbg/Cake5.testsCacheSession' => [
                'path'     => static::getCachePath(),
                'duration' => '+1 day',
                'scope'    => 'session',
                'id'       => 1002
            ],
            'Gbg/Cake5.testsCacheUser' => [
                'path'     => static::getCachePath(),
                'duration' => '+1 day',
                'scope'    => 'user',
                'id'       => 1001
            ]
        ];
        Cache::setConfig($config);

        // test `clearUser`
        Cache::get('Gbg/Cake5.testsCacheUser')?->write('key1', 'value1')->write('key2', 'value2');

        $files1 = Filesystem::list(static::getCachePath(), '*_user_*');
        Cache::clearUser(1001);
        $files2 = Filesystem::list(static::getCachePath(), '*_user_*');
        $this->assertEquals(count($files1) - 2, count($files2));

        Cache::get('Gbg/Cake5.testsCacheUser')?->write('key1', 'value1')->write('key2', 'value2');

        $files1 = Filesystem::list(static::getCachePath(), '*_user_*');
        Cache::clearUsers();
        $files2 = Filesystem::list(static::getCachePath(), '*_user_*');
        $this->assertEquals(count($files1) - 2, count($files2));

        // test `clearSession`
        Cache::get('Gbg/Cake5.testsCacheSession')?->write('key1', 'value1')->write('key2', 'value2');

        $files1 = Filesystem::list(static::getCachePath(), '*_sess_*');
        Cache::clearSession('1002');
        $files2 = Filesystem::list(static::getCachePath(), '*_sess_*');
        $this->assertEquals(count($files1) - 2, count($files2));

        Cache::get('Gbg/Cake5.testsCacheSession')?->write('key1', 'value1')->write('key2', 'value2');

        $files1 = Filesystem::list(static::getCachePath(), '*_sess_*');
        Cache::clearSessions();
        $files2 = Filesystem::list(static::getCachePath(), '*_sess_*');
        $this->assertEquals(count($files1) - 2, count($files2));

        // test `clearAll`
        Cache::get('Gbg/Cake5.testsCacheSession')?->write('key1', 'value1')->write('key2', 'value2');

        $files1 = Filesystem::list(static::getCachePath(), '*file__*');
        Cache::clearAll();
        $files2 = Filesystem::list(static::getCachePath(), '*file__*');
        $this->assertTrue(count($files1) > 0);
        $this->assertTrue(count($files2) === 0);
    }
}
