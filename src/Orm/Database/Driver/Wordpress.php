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

namespace Gbg\Cake5\Orm\Database\Driver;

use Cake5\Database\Driver;

/**
 * Wordpress Driver
 */
class Wordpress extends Driver\Mysql
{
    public function __construct(array $config = [])
    {
        parent::__construct($config);
    }

    /*protected function wpLogQuery($begin, $queryString, $queryData, $wpError) {

        $duration = (microtime(true) - $begin);
        $callStack = wp_debug_backtrace_summary( __CLASS__ );

        global $wpdb;
        $queryData = apply_filters(
            'log_query_custom_data',
            $queryData,
            $queryString,
            $duration,
            wp_debug_backtrace_summary( __CLASS__ ),
            $begin
        );
        $queryLog = [ $queryString, $duration, $callStack, $begin, $queryData ];

        if ( is_plugin_active( 'query-monitor/query-monitor.php' ) ) {
            $queryLog['trace'] = class_exists('QM_Backtrace') ? new \QM_Backtrace() : null;
            $queryLog['result'] = $wpError ?: true;
        }

        $wpdb->num_queries++;
        $wpdb->queries[] = $queryLog;

    }*/

    /**
     * Execute the statement and log the query string.
     *
     * @param \Cake5\Database\StatementInterface $statement Statement to execute.
     * @param array|null $params List of values to be bound to query.
     * @return void
     */
    /*protected function executeStatement(StatementInterface $statement, ?array $params = null): void
    {

        $saveQueries = defined( 'SAVEQUERIES' ) && SAVEQUERIES;
        $begin = $saveQueries ? microtime( true ) : 0;
        $error = null;

        try {
            //gbgDump($statement, $params);
            parent::executeStatement($statement, $params);
            //gbgDump($statement);
        } catch(\Throwable $ex) {
            //gbgDump($ex->getMessage());
            $error = new \WP_Error(
                $ex->getCode(),
                $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine() . ' for query '
                . "\n" . $ex->getTraceAsString()
            );
        }
        //gbgDump($statement->queryString(), $statement->rowCount());

        if ($saveQueries) $this->wpLogQuery($begin, $statement->queryString(), $params, $error);

    }*/

    /*public function execute(string $sql, array $params = [], array $types = []): StatementInterface
    {

        //gbgDump($sql);
        $saveQueries = defined( 'SAVEQUERIES' ) && SAVEQUERIES;
        $begin = $saveQueries ? microtime( true ) : 0;
        $return = $error = null;

        try {
            $return = parent::execute($sql, $params, $types);
        } catch(\Throwable $ex) {
            //gbgDumpExit($ex);
            $error = new \WP_Error(
                $ex->getCode(),
                $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine() . ' for query ' . "\n"
                . $ex->getTraceAsString()
            );
        }

        if ($saveQueries) $this->wpLogQuery($begin, $sql, $params, $error);
        return $return;
    }*/
}
