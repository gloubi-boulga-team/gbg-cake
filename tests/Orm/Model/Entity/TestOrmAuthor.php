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

final class TestOrmAuthor extends Entity
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $options
     */
    public function __construct(array $data, array $options)
    {
        parent::__construct($data, $options);
    }

    // phpcs:disable PSR2.Methods.MethodDeclaration.Underscore -- CakePHP core syntax
    protected function _getVirtualProperty(): mixed
    {
        // phpcs:enable
        return $this['fakeProperty'] ??
            (get_class($this) . "::__getVirtualProperty - " . $this->id . " => " . $this['name']);
    }

    // phpcs:disable PSR2.Methods.MethodDeclaration.Underscore -- CakePHP core syntax
    protected function _setVirtualProperty(mixed $value): void
    {
        // phpcs:enable
        $this['fakeProperty'] = $value;
    }
}
