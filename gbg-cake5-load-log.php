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
use Gbg\Cake5\Wrapper\Log;

add_filter('plugins_loaded', 'gbgCake5_LoadLog', 10);

if (!function_exists('gbgCake5_LoadLog')) {

    /**
     * Load Gbg\Cake5\Wrapper\Log with adequate configurations
     */
    function gbgCake5_LoadLog(): void
    {

        // set default path for on-the-fly cache creations
        $defaultPath = apply_filters('Gbg/Cake5.Log.initLoggerDefaultPath', Configure::read('logDefaultPath'));

        /** @var string $defaultPath */
        Log::setDefaultLogPath($defaultPath);

        $configurations = apply_filters('Gbg/Cake5.Log.initLogger', Configure::read('Log'));

        /** @var array<string, array<string, string>> $configurations */
        Log::setConfig($configurations);

        do_action('Gbg/Cake5.Log.loaded', $configurations);
    }
}
