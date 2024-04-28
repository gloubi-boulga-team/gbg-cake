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

// phpcs:disable PSR1.Files.SideEffects

declare(strict_types=1);

use Cake5\Core\Configure;
use Cake5\Database\TypeFactory;
use Cake5\Datasource\ConnectionManager;
use Cake5\ORM\Locator\LocatorInterface;
use Cake5\ORM\TableRegistry;
use Cake5\Utility\Inflector;
use Gbg\Cake5\Orm\Database\Log\QueryLogger;
use Gbg\Cake5\Orm\Database\Type\IpType;
use Gbg\Cake5\Orm\Database\Type\SerializeType;
use Gbg\Cake5\Orm\TableLocator;
use Gbg\Cake5\Wrapper\Cache;

defined('ABSPATH') || die;

// Declare Gbg custom datatypes
TypeFactory::map('serialize', SerializeType::class);
TypeFactory::map('ip', IpType::class);

// load Gbg Query Logger ORM -------------------------------------------------------------------------------------------

add_action('plugins_loaded', 'gbgCake5_LoadQueryLogger', 12);

if (!function_exists('gbgCake5_LoadQueryLogger')) {
    /**
     * Initialize Query Logger with adequate configuration
     */
    function gbgCake5_LoadQueryLogger(): void
    {
        $configuration = Configure::read('QueryLogger');

        // consider phpunit tests @phpstan-ignore-next-line
        if (defined('GBG_TESTS_RUNNING') && GBG_TESTS_RUNNING) {
            if (!defined('SAVEQUERIES')) {
                define('SAVEQUERIES', true);
            }
        }

        if ($configuration = apply_filters('Gbg/Cake5.Orm.initQueryLogger', $configuration)) {
            /** @var array<string, mixed> $configuration */
            // @phpstan-ignore-next-line
            if (defined('GBG_TESTS_RUNNING') && GBG_TESTS_RUNNING) {
                $configuration = [
                    'status' => 'conditional',
                    'includeWp' => true,
                    'conditions' => [
                        'duration' => ['gte' => -1]
                    ]
                ];
            }
            QueryLogger::initialize($configuration);
        }
        do_action('shutdown', function () {
            QueryLogger::finalize();
        });

        do_action('Gbg/Cake5.QueryLogger.loaded', $configuration);
    }
}

// load Gbg ORM --------------------------------------------------------------------------------------------------------

add_action('plugins_loaded', 'gbgCake5_LoadOrm', 13);

if (!function_exists('gbgCake5_LoadOrm')) {
    /**
     * Initialize CakePHP ORM with adequate configuration
     */
    function gbgCake5_LoadOrm(): void
    {
        // Initialize ORM table locator
        $tableLocator = apply_filters('Gbg/Cake5.Orm.initTableLocator', new TableLocator());
        /** @var LocatorInterface $tableLocator */
        TableRegistry::setTableLocator($tableLocator);

        // do not singularize `meta` to `metum`
        Inflector::rules('singular', ['/meta$/i' => '\1meta']);

        // Initialize ORM engine
        /** @var array<string, array<string, mixed>> $configurations */
        $configurations = apply_filters('Gbg/Cake5.Orm.initConnectionManager', Configure::read('Datasources'));

        foreach ($configurations as $key => $configuration) {
            // convert cacheMetadata to cakePHP cache key
            if (isset($configuration['cacheMetadata'])) {
                if ($configuration['cacheMetadata'] === true) {
                    $configuration['cacheMetadata'] = 'Gbg/Cake5.cakeDefaultDbSchema';
                }
                if (
                    is_string($configuration['cacheMetadata']) &&
                    ($cache = Cache::get($configuration['cacheMetadata']))
                ) {
                    $configuration['cacheMetadata'] = $cache->getConfigKey();
                }
            }
            ConnectionManager::setConfig($key, $configuration);
        }

        do_action('Gbg/Cake5.Orm.loaded', $configurations);
    }
}
