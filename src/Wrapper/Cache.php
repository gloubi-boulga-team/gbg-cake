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

use Cake5\Cache\Cache as CakeCache;
use Cake5\Cache\Engine\FileEngine;
use Cake5\Utility\Hash;
use Cake5\Utility\Inflector;
use Exception;

/**
 *
 * Gbg CakePHP(tm) Cache wrapper Utility class
 *
 * Simplifies/enriches Cakephp Cache usage,
 *
 *          -> avoids requirement of cache config (if missing, will be created on-the-fly)
 *          -> allows cache config overwriting
 *
 *      Only use it with FileEngine (or test it before 😜)
 *
 * Important Garbage collector is automatically processed at cron time
 *
 * ### Example
 *
 *  Declare configuration in an early code (after `Gbg/Cake5.Cache.initialized` has been raised)
 *
 * ```
 *          Cache::addConfig('My/Plugin:myConfiguredCache', [
 *              'path'          => '/path/to/cache',
 *              'duration'      => '1 day',
 *              'scope'         => 'app'
 *          ]);
 * ```
 *
 * Later, when you need to manipulate cached data
 *
 * ```
 *          $cache = Cache::get('My/Plugin:myConfiguredCache');
 *          $cache->read('myKey');
 *          $cache->write('myKey', 'myValue');
 *
 *          $cacheValue = $cache->remember('myRememberKey', function() {
 *              return ['myRememberKey' => 'calculate that value here'];
 *          });
 * ```
 *
 * Configuration options :
 *
 *  - string    `scope`         (default "app") `user`, `session` or `app`
 *  - string    `duration`      (default "+1 day") cache life duration - must be compatible with strtotime()
 *  - string    `prefix`        Cached storage prefix. If not set, underscored $configureKey will be used
 *  - string    `className`     (default "File") only 'File' engine is supported for the moment
 *
 * ### Usage
 *
 * ```
 *      if ($cache && !$cacheValue = $cache->read('myCacheKey')) {
 *          $cacheValue = ['myArrayKey' => 'calculate that value here'];
 *          $cache->write('myCacheKey', $value);
 *          // -> will write $value in a new file app_my_cache_prefix_myCacheKey
 *      }
 *
 *      ... is equivalent to
 *
 *      if ($cache) {
 *          $cacheValue = $cache->remember('myCacheKey', function() {
 *              return ['myArrayKey' => 'calculate that value here'];
 *          });
 *      }
 * ```
 */

/** @phpstan-consistent-constructor */
// Overriding constructor may require overriding `get` function

class Cache
{
    /**
     * Cache key separator
     *
     * @var string
     */
    public static string $separator = '___';

    /**
     * Config key automatically created at runtime from passed $options
     *
     * @var string
     */
    protected string $configKey;

    /**
     * Cache name
     *
     * @var string
     */
    protected string $name;

    /**
     * Cache filename suffix if any
     *
     * @var string
     */
    protected string $suffix;

    /**
     * Cache filename prefix if any
     *
     * @var string
     */
    protected string $prefix;

    /**
     * Cache original config
     *
     * @var array<string, string|int|bool>
     */
    protected array $originalConfig = [];

    /**
     * Static cache for already loaded caches
     *
     * @var array<string, static>
     */
    protected static array $loadedCaches = [];

    /**
     * Some cache can not be writable (ex : Session cache created without a session Id)
     *
     * @var bool
     */
    protected bool $writable = true;

    /**
     * Some cache can not be writable (ex : Session cache created without a session Id)
     *
     * @var string|null
     */

    protected ?string $writableError = null;

    /**
     * Get a preconfigured Cache instance
     *
     * Configuration must have been declared using Cache::setConfigurations or Cache::addConfiguration
     *
     * ### Example
     * ```
     *      $cache = Cache::get('Gbg/Core:sessions')
     *      $cache->write('sessionKey', 'sessionVal');
     * ```
     *
     * @param string $key
     * @param array<string, mixed> $config
     *
     * @return static|null
     * @throws Exception
     */
    public static function get(string $key, array $config = []): static|null
    {

        $loadedKey = $key . '-' . print_r($config, true);

        if ($cache = static::$loadedCaches[$loadedKey] ?? null) {
            return $cache;
        }

        if (empty(static::$configs[$key])) {
            return null;
        }

        return (static::$loadedCaches[$loadedKey] = new static($key, $config));
    }

    /**
     * Declared configurations
     *
     * @var array<string, array<string, string|int|bool>>
     */
    protected static array $configs = [];

    /**
     * Set configurations
     *
     * @param array<string, array<string, string|int|bool>> $configs
     * @throws Exception
     */
    public static function setConfig(array $configs): void
    {
        static::$configs = [];
        foreach ($configs as $configKey => $configVal) {
            static::$configs[$configKey] = static::normalizeConfig($configKey, $configVal);
            /** @var string $path */
            $path = static::$configs[$configKey]['path'];
            Filesystem::ensureDir($path);
            Filesystem::htdeny($path);
        }
    }

    /**
     * Get declared configurations
     *
     * @return array<string, array<string, string|int|bool>>
     */
    public static function getConfig(): array
    {
        return static::$configs;
    }

    /**
     * @return string
     */
    public function getConfigKey(): string
    {
        return $this->configKey;
    }

    /**
     * Add a configuration
     *
     * @param string $key
     * @param array<string, string|int|bool> $config
     *
     * @throws Exception
     */
    public static function addConfig(string $key, array $config): void
    {
        static::$configs[$key] = static::normalizeConfig($key, $config);
    }

    /**
     * Ensure config is complete
     *
     * @param string $key
     * @param array<string, string|int|bool> $config
     *
     * @return array<string, string|int|bool>
     *
     * @throws Exception
     */
    public static function normalizeConfig(string $key, array $config): array
    {
        $config += [
            'name'      => $config['name'] ?? $key,
            'className' => FileEngine::class,
            'duration'  => '+1 day',
            'encoding'  => 'serialize',
            'prefix'    => strtolower(Inflector::underscore($key)),
            'suffix'    => '',
        ];
        foreach (['scope', 'duration', 'encoding', 'path'] as $attr) {
            if (empty($config[$attr])) {
                throw new Exception(
                    sprintf(
                        'Cache `%s` : an empty value for `%s` is not allowed.',
                        esc_html($key),
                        esc_html($attr)
                    ),
                    500
                );
            }
        }

        if ($config['className'] !== FileEngine::class) {
            throw new Exception('Gbg Cache helper only supports `File` engine.', 500);
        }

        return $config;
    }

    /**
     * Constructor
     *
     * @param string $configureKey
     * @param array<string, mixed> $config
     *
     * @throws Exception
     */
    public function __construct(string $configureKey, array $config = null)
    {
        if (!$originalConfig = (static::$configs[$configureKey] ?? null)) {
            throw new Exception(sprintf('Cache `%s` not configured', esc_html($configureKey)));
        }

        $config = $config + $originalConfig;
        if ($config['scope'] === 'session' && !isset($config['id'])) {
            $this->writable = false;
            $this->writableError =
                'Gbg Cache with scope `session` needs the session id to be passed in config[\'id\'].';
        }

        if ($config['scope'] === 'user' && !isset($config['id'])) {
            $config['id'] = get_current_user_id();
        }

        $this->originalConfig = $config;
        $this->name = $config['name'] ?? $configureKey;
        $this->suffix = static::sanitizeFilePart($config['suffix'] ?? '');

        // build real suffix
        if ($config['scope'] === 'session') {
            $this->suffix .= (empty($this->suffix) ? '' : static::$separator) . 'sess_' . ($config['id'] ?? '*');
        } elseif ($config['scope'] === 'user') {
            $this->suffix .= (empty($this->suffix) ? '' : static::$separator) . 'user_' . ($config['id'] ?? '*');
        } else {
            $this->suffix .= (empty($this->suffix) ? '' : static::$separator) . $config['scope'];
        }

        // normalize suffix and prefix
        $this->prefix = static::sanitizeFilePart($config['prefix']);

        $classKey = array_search($config['className'], CakeCache::getDsnClassMap());
        $durationStr = Inflector::underscore(preg_replace("/[^A-Za-z0-9]/", '_', $config['duration']));

        $this->prefix = ($classKey ? strtolower($classKey) . static::$separator : '') .
            static::sanitizeFilePart($durationStr, '') . static::$separator .
            $this->prefix;

        // build real duration
        if (ctype_digit(strval($config['duration']))) {
            $config['duration'] = '+' . $config['duration'] . ' seconds';
        }

        // add storage engine name to cache path
        $this->configKey = $this->prefix . static::$separator . $config['scope'];
        if (!in_array($this->configKey, CakeCache::configured())) {
            // create new cache config and register it to CakeCache
            CakeCache::setConfig($this->configKey, [
                'className' => $config['className'],
                'duration'  => $config['duration'],
                'path'      => $config['path'],
                'prefix'    => $this->prefix . static::$separator,
                'url'       => $config['url'] ?? null,
                'serialize' => $config['serialize'] ?? true,
            ]);
        }
    }

    /**
     * Get path for the current cache
     *
     * @return string
     */
    public function getPath(): string
    {
        return (string)$this->originalConfig['path'];
    }

    /**
     * Get name for the current cache
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Get prefix for the current cache
     *
     * @return string|null
     */
    public function getPrefix(): ?string
    {
        return $this->prefix;
    }

    /**
     * Get suffix for the current cache
     *
     * @return string|null
     */
    public function getSuffix(): ?string
    {
        return $this->suffix;
    }

    /**
     * Write a value
     *
     * @param string $key
     * @param mixed $value
     *
     * @return $this
     * @throws Exception
     */
    public function write(string $key, mixed $value): static
    {
        if (!$this->writable) {
            throw new Exception('Cache is not writable because : ' . esc_html($this->writableError ?? '?'));
        }

        [$key, $subKey] = $this->expandKey($key);
        if (!strlen($key)) {
            return $this;
        }

        if ($subKey) {
            $actualValue = @CakeCache::read($this->normalizeKey($key), $this->configKey);
            if (!is_array($actualValue)) {
                $actualValue = [];
            }
            $actualValue = Hash::insert($actualValue, $subKey, $value);
            CakeCache::write($this->normalizeKey($key), $actualValue, $this->configKey);
            return $this;
        }

        CakeCache::write($this->normalizeKey($key), $value, $this->configKey);

        return $this;
    }

    /**
     * Read a value
     *
     * @param string $key
     * @param mixed $defaultValue
     *
     * @return mixed
     * @throws Exception
     */
    public function read(string $key, mixed $defaultValue = null): mixed
    {
        if (!$this->writable) {
            throw new Exception('Cache is not writable because : ' . esc_html($this->writableError ?? '?'));
        }

        [$key, $subKey] = $this->expandKey($key);
        if (!$key) {
            return $defaultValue;
        }

        $actualValue = @CakeCache::read($this->normalizeKey($key), $this->configKey);
        if (!$subKey || !is_array($actualValue)) {
            return $actualValue;
        } else {
            return Hash::get($actualValue, $subKey, $defaultValue);
        }
    }

    /**
     * Expand a key
     *
     * @param string $key
     *
     * @return array{0: string, 1: ?string}
     */
    protected function expandKey(string $key): array
    {
        $subKey = null;
        if (str_contains($key, '.')) {
            $keyParts = explode('.', $key, 2);
            $subKey = $keyParts[1] ?? null;
            $key = $keyParts[0];
        }

        return [$key, $subKey];
    }

    /**
     * Clear cache files for the current config
     */
    public function clear(): void
    {
        @CakeCache::clear($this->configKey);
    }

    /**
     * Get file info for current cache
     *
     * @param array<string, mixed>|null $options
     *
     * @return array<string, mixed>
     */
    public function getStats(?array $options = ['listFiles' => false]): array
    {

        return Filesystem::getFileStats($this->getPath(), $this->prefix . static::$separator . '*' .
            ($this->suffix ? static::$separator  . $this->suffix : ''));
    }

    /**
     * Clear ALL cache files for ALL configs
     */
    public static function clearAll(): void
    {
        foreach (static::$configs as $configKey => $config) {
            if ($cache = static::get($configKey)) {
                @CakeCache::clear($cache->getConfigKey());
            }
            /*
            if (!$files = glob($config['path'] . '/*')) {
                continue;
            }

            // Deleting all the files in the list
            foreach ($files as $file) {
                if (is_file($file)) {
                    try {
                        @unlink($file);
                    } catch (Exception $ex) {
                    }
                }
            }*/
        }
    }

    /**
     * Clear a folder
     *
     * @param string $path
     * @param string $pattern
     */
    protected static function clearFolder(string $path, string $pattern = '*'): void
    {
        $files = glob($path . (str_ends_with($path, DIRECTORY_SEPARATOR) ? '' : DIRECTORY_SEPARATOR) . $pattern);
        if (!$files) {
            return;
        }

        // Deleting all the files in the list
        foreach ($files as $file) {
            if (is_file($file)) {
                wp_delete_file($file);
            }
        }
    }


    /**
     * Delete a cache key
     *
     * @param string $key
     */
    public function delete(string $key): void
    {
        CakeCache::delete($this->normalizeKey($key), $this->configKey);
    }

    /**
     * Normalize cache key
     *
     * @param string $key
     *
     * @return string
     */
    protected function normalizeKey(string $key): string
    {
        return $key . static::$separator . $this->suffix;
    }

    /**
     * Clear custom cache for $sessionId. Should be called on user disconnection, or by a cron
     *
     * @param string $sessionId
     */
    public static function clearSession(string $sessionId): void
    {
        foreach (static::$configs as $config) {
            static::clearFolder((string)$config['path'], '*' . static::$separator . 'sess_' . $sessionId);
        }
    }

    /**
     * Clear custom cache for the session caches
     *
     * ### Example
     *  ```
     *      Cache::clearSessions();
     * ```
     */
    public static function clearSessions(): void
    {
        foreach (static::$configs as $key => $config) {
            static::clearFolder((string)$config['path'], '*' . static::$separator . 'sess_*');
        }
    }

    /**
     * Clear custom cache for the $userId user
     *
     * ### Example
     * ```
     *      Cache::clearUser($userId);
     * ```
     *
     * @param string|int $userId
     */
    public static function clearUser(string|int $userId): void
    {
        foreach (static::$configs as $config) {
            static::clearFolder((string)$config['path'], '*' . static::$separator . 'user_' . $userId);
        }
    }

    /**
     * Clear custom cache for the user caches
     *
     * ### Example
     * ```
     *      Cache::clearUsers();
     * ```
     */
    public static function clearUsers(): void
    {
        foreach (static::$configs as $config) {
            static::clearFolder((string)$config['path'], '*' . static::$separator . 'user_*');
        }
    }

    /**
     * Clear cache files for obsolete items
     *
     * @return array<string, mixed>
     *
     *      return format :
     *      [
     *          'deletedCount' => n,
     *          'failedCount' => n,
     *          'failed' => [list of files],
     *          'summary' => 'summary text that can be logged'
     *      ]
     */
    public static function garbageCollect(): array
    {
        // read folder paths
        $result = ['deletedCount' => 0, 'failedCount' => 0, 'failed' => [], 'summary' => []];

        foreach (static::$configs as $config) {
            /** @var string $duration */
            $duration = $config['duration'];
            if (str_starts_with($duration, '+') || str_starts_with($duration, '-')) {
                $duration = substr($duration, 1);
            }

            $duration = '-' . $duration;
            $minTime = strtotime($duration);
            $pattern = ['user' => '*user_*', 'app' => '*app', 'session' => '*session_*'][$config['scope']];

            /** @var string $path */
            $path = $config['path'];
            if (
                !$files = glob(
                    $path .
                    ((str_ends_with($path, '/') || str_ends_with($path, '\\')) ? '' : DIRECTORY_SEPARATOR) .
                    $pattern
                )
            ) {
                continue;
            }

            foreach ($files as $file) {
                try {
                    /* phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen */
                    if ($handle = fopen($file, 'r')) {
                        $line = fgets($handle);
                        /* phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose */
                        fclose($handle);
                        if ($line) {
                            $timestamp = (int)trim($line);
                            if ($timestamp < $minTime) {
                                wp_delete_file($file);
                                $result['deletedCount'] += 1;
                            }
                        }
                    }
                } catch (Exception $ex) {
                    $result['failedCount'] += 1;
                }
            }
        }

        $result['summary'] = [ $result['deletedCount'] . ' deleted' ];
        if ($result['failedCount']) {
            $result['summary'][] = $result['failedCount'] . ' failed';
        }

        return $result;
    }

    /**
     * Provides the ability to easily do read-through caching.
     *
     * ### Example
     *
     * Using a Closure to provide data, assume `$this` is a Table object :
     *
     * ```
     * $results = Cache::remember('all_articles', function () {
     *      return $this->find('all');
     * });
     * ```
     *
     * @param string $key The cache key to read/store data at.
     * @param \Closure $callback
     *
     * @return mixed If the key is found: the cached data, false if the data
     *   missing/expired, or an error. If the key is not found: boolean of the
     *   success of the write
     *
     * @throws Exception
     */
    public function remember(string $key, \Closure $callback): mixed
    {
        if (str_contains($key, '.')) {
            throw new Exception('No point allowed in key, sorry');
        }
        //        if (is_array($callback) || is_string($callback)) {
        //            $callback = \Closure::fromCallable($callback);
        //        }

        return CakeCache::remember($this->normalizeKey($key), $callback, $this->configKey);
    }

    /**
     * Sanitize a string for including it into a file name
     *
     * @param string $string
     * @param string $replacement
     *
     * @return string
     */
    public static function sanitizeFilePart(string $string, string $replacement = '-'): string
    {
        if (!$sanitized = preg_replace('/[^A-Za-z0-9]/', $replacement, Inflector::underscore($string))) {
            return '';
        }
        return str_replace('__', '_', strtolower($sanitized));
    }
}
