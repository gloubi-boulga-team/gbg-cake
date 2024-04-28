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

namespace Gbg\Cake5\Orm\Database\Log;

use Cake5\Database\Connection;
use Cake5\Database\Query;
use Cake5\Datasource\QueryInterface;
use Cake5\Utility\Hash;
use Gbg\Cake5\Http\Request;
use Gbg\Cake5\Orm\QueryTools;
use Gbg\Cake5\Wrapper\Log;

/** @phpstan-consistent-constructor */
// Overriding constructor may require overriding `build` function
class QueryLogger
{
    /**
     * @var float
     */
    protected float $totalTime = 0.0;

    /**
     * @var int
     */
    protected int $totalRows = 0;

    /**
     * @var int
     */
    protected int $totalCount = 0;

    /**
     * if WP const SAVEQUERIES is set and true
     * @var bool
     */
    protected bool $saveQueries = false;

    /**
     * The current index (related to $wpdb->queries) already processed
     * @var int
     */
    protected int $wpQueriesProcessed = 0;

    /**
     * @var array<string, mixed>>
     */
    protected static array $config = [];

    /**
     * @var static[]
     */
    protected static array $instances = [];

    /**
     * Constructor (just in case)
     */
    public function __construct()
    {
        static::$instances[] = $this;

        // phpcs:ignore
        $this->saveQueries = defined('SAVEQUERIES') && SAVEQUERIES; /** @phpstan-ignore-line */
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function processWpQueries(): void
    {
        global $wpdb;
        if (count($wpdb->queries) > $this->wpQueriesProcessed) {
            $queries = array_slice(
                $wpdb->queries,
                $this->wpQueriesProcessed,
                count($wpdb->queries) - $this->wpQueriesProcessed
            );
            $this->wpQueriesProcessed = count($wpdb->queries);
            foreach ($queries as $query) {
                $this->processQuery(
                    [
                        'query'     => $query[0],
                        'duration'  => $query[1],
                        'numRows'   => 0,
                        'callStack' => $query[2] ?? null,
                        'start'     => $query[3] ?? null,
                        'result'    => $query[4] ?? null
                    ],
                    ['fromWp' => true]
                );
            }
        }
    }

    /**
     * @return static|null
     */
    public static function build(): ?static
    {
        $saveQueries = defined('SAVEQUERIES') && SAVEQUERIES; /** @phpstan-ignore-line */

        return (static::getConfig('status') || $saveQueries) ? new static() : null;
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function initialize(array $config): void
    {
        static::$config = $config;
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function getConfig(string $key, mixed $default = null): mixed
    {
        return !static::$config ? $default : Hash::get(static::$config, $key, $default);
    }

    /**
     *
     */
    public static function finalize(): void
    {
        foreach (static::$instances as $instance) {
            $instance->processWpQueries();
        }
    }

    /**
     * @param array<Query|string|null|int|array<string,mixed[]>> $queryInfo
     * @param array<string|bool> $context
     *
     * @return void
     * @throws \Exception
     */
    public function processQuery(array $queryInfo, array $context = []): void
    {
        $context += ['fromWp' => false];

        if ($queryInfo['query'] instanceof Query) {
            $queryInfo['query'] = QueryTools::getQueryCompiledSql($queryInfo['query']);
        } elseif (is_array($queryInfo['query'])) {
            /** @phpstan-ignore-next-line */
            $queryInfo['query'] = QueryTools::getPdoCompiledSql($queryInfo['query'][0], $queryInfo['query'][1]);
        }

        if (!$context['fromWp']) {
            $queryData = apply_filters(
                'log_query_custom_data',
                null,
                $queryInfo['query'],
                $queryInfo['duration'],
                $queryInfo['callStack'],
                $queryInfo['start']
            );

            if ($this->saveQueries) {
                $queryLog = [
                    $queryInfo['query'],
                    $queryInfo['duration'],
                    $queryInfo['callStack'],
                    $queryInfo['start'],
                    $queryData
                ];

                if (is_plugin_active('query-monitor/query-monitor.php')) {
                    $queryLog['trace'] = class_exists('QM_Backtrace') ? new \QM_Backtrace() : null;
                    $queryLog['result'] = '~~ result not available ~~';
                }

                global $wpdb;
                $wpdb->num_queries++;
                $wpdb->queries[] = $queryLog;
            }
        }

        $request = Request::instance();
        $conditionBaseValues = [
            'ip'  => $request->getClientIp(),
            'uri' => $request->getCurrentUri(),
            'url' => $request->getCurrentUrl(),
        ];

        /** @var array<string, string|int|float> $conditionValues */
        $conditionValues = $conditionBaseValues + [
                'datetime'   => (new \DateTime())->format('Y-m-d H:i:s'),
                'query'      => $queryInfo['query'],
                'duration'   => $queryInfo['duration'],
                'rows'       => $queryInfo['numRows'] ?? '?',
                'callStack'  => $queryInfo['callStack'],
                'userId'     => get_current_user_id(),
                'level'      => 'info',
                'connection' => $this->connection ?? '?',
            ];

        try {

            /** @var array{query: string, duration: ?int, numRows: ?int} $queryInfo */
            $sql = $queryInfo['query'];
            $this->totalCount++;
            $this->totalTime += $queryInfo['duration'];
            $this->totalRows += $queryInfo['numRows'] ?? 0;

            if (!$this->isLoggable($sql, $conditionValues)) {
                return;
            }

            if (!static::getConfig('includeSchema') && $this->isSchemaQuery($sql)) {
                return;
            }

            // $newLines = [" FROM ", " INNER JOIN ", " LEFT JOIN ", " WHERE ", " ORDER BY ", " GROUP BY ", " LIMIT "];
            // foreach($newLines as $newLine) {
            //  $conditionValues['query'] = str_replace($newLine, "\n" . $newLine, $conditionValues['query']);
            // }

            /** @var string $message */
            $message = static::getConfig('pattern', '{datetime} {level}: connection={connection} duration={duration} rows={rows} uri={uri} ip={ip} userid={userId}\n{query}'); // phpcs:ignore Generic.Files.LineLength

            foreach ($conditionValues as $k => $v) {
                $message = str_replace('{' . $k . '}', strval($v), $message);
            }

            $message = str_replace(
                '{callStack}',
                str_replace(', ', PHP_EOL, strval($conditionValues['callStack'] ?? '')),
                $message
            );

            Log::info($message, ['queries']);
        } catch (\Throwable $ex) {
            Log::error($ex, ['queries']);
        }
    }

    /**
     * @param array<Query|string|null|int|array<string,mixed[]>> $queryInfo
     * @param array<bool|string> $context
     * @throws \Exception
     */
    public function log(array $queryInfo, array $context = []): void
    {
        if ($this->saveQueries && static::getConfig('includeWp')) {
            $this->processWpQueries();
        }

        if (static::getConfig('status')) {
            $this->processQuery($queryInfo, $context);
        }
    }

    /**
     * @param string $sql
     * @param array<mixed> $conditionValues
     *
     * @return bool
     * @throws \Exception
     */
    protected function isLoggable(string $sql, array $conditionValues): bool
    {
        $logStatus = static::getConfig('status');

        /** @var array<string, array<mixed>> $logConditions */
        $logConditions = static::getConfig('conditions', []);

        if ($logStatus === true) {
            return true;
        }

        if ($logStatus !== 'conditional') {
            return false;
        }

        foreach ($conditionValues as $condition => $value) {
            if (!isset($logConditions[$condition])) {
                continue;
            }

            $conditions = $logConditions[$condition];
            /** @var array<string, string> $conditions */
            foreach ($conditions as $conditionK => $conditionV) {
                if (!$this->evaluateCondition($value, $conditionK, $conditionV)) {
                    return false;
                }
            }
        }

        return true;
    }


    /**
     * Detect if a sql query is a schema query (ex: SHOW FULL COLUMNS FROM wp_my_table)
     *
     * @param string $sql
     *
     * @return bool
     */
    protected function isSchemaQuery(string $sql): bool
    {
        return // Multiple engines
            str_contains($sql, 'FROM information_schema') ||
            // Postgres
            str_contains($sql, 'FROM pg_catalog') ||
            // MySQL
            str_starts_with($sql, 'SHOW TABLE') ||
            str_starts_with($sql, 'SHOW FULL COLUMNS') ||
            str_starts_with($sql, 'SHOW INDEXES') ||
            // Sqlite
            str_contains($sql, 'FROM sqlite_master') ||
            str_starts_with($sql, 'PRAGMA') ||
            // Sqlserver
            str_contains($sql, 'FROM INFORMATION_SCHEMA') ||
            str_contains($sql, 'FROM sys.');
    }

    /**
     * Evaluate a condition
     *
     * @param mixed $value
     * @param string $operator on of :
     *      - equal, notEqual, equalStrict, notEqualStrict
     *      - =, ==, ===, !=, !==
     *      - gt, gte, lt, lte
     *      - >, >=, <, <=
     *      - contains, notContains, startsWidth, notStartsWidth, endsWidth, notEndsWith
     *      - in, notIn, inStrict, notInStrict
     *      - regexp, notRegexp
     * @param mixed $compared
     *
     * @return bool
     * @throws \Exception
     */
    public function evaluateCondition(mixed $value, string $operator, mixed $compared): bool
    {

        switch ($operator) {
            case 'equals':
            case '=':
            case '==':
                return $value == $compared;
            case 'equalsStrict':
            case '===':
                return $value === $compared;
            case 'notEquals':
            case '!=':
                return $value != $compared;
            case 'notEqualsStrict':
            case '!==':
                return $value !== $compared;
            case 'gt':
            case '>':
                return $value > $compared;
            case 'gte':
            case '>=':
                return $value >= $compared;
            case 'lt':
            case '<':
                return $value < $compared;
            case 'lte':
            case '<=':
                return $value <= $compared;
            case 'in':
                return in_array($value, (array)$compared);
            case 'inStrict':
                return in_array($value, (array)$compared, true);
            case 'notIn':
                return !in_array($value, (array)$compared);
            case 'notInStrict':
                return !in_array($value, (array)$compared, true);
            default:
                // string operators
                $value = print_r($value, true);
                $compared = print_r($compared, true);

                if ($operator === 'contains') {
                    return str_contains($value, $compared);
                } elseif ($operator === 'notContains') {
                    return !str_contains($value, $compared);
                } elseif ($operator === 'startsWith') {
                    return str_starts_with($value, $compared);
                } elseif ($operator === 'notStartsWith') {
                    return !str_starts_with($value, $compared);
                } elseif ($operator === 'endsWith') {
                    return str_ends_with($value, $compared);
                } elseif ($operator === 'notEndsWith') {
                    return !str_ends_with($value, $compared);
                } elseif ($operator === 'regexp') {
                    return boolval(preg_match($compared, $value));
                } elseif ($operator === 'notRegexp') {
                    return !preg_match($compared, $value);
                }
        }

        throw new \Exception('Unknown operator');
    }
}
