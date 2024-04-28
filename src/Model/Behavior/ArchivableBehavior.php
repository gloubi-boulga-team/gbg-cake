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
use Cake5\Event\Event;
use Cake5\Event\EventInterface;
use Cake5\ORM\Query\SelectQuery;
use Cake5\ORM\Table;
use Gbg\Cake5\Model\Table\Wp\UsersTable;
use Gbg\Cake5\Orm\QueryTools;

/**
 * Archivable behavior
 *      - can automatically replace a `DELETE` sql query by an `UPDATE ... SET archived_at = ...`
 *      - and will add the condition `where archived_at IS NULL` at `SELECT` queries
 */
class ArchivableBehavior extends Behavior
{
    /**
     * @var TimeAndUserProcessor|null
     */
    protected ?TimeAndUserProcessor $processor = null;

    /**
     * Default configuration.
     * @inheritdoc
     *
     * @var array<string, mixed>
     */
    // phpcs:disable PSR2.Classes.PropertyDeclaration.Underscore -- CakePHP Core variable
    protected array $_defaultConfig = [
        'archivedBy'  => ['field' => ['archived_by'], 'property' => 'archivedBy'],
        'archivedAt'  => ['field' => ['archived_at', 'archived']],
        'actions'     => ['beforeFind', 'beforeDelete'],
        'userModel' => [
            'className'  => UsersTable::class,
            'primaryKey' => 'ID',
            'columns'    => ['ID', 'user_login', 'user_nicename', 'user_email', 'user_url', 'display_name'],
        ],

        // 'archiveDependent', can also be an array of dependent tables
        // 'archiveDependent'  => false
    ];
    // phpcs:enable

    /**
     * @param Table $table
     * @param array<string, mixed> $config
     */
    public function __construct(Table $table, array $config = [])
    {
        parent::__construct($table, $config);
        if (isset($config['actions'])) {
            $this->setConfig('actions', $config['actions'], false);
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        if (isset($config['actions'])) {
            $this->setConfig('actions', $config['actions'], false);
        }
        $this->processor = new TimeAndUserProcessor($this);
        $this->processor->initialize();
    }

    /**
     * @param EventInterface $event
     * @param SelectQuery $query
     * @param ArrayObject $options
     * @param boolean $primary
     *
     * @return void
     * @throws \Exception
     */
    public function beforeFind(
        EventInterface $event,
        SelectQuery $query,
        \ArrayObject $options,
        bool $primary
    ): void {

        if ($this->shouldIgnoreCallback($options)) {
            return;
        }

        /** @var string[] $actions */
        $actions = $this->getConfig('actions', []);

        if (in_array('beforeFind', $actions, true) && $this->getConfig('archivedAt.field')) {
            /** @var Table $table */
            $table = $event->getSubject();
            $query->where([$table->getAlias() . '.' . $this->getConfig('archivedAt.field') . ' IS' => null]);
        }
    }

    /**
     * @param EventInterface $event
     * @param EntityInterface $entity
     * @param ArrayObject $options
     *
     * @return mixed
     * @throws \Exception
     */
    public function beforeDelete(EventInterface $event, EntityInterface $entity, ArrayObject $options): mixed
    {
        /** @var string[] $actions */
        $actions = $this->getConfig('actions', []);
        if (!in_array('beforeDelete', $actions) || $this->shouldIgnoreCallback($options)) {
            return null;
        }
        $event->stopPropagation();
        return $this->bury($entity, $event);
    }

    /**
     * @param EntityInterface $entity
     * @param EventInterface $event
     *
     * @return mixed
     * @throws \Exception
     */
    public function bury(EntityInterface &$entity, EventInterface $event): mixed
    {
        if (!$this->processor) {
            return $entity;
        }
        $this->processor->beforeProcess($entity);

        /** @var Table $table */
        $table = $event->getSubject();

        return $table->save($entity);
    }

    /**
     * @param EntityInterface $entity
     * @param EventInterface|null $event
     * @return EntityInterface|bool
     */
    public function exhume(EntityInterface $entity, ?EventInterface $event = null): EntityInterface|bool
    {
        if ($this->getConfig('archivedAt.field')) {
            $entity->{$this->getConfig('archivedAt.field')} = null;
        }

        if ($this->getConfig('archivedBy.field')) {
            $entity->{$this->getConfig('archivedBy.field')} = null;
        }

        return $this->table()->save($entity, ['ignoreCallbacks' => true]);
    }
}
