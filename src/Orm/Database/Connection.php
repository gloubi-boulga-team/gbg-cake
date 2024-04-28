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

namespace Gbg\Cake5\Orm\Database;

use Cake5\Database\Connection as CakeConnection;
use Cake5\Database\Query;
use Cake5\Database\StatementInterface;
use Gbg\Cake5\Orm\Database\Log\QueryLogger;
use Throwable;

/**
 * A connection with a database server
 */
class Connection extends CakeConnection
{
    /**
     * @var QueryLogger|null $logger
     */
    protected ?QueryLogger $logger = null;

    /**
     * @var null|array<mixed> $queryInfo
     */
    protected ?array $queryInfo = null;

    public function __construct(array $config = [])
    {
        parent::__construct($config);
        $this->logger = QueryLogger::build();
    }

    /**
     * @param string $sql
     * @param mixed[] $params
     * @param string[] $types
     *
     * @return StatementInterface
     * @throws Throwable
     */
    public function execute(string $sql, array $params = [], array $types = []): StatementInterface
    {
        try {
            $this->beforeQuery();
            $return = parent::execute($sql, $params, $types);
        } catch (Throwable $ex) {
            $wpError = new \WP_Error(
                $ex->getCode(),
                $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine() . ' for query '
                . "\n" . $ex->getTraceAsString()
            );
            $this->afterQuery([$sql, $params], $wpError);
            throw $ex;
        }

        $this->afterQuery([$sql, $params, $types], $return);

        return $return;
    }

    /**
     * @inheritDoc
     *
     * @param Query $query
     *
     * @return StatementInterface
     * @throws Throwable
     */
    public function run(Query $query): StatementInterface
    {
        try {
            $this->beforeQuery();
            $return = parent::run($query);
        } catch (Throwable $ex) {
            $wpError = new \WP_Error(
                $ex->getCode(),
                $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine() . ' for query ' . "\n"
                . $ex->getTraceAsString()
            );
            $this->afterQuery($query, $wpError);
            throw $ex;
        }

        $this->afterQuery($query, $return);

        return $return;
    }

    /**
     * Run processed before query
     */
    protected function beforeQuery(): void
    {
        if (!$this->logger) {
            return;
        }
        $this->queryInfo = [
            'start'    => microtime(true),
            'duration' => 0,
            'wpError'  => null
        ];
    }

    /**
     * @param mixed $query
     * @param mixed $result
     *
     * @throws \Exception
     */
    protected function afterQuery(mixed $query, mixed $result): void
    {
        if (!$this->logger) {
            return;
        }

        $this->queryInfo['query'] = $query;
        $this->queryInfo['duration'] = floatval(
            number_format(microtime(true) - $this->queryInfo['start'], 6)
        );
        $this->queryInfo['callStack'] = wp_debug_backtrace_summary(__CLASS__);
        $this->queryInfo['numRows'] = $result instanceof StatementInterface ? $result->rowCount() : 0;
        $this->queryInfo['result'] = '~~ no result available ~~';

        $this->logger->log($this->queryInfo);
    }
}
