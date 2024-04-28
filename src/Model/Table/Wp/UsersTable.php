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

namespace Gbg\Cake5\Model\Table\Wp;

use Gbg\Cake5\Orm\Table;

class UsersTable extends Table
{
    // phpcs:disable PSR2.Classes.PropertyDeclaration.Underscore -- CakePHP core syntax
    protected array $_defaultConfig = [
        'hasMany' => [
            'Wp.Usermeta' => [
                'foreignKey' => 'user_id'
            ],
            'Wp.Posts' => [
                'foreignKey' => 'post_author'
            ],
        ]
    ];
    // phpcs:enable

    public function initialize(array $config): void
    {
        $this->setTable(gbgGetWpdb()->prefix . 'users');
        parent::initialize($config);
    }
}
