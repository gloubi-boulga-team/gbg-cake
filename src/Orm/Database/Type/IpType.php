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

namespace Gbg\Cake5\Orm\Database\Type;

use Cake5\Database\Driver;
use Cake5\Database\Type\BaseType;
use Cake5\Database\Type\BatchCastingInterface;
use InvalidArgumentException;

/**
 * Ip type converter.
 *
 * Use to convert Ip data between PHP and the database types (varBinary(16)).
 */
class IpType extends BaseType implements BatchCastingInterface
{
    /**
     * @inheritDoc
     *
     * Convert a value data into a Ip string
     */
    public function toDatabase(mixed $value, Driver $driver): ?string
    {
        if (is_resource($value)) {
            throw new InvalidArgumentException('Cannot convert a resource value to Ip');
        }

        return !is_string($value) ? null : (inet_pton($value) ?: null);
    }

    /**
     * {@inheritDoc}
     */
    public function toPHP(mixed $value, Driver $driver): mixed
    {
        return !is_string($value) ? null : inet_ntop($value);
    }

    /**
     * @inheritDoc
     * @param array<string, mixed> $values The original array of values containing the fields to be casted
     * @param list<string> $fields The field keys to cast
     * @param Driver $driver Object from which database preferences and configuration will be extracted.
     *
     * @return array<string, mixed>
     */
    public function manyToPHP(array $values, array $fields, Driver $driver): array
    {
        foreach ($fields as $field) {
            if (!isset($values[$field])) {
                continue;
            }

            $values[$field] = $this->toPHP($values[$field], $driver);
        }

        return $values;
    }

    /**
     * @inheritDoc
     */
    public function toStatement(mixed $value, Driver $driver): int
    {
        // phpcs:ignore WordPress.DB.RestrictedClasses.mysql__PDO
        return \PDO::PARAM_STR;
    }

    /**
     * @inheritDoc
     */
    public function marshal(mixed $value): mixed
    {
        return $value;
    }
}
