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

use Cake5\Datasource\RepositoryInterface;
use Cake5\ORM\TableRegistry;

trait TableLocatorTrait
{
    /**
     * Get Table instance
     *
     * @param string $tableName
     * @param array<string, mixed> $options
     *
     * @return RepositoryInterface
     */
    public function getTable($tableName, $options = []): RepositoryInterface
    {
        if (!str_contains($tableName, '.') && empty($options['className'])) {
            $class = explode('\\', get_class($this));
            array_pop($class);
            array_pop($class);
            $namespace = implode('\\', $class);
            $options['plugins'] = array_values(array_unique([$namespace, 'Gbg\\Core', 'WP']));
        }

        return TableRegistry::getTableLocator()->get($tableName, $options);
    }
}
