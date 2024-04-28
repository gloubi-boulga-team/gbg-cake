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

defined('ABSPATH') || die;

use Cake5\Core\Configure;
use Gbg\Cake5\Wrapper\Cache;

add_filter('plugins_loaded', 'gbgCake5_LoadCache', 11);

if (!function_exists('gbgCake5_LoadCache')) {

    /**
     * Load Gbg\Cake5\Wrapper\Cache with adequate configurations
     */
    function gbgCake5_LoadCache(): void
    {
        $configurations = apply_filters('Gbg/Cake5.Cache.initCache', Configure::read('Cache'));
        /** @var array<string, array<string, bool|int|string>> $configurations */
        foreach ($configurations as $name => $config) {
            // resolve `duration`
            if (is_callable($config['duration'])) {
                $config['duration'] = $config['duration']();
            }
            Cache::addConfig($name, $config);
        }

        do_action('Gbg/Cake5.Cache.loaded', $configurations);
    }
}
