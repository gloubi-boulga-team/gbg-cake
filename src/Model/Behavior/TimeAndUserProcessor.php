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

namespace Gbg\Cake5\Model\Behavior;

use Cake5\Datasource\EntityInterface;

/**
 * Used to centralize functions related to time and user fields
 */
class TimeAndUserProcessor
{
    /**
     * @var Behavior
     */
    protected Behavior $behavior;

    /**
     * Centralized construct function - Will search for *By and *At fields and normalize them
     *
     * @param Behavior $behavior
     */
    public function __construct(Behavior $behavior)
    {
        $this->behavior = $behavior;

        // discover all *By and *At fields
        if (!$this->behavior->getConfig('fields')) {
            $user = $time = [];

            /** @var array<string, string> $config */
            $config = $this->behavior->getConfig();

            foreach (array_keys($config) as $key) {
                $key = strval($key);
                if (str_ends_with($key, 'By')) {
                    $user[] = $key;
                } elseif (str_ends_with($key, 'At')) {
                    $time[] = $key;
                }
            }

            $this->behavior->setConfig(
                'fields',
                ['time' => $time, 'user' => $user, 'all' => array_merge($user, $time)]
            );
        }

        // ensure `all`
        if (!$this->behavior->getConfig('fields.all')) {
            /** @var string[] $timeFields */
            $timeFields = $this->behavior->getConfig('fields.time', []);

            /** @var string[] $userFields */
            $userFields = $this->behavior->getConfig('fields.user', []);
            $this->behavior->setConfig('fields.all', array_merge($userFields, $timeFields));
        }

        // check/find column existence
        /** @var string[] $allFields */
        $allFields = $this->behavior->getConfig('fields.all');

        foreach ($allFields as $fieldKey) {
            /** @var string $fieldKey */
            /** @var array<string, string> $definition */
            if ($definition = $this->behavior->getConfig($fieldKey, [])) {
                $this->behavior->setConfig($fieldKey . '.field', null, false);
                /** @var string[] $definitionFields */
                $definitionFields = (array)$definition['field'];

                foreach ($definitionFields as $field) {
                    // search for column existence in table
                    if ($this->behavior->table()->getSchema()->hasColumn($field)) {
                        $this->behavior->setConfig($fieldKey . '.field', $field, false);
                        break;
                    }
                }
            }
        }
    }

    public function initialize(): void
    {
        // junction must be applied at initialize, after, it's too late
        /** @var string[] $userFields */
        $userFields = $this->behavior->getConfig('fields.user');

        foreach ($userFields as $fieldKey) {
            if ($property = $this->behavior->getConfig($fieldKey . '.property')) {
                /** @var string $property */
                $this->behavior->table()->belongsTo(
                    ucfirst($property),
                    [
                         'foreignKey'    => $this->behavior->getConfig($fieldKey . '.field'),
                         'bindingKey'    => $this->behavior->getConfig('userModel.primaryKey'),
                         'className'     => $this->behavior->getConfig('userModel.className'),
                         'propertyName'  => $property,
                      ] + (
                          $this->behavior->getConfig('userModel.columns')
                          ? ['fields' => $this->behavior->getConfig('userModel.columns')]
                          : []
                      )
                );
            }
        }
    }

    /**
     * Check for user/time fields and fill them with currentUser id and FrozenTime::now('UTC')
     *
     * @param EntityInterface $entity
     * @param string[]|null $fields
     *
     * @throws \Exception
     */
    public function beforeProcess(EntityInterface &$entity, ?array $fields = []): void
    {
        if (empty($fields)) {
            $fields = $this->behavior->getConfig('fields.all', []);
        }

        /** @var string[] $fields */
        foreach ($fields as $fieldKey) {
            if ($this->behavior->getConfig($fieldKey . '.field')) {
                /** @var string[] $userFields */
                $userFields = $this->behavior->getConfig('fields.user');

                /** @var string[] $timeFields */
                $timeFields = $this->behavior->getConfig('fields.time');

                if (in_array($fieldKey, $userFields)) {
                    // only if empty
                    if (!isset($entity->{$this->behavior->getConfig($fieldKey . '.field')})) {
                        $entity->{$this->behavior->getConfig($fieldKey . '.field')} = get_current_user_id();
                    }
                } elseif (in_array($fieldKey, $timeFields)) {
                    // only if empty
                    $entity->{$this->behavior->getConfig($fieldKey . '.field')} =
                        new \DateTime('now', new \DateTimeZone('UTC'));
                }
            }
        }
    }
}
