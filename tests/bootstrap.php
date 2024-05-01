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
 *  Gbg/Cake5 tests bootstrap file, called in phpunit.xml.dist
 */

declare(strict_types=1);

include_once 'TestCase.php';

error_reporting(E_ALL);
ini_set('memory_limit', '1G');

const GBG_TESTS_RUNNING = true;

$configFile = dirname(__FILE__, 5) . DIRECTORY_SEPARATOR . 'wp-config.php';
include_once $configFile;
