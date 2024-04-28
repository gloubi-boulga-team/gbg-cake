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

defined('WPINC') || die;

/**
 * ### Usage example for Gbg\Cake5\Wrapper\Cache
 *
 * After that, check folder `wp-content/.gbg/cache`
 * (this is the default path for storing cache files)
 *
 * If you want to customize minimum log level for file logging,
 * or be able to clear cache files from an admin page,
 * see https://github.com/gloubi-boulga-team/gbg-core
 */

use Gbg\Cake5\Wrapper\Cache;
use Gbg\Cake5\Wrapper\Filesystem;

add_action('Gbg/Cake5.Cache.loaded', function () {

    $cachePath = Filesystem::concat(WP_CONTENT_DIR, '.gbg', 'cache-demo') . DIRECTORY_SEPARATOR;

    Cache::addConfig('Gbg/Cake5Demo.data', [
        'scope'         => 'user',      // can be `app` or `user` (`session` needs gbg-cake5-core)
        'path'          => $cachePath,
        'duration'      => '1 day'
    ]);

    // load cache instance
    $cache = Cache::get('Gbg/Cake5Demo.data');

    // `remember` tries to read, but if it fails, calls the callback
    $cache?->remember(
        'rememberMe',
        function () {
            return 'It will only pass here once a day by user !';
        }
    );

    if (!$cache?->read('cacheMe')) {
        // It will only pass here once a day by user
        $cache?->write('cacheMe', 'If you can...');
    }

    // $cache->clear();                 // will remove all files for this config
    // $cache->delete('writeThis');     // will remove file for this config for this key

    Cache::garbageCollect();            // will remove obsolete files of all declared configs
    // Cache::clearAll();               // will remove all files of all declared configs
    // Cache::clearSessions();          // will remove all files of all declared configs with scope `session`
    // Cache::clearUsers();             // will remove all files of all declared configs with scope `user`
});
