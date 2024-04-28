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

namespace Gbg\Cake5\Orm;

use Cake5\Event\EventInterface;
use Cake5\ORM\Query\SelectQuery;

trait CallbackTrait
{
    /**
     * Will return true if callback should be ignored, false otherwise
     * @param array<string, mixed>|\ArrayObject $options
     *      'ignoreCallbacks'   :   true - will ignore all Callbacks
     *                              false - will apply all Callbacks
     *                              array of table or behavior aliases
     *                                  -> will play all Callbacks only for unspecified items
     *
     * @return boolean
     *
     * ### Example
     * ```
     *     $resultsTable->find('all', ['ignoreCallbacks' => ['ResultsTable']])->all();
     *
     *     public function EventInterface $event, SelectQuery $query, \ArrayObject $options, bool $primary) {
     *          if (!$this->_applyCallback($options)) {
     *              return;
     *          }
     *          ...
     *      }
     * }
     * ```
     */
    protected function shouldIgnoreCallback(array|\ArrayObject $options): bool
    {
        if (!isset($options['ignoreCallbacks'])) {
            return false;
        }

        if ($options['ignoreCallbacks'] === true) {
            return true;
        }

        $options['ignoreCallbacks'] = (array)$options['ignoreCallbacks'];

        /** @var array<string, string> $classDef */
        $classDef = gbgParseClassName(get_class($this));

        if (
            in_array(
                ($classDef['plugin'] ?? 'Gbg/Cake5') . '.' . $classDef['finalRaw'],
                $options['ignoreCallbacks'],
                true
            )
            || in_array(
                $classDef['finalRaw'],
                $options['ignoreCallbacks'],
                true
            )
        ) {
            return true;
        }
        if (in_array(get_class($this), $options['ignoreCallbacks'], true)) {
            return true;
        }

        return false;
    }
}
