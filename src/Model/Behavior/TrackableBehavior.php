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

use ArrayObject;
use Cake5\Datasource\EntityInterface;
use Cake5\Event\EventInterface;
use Cake5\ORM\Table;
use Exception;
use Gbg\Cake5\Model\Table\Wp\UsersTable;

/**
 * TrackableBehavior behavior
 * Simply manage createdBy/modifiedBy and createdAt/modifiedAt properties
 */
class TrackableBehavior extends Behavior
{
    /**
     * @var ?TimeAndUserProcessor
     */
    protected ?TimeAndUserProcessor $processor = null;

    /**
     * @inheritdoc
     * Default configuration.
     *
     * @var array<string, mixed>
     */
    // phpcs:disable PSR2.Classes.PropertyDeclaration.Underscore -- CakePHP core syntax
    protected array $_defaultConfig = [
        'createdAt'     => ['field' => ['created_at']],
        'createdBy'     => ['field' => 'created_by', 'property' => 'createdBy'],
        'modifiedAt'    => ['field' => ['modified_at']],
        'modifiedBy'    => ['field' => ['modified_by'], 'property' => 'modifiedBy'],
        'userModel' => [
            'className'  => UsersTable::class,
            'primaryKey' => 'ID',
            'columns'    => ['ID', 'user_login', 'user_nicename', 'user_email', 'user_url', 'display_name'],
        ],
    ];
    // phpcs:enable

    /**
     * @param Table $table
     * @param array<string, mixed> $config
     */
    public function __construct(Table $table, array $config = [])
    {
        parent::__construct($table, $config);
    }

    /**
     * @param array<string, mixed> $config
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->processor = new TimeAndUserProcessor($this);
        $this->processor->initialize();
    }

    /**
     * @param EventInterface $event
     * @param EntityInterface $entity
     * @param ArrayObject $options
     *
     * @throws Exception
     */
    public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        if ($this->shouldIgnoreCallback($options)) {
            return;
        }
        $this->processor?->beforeProcess($entity, $entity->isNew() ?
                [ 'createdAt', 'createdBy', 'modifiedAt', 'modifiedBy' ] :
                [ 'modifiedAt', 'modifiedBy' ]);
    }
}
