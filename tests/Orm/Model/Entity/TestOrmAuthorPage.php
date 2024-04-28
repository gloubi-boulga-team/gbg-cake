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

namespace Gbg\Cake5\Model\Entity;

use Gbg\Cake5\Orm\Entity;

final class TestOrmAuthorPage extends Entity
{
    // phpcs:disable PSR2.Classes.MethodDeclaration.Underscore -- CakePHP core syntax
    protected function __getVirtualProperty1(): mixed
    {
        return get_class($this) . "::__getVirtualProperty1 : " . $this->id . " => " . $this['name'];
    }

    protected function __setVirtualProperty1(mixed $value): void
    {
        $this['fakeProperty1'] = $value;
    }

    protected function __getVirtualProperty2(): mixed
    {
        return get_class($this) . "::__getVirtualProperty2 : " . $this->id . " => " . $this['name'];
    }

    protected function __setVirtualProperty2(mixed $value): void
    {
        $this['fakeProperty2'] = $value;
    }
    // phpcs:enable
}
