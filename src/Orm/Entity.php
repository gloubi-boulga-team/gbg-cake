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

use Cake5\Datasource\EntityInterface;
use Cake5\ORM\TableRegistry;
use Cake5\Utility\Inflector;

class Entity extends \Cake5\ORM\Entity
{
    /**
     * @param string $field
     * @return bool
     */
    public function hasField(string $field): bool
    {
        return in_array($field, array_keys($this->_fields), true);
    }

    /**
     * Get the primary value for this entity using primary key definition
     *
     * @return array<mixed>
     */
    public function getPrimaryValue(): array
    {
        $result = [];
        $primaryKey = TableRegistry::getTableLocator()->get($this->_registryAlias)->getSchema()->getPrimaryKey();
        foreach ($primaryKey as $primaryColumn) {
            $result[] = $this->{$primaryColumn};
        }

        return $result;
    }

    /**
     * Check if a field in a field array is dirty
     *
     * @param string[] $fields
     *
     * @return bool
     */
    public function oneIsDirty(array $fields): bool
    {
        foreach ($fields as $field) {
            if ($this->isDirty($field)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get table object for current entity
     *
     * @return \Cake5\ORM\Table Table object for current Entity
     */
    public function getTable(): \Cake5\ORM\Table
    {
        $source = $this->getSource();

        if (empty($source)) {
            list(, $class) = \Cake5\Core\namespaceSplit(get_class($this));
            $source = Inflector::pluralize($class);
        }

        return TableRegistry::getTableLocator()->get($source);
    }

    /**
     * Save this entity
     *
     * Take care, it can be expensive, but can be useful if you don't know the name of the table
     *
     * @return EntityInterface|false
     */
    public function saveMe(): EntityInterface|false
    {
        return $this->getTable()->save($this);
    }
}
