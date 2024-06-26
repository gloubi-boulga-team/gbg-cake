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
    public static bool $debug = false;

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
     * Return a normalized path
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

        $paths = array_values(array_filter($paths, function ($path) {
            return is_string($path) && strlen($path) > 0;
        }));

        return static::normalize(implode(DIRECTORY_SEPARATOR, $paths));
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
     * @param string $separator
     *
     * @return string
     */
    public static function normalize(string $path, string $separator = DIRECTORY_SEPARATOR): string
    {
        return static::sanitizeDoubleSeparators(
            static::normalizeSeparators($path, $separator),
            $separator
        );
    }

    public static function normalizeSeparators(string $path, string $separator = DIRECTORY_SEPARATOR): string
    {
        return str_replace(['/', '\\'], $separator, $path);
    }

    /**
     * Return value is normalized
     *
     * @param string $string Must be normalized
     * @param string $separator
     *
     * @return string
     */
    public static function sanitizeDoubleSeparators(string $string, string $separator = DIRECTORY_SEPARATOR): string
    {
        // parse eventual starting protocol containing double separators (can also be a Windows network path start)
        $separatorPattern = preg_quote($separator, '/');
        // parse http:// or file:// or \\server\share\ ...
        $pattern = "/^((?:[a-zA-Z0-9]+[:])?[:]?[$separatorPattern]{2})(.*)/";
        $protocol = '';

        preg_match_all($pattern, $string, $parts);
        if (!empty($parts[1][0])) {
            $protocol = $parts[1][0];
            $string = $parts[2][0];
        }

        $lastString = $string;
        $i = 0;
        while ($i++ < 256 && (($string = str_replace($separator . $separator, $separator, $string)) !== $lastString)) {
            $lastString = $string;
        }

        $i = 0;
        if ($protocol) {
            while (str_starts_with($string, $separator) && $i++ < 256) {
                $string = substr($string, strlen($separator));
            }
        }

        return  $protocol . $string;
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
     * Ensure a path is relative to another one (if it is not, then make it relative).
     * Return value is normalized using $separator.
     *
     * @param string $path
     * @param string $basePath
     * @param string $separator
     *
     * @return string
     */
    public static function ensureRelative(string $path, string $basePath = ABSPATH, string $separator = DS): string
    {
        foreach (['path', 'basePath'] as $var) {
            $varParts = $var . 'Parts';
            $$var = static::normalize($$var, $separator);
            $$varParts = explode($separator, $$var);
            while ($$varParts[count($$varParts) - 1] === '') {
                array_pop($$varParts);
            }
        }

        /** @phpstan-ignore-next-line -- variable declared */
        foreach ($basePathParts as $k => $base) {
            /** @phpstan-ignore-next-line -- variable declared */
            if ($pathParts[$k] !== $base) {
                return $path;
            }
        }

        return Text::removeLeading($path, $basePath);
    }

    /**
     * Ensure a path is absolute (if it is not, then make it absolute).
     * Return value is normalized using $separator.
     *
     * @param string $path
     * @param string $separator
     * @param string $basePath
     *
     * @return string
     */
    public static function ensureAbsolute(string $path, string $basePath = ABSPATH, string $separator = DS): string
    {
        $path = static::normalize($path, $separator);
        $basePath = static::normalize($basePath, $separator);

        if ($basePath === $separator) {
            return Text::ensureLeading($path, $separator);
        }

        foreach (['path', 'basePath'] as $var) {
            $varParts = $var . 'Parts';
            $$var = static::normalize($$var, $separator);
            $$varParts = explode($separator, $$var);
            while ($$varParts[count($$varParts) - 1] === '') {
                array_pop($$varParts);
            }
        }

        /** @phpstan-ignore-next-line -- variable declared */
        foreach ($basePathParts as $k => $base) {
            /** @phpstan-ignore-next-line -- variable declared */
            if ($pathParts[$k] !== $base) {
                return $separator === '/' ?
                    Filesystem::concatSlash($basePath, $path) :
                    Filesystem::concat($basePath, $path);
            }
        }

        return $path;
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

        $duration = Text::ensureLeading(Text::removeLeading($duration, ['+', '-']), '-');
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

        $result['oldestWp'] = wp_date("$df $tf", $result['oldest'] ?: null);
        $result['newestWp'] = wp_date("$df $tf", $result['newest'] ?: null);
        $result['oldestStd'] = gmdate('Y-m-d H:i:s', $result['oldest'] ?: null);
        $result['newestStd'] = gmdate('Y-m-d H:i:s', $result['newest'] ?: null);

        $result['totalSize'] = Text::parseBytesToSize($result['totalSize']);
        $result['biggest'] = Text::parseBytesToSize($result['biggest']);

        return $result;
    }
}
