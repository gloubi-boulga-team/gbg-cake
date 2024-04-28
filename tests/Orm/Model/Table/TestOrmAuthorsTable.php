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

namespace Gbg\Cake5\Model\Table;

use Cake5\Datasource\EntityInterface;
use Cake5\Event\EventInterface;
use Cake5\ORM\Query\SelectQuery;
use Gbg\Cake5\Orm\CallbackTrait;
use Gbg\Cake5\Orm\Table;

class TestOrmAuthorsTable extends Table
{
    use CallbackTrait;

    /**
     * @var array<string, mixed>
     */
    // phpcs:disable PSR2.Classes.PropertyDeclaration.Underscore -- CakePHP core syntax
    protected array $_defaultConfig = [
        'types'         => [
            'json'      => ['json_col'],
            'ip'        => 'ip_col',
            'serialize' => ['serialize_col'],
        ],
        'behaviors'     => [
            'Gbg/Cake5.Trackable',
            'Gbg/Cake5.Archivable' => [
                'archivedAt' => ['field' => 'deleted_at'],
                'archivedBy' => ['field' => 'deleted_by']
            ]
        ],
        'belongsToMany' => [
            'Gbg/Cake5.TestOrmPosts' => [
                'through'          => 'Gbg/Cake5.TestOrmAuthorPosts',
                //'className' => TestOrmPostsTable::class,
                'foreignKey'       => 'author_id',
                'targetForeignKey' => 'post_id',
                /*'throughX'          =>  [
                    'alias' => 'TestOrmAuthorPosts',
                    'className' => TestOrmAuthorPostsTable::class
                ]*/
            ]
        ],
        'hasMany'       => [
            'Gbg/Cake5.TestOrmAuthorPosts' => [
                //'className' => TestOrmAuthorPostsTable::class,
                'foreignKey' => 'author_id',
                'bindingKey' => 'id',
                'dependent'  => true
            ],
        ],
        'hasOne'        => [
            'Gbg/Cake5.TestOrmAuthorTypes' => [
                'bingingKey' => 'type_id',
                'foreignKey' => 'id',
            ]
        ],
        'belongsTo'     => [
            'Gbg/Cake5.TestOrmAuthorMetas' => [
                'foreignKey' => 'meta_id',
                'dependent'  => true
            ],
            'TestOrmAuthorMetasAlias'      => [
                'foreignKey' => 'meta_id',
                'className'  => TestOrmAuthorMetasTable::class
            ]
        ]
    ];
    // phpcs:enable

    public function beforeFind(
        EventInterface $event,
        SelectQuery $query,
        \ArrayObject $options,
        bool $primary
    ): void {
        if ($this->shouldIgnoreCallback($options)) {
            return;
        }

        $query->where(["'1' /* TestOrmAuthorsTable::beforeFind */ = '1'"]);
    }

    public function beforeSave(EventInterface $event, EntityInterface $entity, \ArrayObject $options): void
    {
        if ($this->shouldIgnoreCallback($options)) {
            return;
        }

        $entity['details'] .= ' TestOrmAuthorsTable:beforeSave';
    }

    public function afterSaveCommit(EventInterface $event, EntityInterface $entity, \ArrayObject $options): void
    {
        if ($this->shouldIgnoreCallback($options)) {
            return;
        }

        $entity['details'] .= ' TestOrmAuthorsTable:afterSave';
    }

    public function beforeDelete(EventInterface $event, EntityInterface $entity, \ArrayObject $options): void
    {
        if ($this->shouldIgnoreCallback($options)) {
            return;
        }

        $entity['details'] .= ' TestOrmAuthorsTable:beforeDelete';
    }

    public function afterDeleteCommit(EventInterface $event, EntityInterface $entity, \ArrayObject $options): void
    {
        if ($this->shouldIgnoreCallback($options)) {
            return;
        }

        $entity['details'] .= ' TestOrmAuthorsTable:afterDelete';
    }
}
