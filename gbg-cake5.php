<?php

/**
 * Gloubi Boulga WP CakePHP(tm) 5 adapter
 *
 * @wordpress-plugin
 *
 * Plugin Name:         Gloubi Boulga WP CakePHP 5 Adapter
 * Plugin URI:          https://github.com/gloubi-boulga-team
 * Description:         Embed CakePHP(tm) libraries (ORM, Utilility, Log, Cache...) into your WP extension
 * Version:             5.0.0
 * Author:              Gloubi Boulga Team
 * Author URI:          https://github.com/gloubi-boulga-team
 * License:             MIT
 * License URI:         https://opensource.org/licenses/mit-license.php
 * Text Domain:         gbg-cake5
 * Tested up to:        6.5
 * Requires at least:   6.2
 * Requires PHP:        8.1
 * Domain Path:         /resources/locales
 *
 * @package             Gbg\Cake5
 * @since               5.0
 *
 *  This plugin is NOT part of CakePHP(tm) development framework
 *  It is an independent plugin allowing WP developers to use CakePHP development framework functionalities
*/

declare(strict_types=1);

// phpcs:disable PSR1.Files.SideEffects -- because we need to know if PHP version is compatible

defined('ABSPATH') || die;

if (!function_exists('gbgAdminNotice')) {

    /**
     * Add notice on the admin side of the Force
     *
     * @param string $level One of `info`, `success`, `warning`, `error`
     * @param string|string[] $message Message to display
     */
    function gbgAdminNotice(string $level, string|array $message): void
    {
        if (is_array($message)) {
            $message = implode('<br>', $message);
        }
        add_action('admin_notices', function () use ($level, $message) {
            echo '<div class="notice notice-' . esc_html($level) . '">';
            echo '<p>' . esc_html($message) . '</p>';
            echo '</div>';
        });
    }
}

// check blocking requirements -----------------------------------------------------------------------------------------

$phpMin = '8.1';
if (version_compare(PHP_VERSION, $phpMin, '<')) {
    /* translators: 1: plugin name, 2: actual PHP version, 3: expected PHP version */
    $message = sprintf(__('Plugin « %1$s » can not run because obsolete PHP version %2$s is not supported (should be >=%3$s). Upgrade it as soon as possible !', 'gbg-cake5'), 'Gloubi Boulga WP CakePHP 5 adapter', PHP_VERSION, $phpMin); // phpcs:ignore Generic.Files.LineLength
    do_action('Gbg/Cake5.failed', 'php-version', $message);
    gbgAdminNotice('error', $message);
    if (!is_admin() && !is_login()) {
        trigger_error(esc_html($message), E_ERROR);
    }
    return;
}

$extensions = ['pdo_mysql', 'mbstring'];
foreach ($extensions as $extension) {
    if (!extension_loaded($extension)) {
        /* translators: 1: plugin name, 2: Extension name */
        $message = sprintf(__('Plugin « %1$s » can not run because the required PHP extension « %2$s » is not active !', 'gbg-cake5'), 'Gloubi Boulga WP CakePHP 5 adapter', $extension); // phpcs:ignore Generic.Files.LineLength
        do_action('Gbg/Cake5.failed', 'ext-missing', $message);
        gbgAdminNotice('error', $message);
        if (!is_admin() && !is_login()) {
            trigger_error(esc_html($message), E_ERROR);
        }
        return;
    }
}

// include dependencies and declare constants --------------------------------------------------------------------------

require_once 'gbg-cake5-functions.php';

// empty composer memory to let other versions load their files (sharing same ids)
$GLOBALS['__composer_autoload_files'] = [];
require_once 'vendor/autoload.php';             // vendors that you should have acquired from a `composer install`

use Cake5\Core\Configure;
use Cake5\Core\Configure\Engine\PhpConfig;
use Gbg\Cake5\Wrapper\Filesystem;

// load wrappers
require_once 'src/Wrapper/Log.php';
require_once 'src/Wrapper/Text.php';
require_once 'src/Wrapper/Cache.php';
require_once 'src/Wrapper/Filesystem.php';

// load Log, Cache and ORM configuration
require_once 'gbg-cake5-load-log.php';
require_once 'gbg-cake5-load-cache.php';
require_once 'gbg-cake5-load-orm.php';


$contentDir = Filesystem::normalize(WP_CONTENT_DIR);
define('GBG_CAKE5_PATH', Filesystem::normalize(plugin_dir_path(__FILE__)));
define('GBG_CAKE5_TEMP_PATH', Filesystem::concat($contentDir, '.gbg', 'tmp') . DS);
define('GBG_CAKE5_LOG_PATH', Filesystem::concat($contentDir, '.gbg', 'log') . DS);
define('GBG_CAKE5_CACHE_PATH', Filesystem::concat($contentDir, '.gbg', 'cache') . DS);
define('GBG_CAKE5_TEXT_DEFAULT', 'gbg-cake5-qsyg"é"964(rf+qsd.:;qfloi.bd*fs65d4+*s8d+fs4s-tèysgfbfz:euà"ùµq^sdg$diçn');

foreach ([GBG_CAKE5_TEMP_PATH, GBG_CAKE5_LOG_PATH, GBG_CAKE5_CACHE_PATH] as $folder) {
    Gbg\Cake5\Wrapper\Filesystem::ensureDir($folder);
    Gbg\Cake5\Wrapper\Filesystem::htDeny($folder, '999 years', [
        'addComment' => 'But you can remove this folder when you want, no problemo !'
    ]);
}

// load json settings --------------------------------------------------------------------------------------------------

Configure::config('gbg-cake5', new PhpConfig(__DIR__ . DS . 'config' . DS));
Configure::load('app', 'gbg-cake5');

do_action('Gbg/Cake5.loaded');
