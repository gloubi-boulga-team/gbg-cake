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

use Cake5\Datasource\QueryInterface;
use Cake5\Log\Engine\FileLog;
use Cake5\ORM\Query;
use Cake5\Utility\Hash;
use Exception;
use Gbg\Cake5\Http\Request;
use Gbg\Cake5\Orm\QueryTools;
use Throwable;

/**
 * CakePHP 5 Log wrapper
 */
class Log
{
    /**
     * Current log level - allows filtering low levels to avoid writing too much logs
     *
     * @var string
     */
    protected static string $currentLogLevel = 'notice';

    /**
     * Current log level index (calculated from $currentLogLevel)
     *
     * @var null|int|string|false
     */
    protected static null|int|string|false $currentLogLevelKey = null;

    /**
     * Ordered log levels
     *
     * @var string[]
     */
    public static array $orderedLevels = [
        'debug', 'notice', 'info', 'warning', 'error', 'critical', 'alert', 'emergency', 'none'
    ];

    /**
     * Default log dir path for on-the-fly created configs
     *
     * @var string|null
     */
    public static ?string $defaultLogPath = null;

    /**
     * Write log message(s)
     *
     * ### Example (considering Engine is Cake\Cache\Engine\FileEngine)
     * ```
     *      Log::write('debug', 'My log message', 'custom-file');
     *          -> the message will be logged as `debug` in $defaultLogPath/custom-file.log
     *
     *      Log::write('warning', 'My log message');
     *          -> the message will be logged (at least) GBG_CAKE5_LOG_PATH/warning.log
     *          -> according to the Log configuration, it can be written in other (lower level files,
     *          such as info.log, debug.log...)
     * ```
     *
     * See {@link \Cake5\Log\Log::write()} for more information
     *
     * @param string $level One of [ 'debug', 'notice', 'info', 'warning', 'error', 'critical' ],
     * @param mixed $messages What you want to log
     * @param array<string|int, string|int|bool> $context Additional data to be used for logging the message.
     *  Mostly used to pass the scopes for filtering the engines to be used.
     * @param int $stackLevel @internal
     *
     * @return bool
     * @throws Exception
     */
    public static function write(string $level, mixed $messages, array $context = [], int $stackLevel = 0): bool
    {
        // should this level be logged ?
        if (empty($context['force']) && static::$currentLogLevel && static::$currentLogLevel !== 'all') {
            if (static::$currentLogLevelKey === null) {
                static::$currentLogLevelKey = array_search(static::$currentLogLevel, static::$orderedLevels);
            }
            $pos = array_search($level, static::$orderedLevels);
            if ($pos < static::$currentLogLevelKey) {
                return false;
            }
        }
        $rootMessage = !empty($context['rootMessage']);
        unset($context['force'], $context['rootMessage']);

        // get the caller info
        $bt = debug_backtrace();
        $bt = Hash::get($bt, $stackLevel . '.file', '') . ':' .
            Hash::get($bt, $stackLevel . '.line', '');
        // build the message
        $logString = '';

        $complex = false;
        foreach (is_array($messages) ? $messages : [$messages] as $message) {
            if ($message instanceof \Cake5\Database\Query || $message instanceof Throwable) {
                $complex = true;
                break;
            }
        }

        if ($rootMessage && !$complex) {
            $logString .= print_r($messages, true) . "\n";
        } else {
            foreach (is_array($messages) ? $messages : [$messages] as $message) {
                if ($message instanceof \Cake5\Database\Query) {
                    $logString .= QueryTools::getQueryCompiledSql($message) . "\n";
                } elseif ($message instanceof Throwable) {
                    $logString .= $message->getMessage() . ' (' . $message->getFile() . ':' . $message->getLine() . ')';
                    $logString .= "\n" . $message->getTraceAsString();
                } else {
                    $logString .= print_r($message, true) . "\n";
                }
            }
        }

        // check if $context exists, otherwise create it
        if ($context) {
            if ($diff = array_diff($context, \Cake5\Log\Log::configured())) {
                // create a context with default options
                foreach ($diff as $contextName) {
                    \Cake5\Log\Log::setConfig((string)$contextName, [
                            'className' => FileLog::class,
                            'path'      => static::$defaultLogPath ?? GBG_CAKE5_LOG_PATH,
                            'file'      => $contextName,
                            'url'       => null,
                            'scopes'    => [$contextName],
                            //'levels'    => ['trace', 'debug', 'notice', 'info', 'warning', 'error'],
                        ]);
                }
            }
        } else {
            $context = ['scope' => 'default'];
        }

        if ($currentUser = function_exists('wp_get_current_user') ? wp_get_current_user() : null) {
            $currentUser = $currentUser->ID . ' - ' . $currentUser->user_email;
        }

        $logString = $bt . "\t" . ($_SERVER['REQUEST_URI'] ?? '?') .
            "\t" . (Request::instance()->getClientIp() ?: '?')
            . "\nUser : " . $currentUser
            . "\nReferer : " . ($_SERVER['HTTP_REFERER'] ?? '?')
            . "\n" . $logString;
        unset($bt);

        return \Cake5\Log\Log::write($level, $logString, $context);
    }

    /**
     * Convenience method to log notice messages
     *
     * See {@link \Gbg\Cake5\Wrapper\Log::write()} for more information
     *
     * @param mixed $messages What you want to log
     * @param array<string|int, string|int|bool> $context Additional data to be used for logging the message.
     *  Mostly used to pass the scopes for filtering the engines to be used.
     *
     * @return bool
     * @throws Exception
     */
    public static function notice(mixed $messages, array $context = []): bool
    {
        return static::write(__FUNCTION__, $messages, $context + ['rootMessage' => true], 1);
    }

    /**
     * Convenience method to log debug messages
     *
     * See {@link \Gbg\Cake5\Wrapper\Log::write()} for more information
     *
     * @param mixed $messages What you want to log
     * @param array<string|int, string|int|bool> $context Additional data to be used for logging the message.
     *  Mostly used to pass the scopes for filtering the engines to be used.
     *
     * @return bool
     * @throws Exception
     */
    public static function debug(mixed $messages, array $context = []): bool
    {
        return static::write(__FUNCTION__, $messages, $context + ['rootMessage' => true], 1);
    }

    /**
     * Convenience method to log info messages
     *
     * See {@link \Gbg\Cake5\Wrapper\Log::write()} for more information
     *
     * @param mixed $messages What you want to log
     * @param array<string|int, string|int|bool> $context Additional data to be used for logging the message.
     *  Mostly used to pass the scopes for filtering the engines to be used.
     *
     * @return bool
     * @throws Exception
     */
    public static function info(mixed $messages, array $context = []): bool
    {
        return static::write(__FUNCTION__, $messages, $context + ['rootMessage' => true], 1);
    }

    /**
     * Convenience method to log warning messages
     *
     * See {@link \Gbg\Cake5\Wrapper\Log::write()} for more information
     *
     * @param mixed $messages What you want to log
     * @param array<string|int, string|int|bool> $context Additional data to be used for logging the message.
     *  Mostly used to pass the scopes for filtering the engines to be used.
     *
     * @return bool
     * @throws Exception
     */
    public static function warning(mixed $messages, array $context = []): bool
    {
        return static::write(__FUNCTION__, $messages, $context + ['rootMessage' => true], 1);
    }

    /**
     * Convenience method to log error messages
     *
     * See {@link \Gbg\Cake5\Wrapper\Log::write()} for more information
     *
     * @param mixed $messages What you want to log
     * @param array<string|int, string|int|bool> $context Additional data to be used for logging the message.
     *  Mostly used to pass the scopes for filtering the engines to be used.
     *
     * @return bool
     * @throws Exception
     */
    public static function error(mixed $messages, array $context = []): bool
    {
        return static::write(__FUNCTION__, $messages, $context + ['rootMessage' => true], 1);
    }

    /**
     * Convenience method to log notice messages
     *
     * Difference with {@link \Gbg\Cake5\Wrapper\Log::notice()} is that it is considered that you will use
     * default scopes. So that you can pass a variable number of messages as arguments
     *
     * @param mixed ...$messages What you want to log
     *
     * @return bool
     * @throws Exception
     */
    public static function noticeDefault(mixed ...$messages): bool
    {
        return static::write('notice', $messages, [], 1);
    }

    /**
     * Convenience method to log debug messages
     *
     * Difference with {@link \Gbg\Cake5\Wrapper\Log::debug()} is that it is considered that you will use
     * default scopes. So that you can pass a variable number of messages as arguments
     *
     * @param mixed ...$messages What you want to log
     *
     * @return bool
     * @throws Exception
     */
    public static function debugDefault(mixed ...$messages): bool
    {
        return static::write('debug', $messages, [], 1);
    }

    /**
     * Convenience method to log info messages
     *
     * Difference with {@link \Gbg\Cake5\Wrapper\Log::info()} is that it is considered that you will use
     * default scopes. So that you can pass a variable number of messages as arguments
     *
     * @param mixed ...$messages What you want to log
     *
     * @return bool
     * @throws Exception
     */
    public static function infoDefault(mixed ...$messages): bool
    {
        return static::write('info', $messages, [], 1);
    }

    /**
     * Convenience method to log warning messages
     *
     * Difference with {@link \Gbg\Cake5\Wrapper\Log::warning()} is that it is considered that you will use
     * default scopes. So that you can pass a variable number of messages as arguments
     *
     * @param mixed ...$messages What you want to log
     *
     * @return bool
     * @throws Exception
     */
    public static function warningDefault(mixed ...$messages): bool
    {
        return static::write('warning', $messages, [], 1);
    }

    /**
     * Convenience method to log error messages
     *
     * Difference with {@link \Gbg\Cake5\Wrapper\Log::error()} is that it is considered that you will use
     * default scopes. So that you can pass a variable number of messages as arguments
     *
     * @param mixed ...$messages What you want to log
     *
     * @return bool
     * @throws Exception
     */
    public static function errorDefault(mixed ...$messages): bool
    {
        return static::write('error', $messages, [], 1);
    }

    /**
     * See {@link \Cake5\Log\Log::setConfig()}
     *
     * @param array<string, mixed> $configs
     */
    public static function setConfig(array $configs): void
    {
        foreach ($configs as $config) {
            /** @var array{path: ?string} $config */
            $path = $config['path'] ?? (static::$defaultLogPath ?? GBG_CAKE5_LOG_PATH);
            Filesystem::ensureDir($path);
            Filesystem::htdeny($path);
        }
        \Cake5\Log\Log::setConfig($configs);
    }

    /**
     * @return array<string, mixed>
     */
    public static function getConfig(): array
    {
        $result = [];
        $configured = \Cake5\Log\Log::configured();
        foreach ($configured as $configuredItem) {
            $result[$configuredItem] = \Cake5\Log\Log::getConfig($configuredItem);
        }
        return $result;
    }

    /**
     * @param string $configKey
     *
     * @return int
     */
    public static function clear(string $configKey): int
    {
        $config = (array)\Cake5\Log\Log::getConfig($configKey);
        /** @var array<string, string> $config */
        if (!$files = Filesystem::listFiles($config['path'], $config['file'] . '.log*')) {
            return 0;
        }

        $deleted = 0;
        foreach ($files as $file) {
            wp_delete_file($file);
            $deleted++;
        }

        return $deleted;
    }

    /**
     * @param string $configKey
     * @param array<string, mixed>|null $options
     *
     * @return null|array<string, mixed>
     */
    public static function getFileStats(string $configKey, ?array $options = ['listFiles' => false]): ?array
    {
        if (!$config = \Cake5\Log\Log::getConfig($configKey)) {
            return null;
        }

        /*+ @phpstan-ignore-next-line */
        return Filesystem::getFileStats($config['path'], $config['file'] . '.log*');
    }


    /**
     * Get default log dir path
     *
     * @return string|null
     */
    public static function getDefaultLogPath(): ?string
    {
        return static::$defaultLogPath;
    }

    /**
     * Set default log dir path
     *
     * @param string $path
     */
    public static function setDefaultLogPath(string $path): void
    {
        if (!str_ends_with($path, '/') && !str_ends_with($path, '\\')) {
            $path = $path . DIRECTORY_SEPARATOR;
        }
        static::$defaultLogPath = $path;
    }

    public static function setMinLoglevel(string $level): void
    {
        static::$currentLogLevel = $level;
        static::$currentLogLevelKey = null;
    }

    public static function getMinLoglevel(): string
    {
        return static::$currentLogLevel;
    }
}
