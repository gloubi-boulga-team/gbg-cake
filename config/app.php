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
 *
 * App default configuration - loaded using \Cake5\Core\Configure::load()
 * !! If you want to change something, do not modify this file !!
 *
 * ### Example
 * ```
 *  add_action('plugins_loaded', function() {
 *
 *      Configure::write('Cache.Your/Plugin:configName', [
 *          'path'          => $cachePath,
 *          'duration'      => '1 day',
 *          'scope'         => 'app'
 *      ]);
 *
 *      $cache = Configure::read('Cache.Your/Plugin:configName');
 *      $cache['scope'] = 'user';
 *      $cache = Configure::write('Cache.Your/Plugin:configName', $cache);
 * };
 * ```
 */

declare(strict_types=1);

use Cake5\Log\Engine\FileLog;
use Gbg\Cake5\Orm\Database\Connection;
use Gbg\Cake5\Orm\Database\Driver\Wordpress;

defined('ABSPATH') || die;

$logDefaults = [ 'className' => FileLog::class, 'path' => GBG_CAKE5_LOG_PATH, 'scopes' => [ 'default' ] ];
$dbHostParts = explode(':', defined('DB_HOST') ? DB_HOST : 'localhost');
[$dbHost, $dbPort] = (count($dbHostParts) === 1 ? [$dbHostParts[0], 3306] : $dbHostParts);

global $wpdb;

return [

    'Cache' => [
        'Gbg/Cake5.dbSchemas'    => [
            'name'              => 'Db schemas',
            'path'              => GBG_CAKE5_CACHE_PATH,
            'duration'          => function () {
                if (function_exists('current_user_can')) {
                    return current_user_can('manage_options') ? '5 seconds' : '1 year';
                }
                return '1 year';
            },
            'scope'             => 'app'
        ],
        'Gbg/Cake5.dbVariables'  => [
            'name'     => 'Db variables',
            'path'     => GBG_CAKE5_CACHE_PATH,
            'duration' => '1 year',
            'scope'    => 'app'
        ],
        'Gbg/Cake5.translations' => [
            'name'     => 'Translations',
            'path'     => GBG_CAKE5_CACHE_PATH,
            'duration' => '1 year',
            'scope'    => 'app'
        ],
//        'Gbg/Cake5.cakeDefaultDbSchema' => [
//            'name'      => 'Cake default DB Cache',
//            'prefix'    => 'cake_model_',
//            'path'      => GBG_CAKE5_CACHE_PATH . 'models' . DIRECTORY_SEPARATOR,
//            'serialize' => true,
//            'duration'          => function () {
//                if (function_exists('current_user_can')) {
//                    return current_user_can('manage_options') ? '5 seconds' : '1 year';
//                }
//                return '1 year';
//            },
//            'scope'     => 'app',
//        ]
    ],

    'logDefaultPath' => GBG_CAKE5_LOG_PATH,

    'Log' => [
        'notice' => [
                'file'      => 'notice',
                'levels'    => ['notice'],
            ] + $logDefaults,
        'debug' => [
                'file'      => 'debug',
                'levels'    => ['debug'],
            ] + $logDefaults,
        'info' => [
                'file'      => 'info',
                'levels'    => ['info'],
            ] + $logDefaults,
        'warning' => [
                'file'      => 'warning',
                'levels'    => ['warning' ],
            ] + $logDefaults,
        'error' => [
                'file'      => 'error',
                'levels'    => ['error', 'emergency', 'alert', 'critical' ],
            ] + $logDefaults,
        'php' => [
                'file'      => 'php',
                'scopes'    => ['php'],
                'levels'    => ['notice', 'debug', 'info', 'warning', 'error', 'emergency', 'alert', 'critical' ],
            ] + $logDefaults,
    ],

    'Datasources' => [
        'default' => [
            'className'        => Connection::class,
            'driver'           => Wordpress::class ,
            'persistent'       => false,
            'host'             => $dbHost,
            'port'             => $dbPort,
            'username'         => defined('DB_USER') ? DB_USER : null,
            'password'         => defined('DB_PASSWORD') ? DB_PASSWORD : null,
            'database'         => defined('DB_NAME') ? DB_NAME : null,
            'encoding'         => defined('DB_CHARSET') ? DB_CHARSET : null,
            'timezone'         => 'UTC',
            'cacheMetadata'    => false,
            'log'              => false,
            'quoteIdentifiers' => false,
            'tablePrefix'      => $wpdb->prefix
        ],
    ],

    'QueryLogger' => [

        // status can be one of [true, false, "conditional"]
        'status'        => false,
        // if schema queries should be logged
        'includeSchema' => true,
        // if queries coming from WP (and not using Gbg/Cake) should be logged
        // SAVEQUERIES constant should be set to true
        'includeWp'     => false,
        // log format
        'pattern'       => "{datetime} {level} connection={connection} duration={duration} rows={rows} url={url} ip={ip} \n---- {query}\n---- {callStack}", // phpcs:ignore Generic.Files.LineLength
        // conditions for logging
        'conditions'    => [
            // available condition keys are ["duration", "uri", "ip", "query", "row"]
            // available condition values are :
            //      - contains, notContains, startsWidth, notStartsWidth, endsWidth, notEndsWith
            //      - in, notIn, inStrict, notInStrict
            //      - equals, notEquals, equalsStrict, notEqualsStrict
            //      - gt, gte, lt, lte
            //      - regexp, notRegexp
            'duration' => ['gte' => -1],
            'uri'      => [],
            'ip'       => [],
            'query'    => [],
            'rows'     => [],
        ]
    ],

    // analyze sql queries :
    //  - detect similar queries from sql string, stacktrace, ...
    //  - detect slow queries
    //  - ...
    'QueryAnalyzer'       => false,

];
