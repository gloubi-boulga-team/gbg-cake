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

use Gbg\Cake5\Wrapper\Log;

defined('WPINC') || die;

/**
 * ### Usage example for Gbg\Cake5\Wrapper\Log
 *
 * After that, check folder `wp-content/.gbg/log`
 * (this is the default path for storing log files)
 *
 * If you want to customize minimum log level for file logging,
 * or be able to clear log files from an admin page,
 * see https://github.com/gloubi-boulga-team/gbg-core
 *
 */

add_action('Gbg/Cake5.Log.loaded', function () {

    // write in different files according to the level
    Log::notice('This is a `notice` log from gbg-cake5-demo');
    Log::debug('This is a `debug` log from gbg-cake5-demo');
    Log::info('This is a `info` log from gbg-cake5-demo');

    // write in `debug` file using variable-length argument lists
    Log::debugDefault(
        'This is message #1 written using default config',
        'This is message #2 written using default config',
        'This is message #3 written using default config'
    );

    // write in a `blabla` file
    Log::info(
        'This is a message written using on-the-fly created `blabla` config',
        ['blabla']
    );

    // trigger a notice. If no other plugin changed default config, it will be written in php.log file
    trigger_error('This is a demo for PHP notice');
});
