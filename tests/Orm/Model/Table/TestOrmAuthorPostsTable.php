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

use Cake5\Event\EventInterface;
use Cake5\ORM\Query\SelectQuery;
use Gbg\Cake5\Orm\Table;

class TestOrmAuthorPostsTable extends Table
{
    /**
     * @inheritdoc
     *
     * @var array<string, mixed>
     */
    // phpcs:disable PSR2.Classes.PropertyDeclaration.Underscore -- CakePHP Core variable
    protected array $_defaultConfig = [
        'behaviors'     => [ 'Gbg/Cake5.Trackable', 'Gbg/Cake5.Archivable' ],
    ];
    // phpcs:enable

    public function beforeFind(
        EventInterface $event,
        SelectQuery $query,
        \ArrayObject $options,
        bool $primary
    ): void {
        $query->where(["'" . __CLASS__ . "::before_find' = '" . __CLASS__ . "::before_find'"]);
    }
}
