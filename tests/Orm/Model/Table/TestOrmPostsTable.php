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

use Cake5\Validation\Validator;
use Gbg\Cake5\Orm\CallbackTrait;
use Gbg\Cake5\Orm\Table;

class TestOrmPostsTable extends Table
{
    use CallbackTrait;

    /**
     * @inheritdoc
     * @var array<string, mixed>
     */
    // phpcs:disable PSR2.Classes.PropertyDeclaration.Underscore -- CakePHP core syntax
    protected array $_defaultConfig = [
        'behaviors'     => [
            'Gbg/Cake5.Trackable',
            'Gbg/Cake5.Archivable'   => ['archivedAt' => ['field' => 'deleted_at']]
        ],
    ];
    // phpcs:enable

    /**
     * Validate data before saving
     *
     * @param Validator $validator Instance de validation.
     *
     * @return Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            // test length between 0 and 250
            ->lengthBetween('name', [1, 250], 'Name must be between 1 and 250 characters')
            // non negative integer
            ->nonNegativeInteger('status', 'Status must be a non negative integer between 0 and 2')
            // between 1 and 4
            ->greaterThanOrEqual('status', 0, 'Status must be a non negative integer between 0 and 2')
            ->lessThanOrEqual('status', 2, 'Status must be a non negative integer between 0 and 2')
            // check ascii
            ->ascii('secret', 'Secret must be ascii');

        return $validator;
    }
}
