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

// phpcs:disable PSR1.Files.SideEffects -- some important checks need to be
// done before function declarations

declare(strict_types=1);

use Gbg\Cake5\Wrapper\Text;

//defined('ABSPATH') || die;

if (!function_exists('gbgGetWpdb')) {
    /**
     * Simple time-and-energy-saver function to get global $wpdb - Duplicate from Gbg/Core plugin
     */
    function gbgGetWpdb(): \wpdb
    {
        global $wpdb;
        return $wpdb;
    }
}

if (!function_exists('gbgParseClassName')) {

    /**
     * Parse a Gbg class name - Duplicate from Gbg/Core plugin
     *
     * ### Example
     *
     * ```
     *      gbgParseClassName('\Gbg\Cake5\Model\Table\Wp\UsersTable');
     * ```
     *
     * Will generate the following result:
     *
     * ```
     *  ['plugin' => 'Gbg/Cake5', 'type' => 'Model\Table\Wp', 'final' = 'UsersTable']
     * ```
     *
     * @param string $className
     * @param string|null $attribute
     * @param array|string[] $reservedKeywords
     * @return mixed
     */
    function gbgParseClassName(
        string $className,
        ?string $attribute = null,
        array $reservedKeywords = [
            'event',
            'plugin',
            'controller',
            'utility',
            'view',
            'orm',
            'http',
            'model',
            'behavior',
            'table',
            'entity',
            'admin',
        ]
    ): mixed {

        static $classCache = [];
        $cacheKey = $className . '__' . print_r($reservedKeywords, true);
        if (isset($classCache[$cacheKey])) {
            return !$attribute ? $classCache[$cacheKey] : ($classCache[$cacheKey][$attribute] ?? null);
        }

        $result = [
            'namespace'       => [],
            'plugin'          => [],
            'pluginNamespace' => [],
            'type'            => [],
            'final'           => '',
            'finalRaw'        => ''
        ];

        $parts = explode('\\', $className);
        $result['final'] = $result['finalRaw'] = array_pop($parts);
        $result['namespace'] = implode('\\', $parts);

        $pluginClosed = false;

        for ($i = 0; $i < count($parts); $i++) {
            if (in_array(strtolower($parts[$i]), $reservedKeywords)) {
                $pluginClosed = true;
                $result['type'][] = $parts[$i];
            } elseif (!$pluginClosed) {
                $result['plugin'][] = $parts[$i];
            }
        }

        $result['type'] = implode('\\', $result['type']);
        $result['finalRaw'] = Text::removeTrailing($result['finalRaw'], $result['type']);
        $result['pluginNamespace'] = implode('\\', $result['plugin']);
        $result['plugin'] = implode('/', $result['plugin']);
        $classCache[$cacheKey] = $result;

        return !$attribute ? $result : ($result[$attribute] ?? null);
    }
}
