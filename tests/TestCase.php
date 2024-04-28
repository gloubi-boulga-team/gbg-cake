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

namespace Gbg\Cake5;

class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @param array<int, array<int, string|callable|array|mixed>> $items
     */
    protected function testBulkEquals(array $items): void
    {
        $i = 1;
        foreach ($items as $params) {
            /** @phpstan-ignore-next-line */
            $this->testBulkEqualsItem($params[0] . ' #' . $i++ . ' ', $params[1], $params[2], $params[3]);
        }
    }

    /**
     * @param string $message
     * @param callable $callback
     * @param array<int, mixed> $args
     * @param mixed $expected
     */
    protected function testBulkEqualsItem(string $message, callable $callback, array $args, mixed $expected): void
    {
        $result = call_user_func_array($callback, $args);
        $this->assertEquals($expected, $result, $message);
    }

    protected function testException(string $exception, callable $callback, string $message = ''): mixed
    {
        try {
            return $callback();
        } catch (\Throwable $ex) {
            if ($ex::class === $exception) {
                $this->assertTrue(true);
            } else {
                $this->fail(
                    'Actual ' . $ex::class . ' VS expected ' . $exception . ' : ' .
                    $ex->getMessage() . ($message ? ' : ' . $message : '')
                );
            }
        }
        return null;
    }
}
