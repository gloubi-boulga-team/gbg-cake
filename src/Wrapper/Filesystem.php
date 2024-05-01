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

class Filesystem
{
    /**
     * Explode path - path should only contain ascii chars
     *
     * @param string $path
     * @param int $limit
     *
     * @return string[]
     */
    public static function explodePath(string $path, int $limit = -1): array
    {
        $path = str_replace('\\', '/', $path);
        $path = Text::removeLeading($path, '/');
        return array_values(
            array_filter(
                preg_split(
                    '/[\\\\\/]/',
                    $path,
                    $limit
                ) ?: [],
                function ($item) {
                    return strval($item) !== '';
                }
            )
        );
    }

    /**
     * Concatenate path parts
     *
     * @param string|null $basePath
     * @param string|null ...$additionalPath
     *
     * @return string
     */
    public static function concat(?string $basePath, ?string ...$additionalPath): string
    {
        /** @var array<?string> $paths */
        $paths = func_get_args();
        $result = [];
        $i = 0;

        foreach ($paths as $path) {
            if ($path === null || strlen($path) === 0) {
                continue;
            }

            $path = static::normalize($path);
            if ($i !== 0 && str_starts_with($path, DIRECTORY_SEPARATOR)) {
                $path = substr($path, 1);
            }
            if ($i !== count($paths) - 1 && str_ends_with($path, DIRECTORY_SEPARATOR)) {
                $path = substr($path, 0, strlen($path) - 1);
            }

            $result[] = $path;
            $i++;
        }

        return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, implode(DIRECTORY_SEPARATOR, $result));
    }

    /**
     * Same as `concat`, but using slash as separator
     *
     * @param string|null $basePath
     * @param string|null ...$additionalPath
     *
     * @return string
     */
    public static function concatSlash(?string $basePath, ?string ...$additionalPath): string
    {
        return static::normalize(static::concat($basePath, ...$additionalPath), '/');
    }

    /**
     * Replace backslash or forward slash with the accurate separator
     *
     * @param string $path The path to normalize
     * @param string $delimiter
     *
     * @return string
     */
    public static function normalize(string $path, string $delimiter = DIRECTORY_SEPARATOR): string
    {
        return str_replace(['/', '\\'], $delimiter, $path);
    }

    /**
     * List files or dirs in a path using a pattern
     *
     * - efficient but maybe costly, check before using it in big processes
     * - as it use `glob`, it does not read file starting with '.' (except .htaccess)
     *
     * ### Example
     *
     *      Filesystem::list('/toto', '*.txt', null, 2)
     *
     * @param string $path
     * @param string $pattern
     * @param string|null $filter `dir`, `file` or null
     * @param int|null $depth
     * @param int|null $globFlags
     *
     * @return string[]
     */
    public static function list(
        string $path,
        string $pattern = '*',
        ?string $filter = null,
        ?int $depth = 1,
        ?int $globFlags = null
    ): array {

        $globFlagsCalculated = ($globFlags ?? 0) | ($filter === 'dir' ? GLOB_ONLYDIR : 0);
        $results = [];

        if ($depth === 0 || $depth === -1) {
            $depth = PHP_INT_MAX;
        }

        if ($depth === 1) {
            $results = glob(static::concat($path, $pattern), $globFlagsCalculated);

            if ($results && $filter === 'file') {
                $results = array_values(array_filter($results, 'is_file') ?: []);
            }
        } else {
            $newDepth = (empty($depth) ? $depth : $depth - 1);
            $items = glob(static::concat($path, '*'), $globFlagsCalculated);

            if ($items) {
                foreach ($items as $item) {
                    if ($filter !== 'file' || is_file($item)) {
                        $results[] = $item;
                    }
                    if (
                        is_dir($item) &&
                        !str_ends_with($item, DIRECTORY_SEPARATOR . '.') &&
                        !str_ends_with($item, DIRECTORY_SEPARATOR . '..')
                    ) {
                        $results = array_merge($results, static::list($item, '*', $filter, $newDepth, $globFlags));
                    }
                }

                if ($pattern !== '*') {
                    // convert glob pattern to regex pattern
                    $pattern = str_replace('*', '(.*)', $pattern);
                    $pattern = str_replace('?', '(.)', $pattern);
                    $pattern = '/^' . $pattern . '$/';
                    $results = array_filter($results, function ($item) use ($pattern) {

                        $parts = static::explodePath($item);
                        return boolval(preg_match($pattern, end($parts) ?: ''));
                    });
                }
            }
        }

        return $results ? array_values($results) : [];
    }

    /**
     * List dirs in a path using a pattern
     *
     * - efficient but maybe costly, check before using it in big processes
     * - as it use `glob`, it does not read file starting with '.' (except .htaccess)
     *
     * ### Example
     * ```
     *      Filesystem::listDirs('/toto', '*filter_dir*', 2)
     * ```
     *
     * @param string $path
     * @param string $pattern
     * @param int $depth
     * @param int|null $globFlags
     *
     * @return string[]|false
     */
    public static function listDirs(
        string $path,
        string $pattern = '*',
        int $depth = 1,
        ?int $globFlags = null
    ): bool|array {
        return static::list($path, $pattern, 'dir', $depth, $globFlags);
    }

    /**
     * List files in a path using a pattern
     *
     * - efficient but maybe costly, check before using it in big processes
     * - as it use `glob`, it does not read file starting with '.' (except .htaccess)
     *
     * ### Example
     * ```
     *      Filesystem::listDirs('/toto', '*filter_dir*', 2)
     * ```
     *
     * @param string $path
     * @param string $pattern
     * @param int $depth
     * @param int|null $globFlags
     *
     * @return string[]|false
     */
    public static function listFiles(
        string $path,
        string $pattern = '*',
        int $depth = 1,
        ?int $globFlags = null
    ): bool|array {
        return static::list($path, $pattern, 'file', $depth, $globFlags);
    }

    /**
     * Ensure a path is relative to another one
     *
     * @param string $path
     * @param string $basePath
     * @param string $delimiter
     *
     * @return string
     */
    public static function ensureRelative(string $path, string $basePath = ABSPATH, string $delimiter = DS): string
    {
        $path = static::normalize($path, $delimiter);
        $basePath = static::normalize($basePath, $delimiter);
        return static::normalize(str_replace($basePath, '', $path), $delimiter);
    }

    /**
     * Ensure a path is absolute (and if it is not, then make it absolute)
     *
     * @param string $path
     * @param string $delimiter
     * @param string $basePath
     *
     * @return string
     */
    public static function ensureAbsolute(string $path, string $basePath = ABSPATH, string $delimiter = DS): string
    {
        $path = static::normalize($path, $delimiter);
        $basePath = static::normalize($basePath, $delimiter);

        return !Text::startsWith($path, $basePath) ?
            static::concat($basePath, $path) :
            $path;
    }

    /**
     * Ensure a dir exists (if not, create it)
     *
     * @param string $path
     * @param int $permission
     *
     * @return void
     */
    public static function ensureDir(string $path, int $permission = 0755): void
    {
        if (!is_dir($path)) {
            wp_mkdir_p($path);
        }
    }

    /**
     * Remove a dir and all its files/subdirs recursively
     *
     * @param string $path
     * @param bool $recursive
     *
     * @return bool
     */
    public static function removeDir(string $path, bool $recursive = true): bool
    {
        $fileSystemDirect = new \WP_Filesystem_Direct(false);
        return $fileSystemDirect->rmdir($path, $recursive);
    }

    /**
     * Empty a dir without removing it
     *
     * @param string $path
     * @param bool $recursive
     *
     * @return bool
     */
    public static function emptyDir(string $path, bool $recursive = true): bool
    {
        if (empty($path) || !file_exists($path) || !is_dir($path)) {
            return false;
        }

        try {
            $htaccess = static::concat($path, '.htaccess');
            if (file_exists($htaccess)) {
                wp_delete_file($htaccess);
            }

            $items = static::list($path, '*', null, 1);

            for ($i = count($items) - 1; $i >= 0; $i--) {
                $item = $items[$i];
                if ($recursive && is_dir($item)) {
                    static::removeDir($item);
                } elseif (is_file($item)) {
                    wp_delete_file($item);
                }
            }
        } catch (\Exception $ex) {
            return false;
        }

        return true;
    }

    /**
     * Check if a file is older than a certain duration
     *
     * @param string $path
     * @param string $duration
     * @param string|string[] $func
     *
     * @return bool|null
     */
    public static function isFileOlderThan(string $path, string $duration, string|array $func = 'filemtime'): ?bool
    {
        if (!file_exists($path) || empty($duration)) {
            return true;
        }

        $duration = str_starts_with($duration, '+') ? substr($duration, 1) : $duration;
        $duration = str_starts_with($duration, '-') ? substr($duration, 1) : $duration;
        $duration = '-' . $duration;
        $minTime = strtotime($duration);
        foreach ((array)$func as $prop) {
            if (!is_callable($prop)) {
                continue;
            }
            $time = $prop($path);
            if ($time === false) {
                continue;
            }
            return $time < $minTime;
        }

        return null;
    }

    /**
     * Ensure .htaccess exists and is not older than strtotime($duration)
     *
     * @param string $path
     * @param string $duration
     *
     * @param array<string, string> $options
     */
    public static function htDeny(string $path, string $duration = '+999 years', array $options = [
            'comment' => 'Please do not edit this file, or you are (potentially) dead...'
        ]): void
    {
        $htAccess = static::concat($path, '.htaccess');
        if (static::isFileOlderThan($htAccess, $duration)) {
            $content =
                (!empty($options['comment']) ? '# ' . $options['comment'] . "\r\n" : '') .
                (!empty($options['addComment']) ? '# ' . $options['addComment'] . "\r\n" : '') .
                '<FilesMatch ".*">' . "\r\n" .
                'Order Allow,Deny' . "\r\n" .
                'Deny from All' . "\r\n" .
                '</FilesMatch>';

            /** @var \WP_Filesystem_Base $wp_filesystem */
            global $wp_filesystem;
            $wp_filesystem->put_contents($htAccess, $content);
        }
    }

    /**
     * Get file stats for a given folder / pattern
     *
     * @param string $folder
     * @param string $pattern
     * @param array<string, mixed> $options
     *
     * @return array<string, array<int, string>|int|string|false|null>
     */
    public static function getFileStats(string $folder, string $pattern, array $options = []): array
    {
        if (!$files = Filesystem::listFiles($folder, $pattern)) {
            return ['count' => 0, 'totalSize' => null, 'oldest' => null, 'newest' => null, 'biggest' => null];
        }

        $result = ['count' => 0, 'totalSize' => 0, 'oldest' => time(), 'newest' => 0, 'biggest' => 0];

        foreach ($files as $file) {
            if (!empty($options['listFiles'])) {
                $result['list'][] = $file;
            }
            $lastModified = filemtime($file);
            $size = filesize($file);
            $result['oldest'] = $lastModified < $result['oldest'] ? $lastModified : $result['oldest'];
            $result['newest'] = $lastModified > $result['newest'] ? $lastModified : $result['newest'];
            $result['biggest'] = $size > $result['biggest'] ? $size : $result['biggest'];
            $result['totalSize'] += $size;
            $result['count']++;
        }

        /** @var string $df */
        $df = get_option('date_format');
        /** @var string $tf */
        $tf = get_option('time_format');

        $result['oldest_wp'] = wp_date("$df $tf", $result['oldest'] ?: null);
        $result['newest_wp'] = wp_date("$df $tf", $result['newest'] ?: null);
        $result['oldest_std'] = gmdate('Y-m-d H:i:s', $result['oldest'] ?: null);
        $result['newest_std'] = gmdate('Y-m-d H:i:s', $result['newest'] ?: null);

        $result['totalSize'] = Text::parseBytesToSize($result['totalSize']);
        $result['biggest'] = Text::parseBytesToSize($result['biggest']);

        return $result;
    }
}
