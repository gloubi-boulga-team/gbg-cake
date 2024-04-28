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

namespace Gbg\Cake5\Wrapper;

use Gbg\Cake5\TestCase;

class HashTest extends TestCase
{
    /**
     * @inheritDoc
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
    }

    /**
     * @inheritDoc
     */
    public static function tearDownAfterClass(): void
    {
        parent::setUpBeforeClass();
    }

    public function testEnsureKey(): void
    {
        $data = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ];
        $data = Hash::ensureKey($data, 'key4', 'value4');
        $this->assertEquals('value4', $data['key4'] ?? null);

        $data = Hash::ensureKey($data, ['key5', 'key6'], 'values');
        $this->assertEquals('values', $data['key5'] ?? null);

        $data = Hash::ensureKey($data, ['key5', 'key6'], 'values');
        $this->assertEquals('values', $data['key6'] ?? null);
    }

    public function testResolveFormula(): void
    {
        $data = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
            'key4' => ['sub1' => 'subvalue1', 'sub2' => 'subvalue2'],
        ];

        $resolved = Hash::resolveFormula($data, '{key1} {key4.sub1} {key4.sub2} {key5}', ['removeNotFound' => true]);
        $this->assertEquals('value1 subvalue1 subvalue2', $resolved);

        $resolved = Hash::resolveFormula($data, '{key1} {key4.sub1} {key4.sub2} {key5}', ['removeNotFound' => false]);
        $this->assertEquals('value1 subvalue1 subvalue2 {key5}', $resolved);

        $resolved = Hash::resolveFormula([], '{key1} {key4.sub1} {key4.sub2} {key5}', ['removeNotFound' => false]);
        $this->assertEquals('{key1} {key4.sub1} {key4.sub2} {key5}', $resolved);

        $resolved = Hash::resolveFormula(
            $data,
            '{key1} {key4.sub1} {key4.sub2} {key5|key2}',
            ['removeNotFound' => true]
        );
        $this->assertEquals('value1 subvalue1 subvalue2 value2', $resolved);

        $resolved = Hash::resolveFormula(
            $data,
            '{key1} {key4.sub1} {key4.sub2} {key5| key2}',
            ['removeNotFound' => true]
        );
        $this->assertEquals('value1 subvalue1 subvalue2  value2', $resolved);

        $resolved = Hash::resolveFormula($data, '{key1}{ key5| key2}', ['removeNotFound' => false]);
        $this->assertEquals('value1 value2', $resolved);

        $resolved = Hash::resolveFormula($data, '{key1}{ key9| key8}', ['removeNotFound' => true]);
        $this->assertEquals('value1', $resolved);

        $resolved = Hash::resolveFormula($data, 'key1', ['removeNotFound' => true]);
        $this->assertEquals('value1', $resolved);

        $resolved = Hash::resolveFormula($data, 'key9', ['removeNotFound' => true]);
        $this->assertEquals('key9', $resolved);
    }
}
