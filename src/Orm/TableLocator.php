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

namespace Gbg\Cake5\Orm;

use Cake5\Database\StatementInterface;
use Cake5\Datasource\ConnectionInterface;
use Cake5\Datasource\ConnectionManager;
use Cake5\Datasource\RepositoryInterface;
use Cake5\ORM\Table;
use Cake5\Utility\Inflector;
use Exception;
use Gbg\Cake5\Orm\Database\Connection;
use Gbg\Cake5\Wrapper\Cache;

/**
 * Provides a default registry/factory for Table objects
 */
class TableLocator extends \Cake5\ORM\Locator\TableLocator
{
    /**
     * Table list cache
     *
     * @var array<string, string[]>
     */
    protected static array $tableList = [];

    /**
     * @inheritDoc
     *
     * Get the table class name.
     * Called by CakePHP Core for resolving table class name : TableRegistry::getTableLocator()->get('LittleShoes')
     *
     * @param string $alias
     * @param array<string, mixed> $options
     *
     * @return string|null
     */
    // phpcs:disable PSR2.Methods.MethodDeclaration.Underscore -- CakePHP core method
    protected function _getClassName(string $alias, array $options = []): ?string
    {
        // phpcs:enable
        return static::resolveClassName($alias, $options);
    }


    /**
     * @inheritDoc
     *
     * Called when a table class is instantiated
     * Called by CakePHP Core for resolving table class name :
     *      - TableRegistry::getTableLocator()->get('Gbg/Core5.BigFoot')
     *
     * @param array<string, mixed> $options
     *
     * @return Table
     */
    // phpcs:disable PSR2.Methods.MethodDeclaration.Underscore -- CakePHP core method
    protected function _create(array $options): Table
    {
        // phpcs:enable
        /** @var array{className: string, registryAlias: string} $options */
        if (
            empty($options['className'])
            || $options['className'] === 'Cake5\ORM\Table\Table'
            || !class_exists($options['className'])
        ) {
            $options['className'] = static::resolveClassName($options['registryAlias'], $options);
        }

        return parent::_create($options);
    }

    /**
     * List all database tables of a connection
     *
     * @param ConnectionInterface|string $connection
     * @param bool $refresh
     *
     * @return array<string>
     * @throws Exception
     */
    public static function listTables(ConnectionInterface|string $connection = 'default', bool $refresh = false): array
    {
        if (is_string($connection)) {
            $connection = ConnectionManager::get($connection);
        }
        /** @var Connection $connection */
        $connectionName = $connection->configName();
        $cache = Cache::get('Gbg/Cake5.dbSchemas');
        $cacheKey = $connectionName . '_tables';
        if ($refresh) {
            static::$tableList = [];
            $cache?->delete($connectionName . '_tables');
        }

        if (empty(static::$tableList[$connectionName])) {
            /** @phpstan-ignore-next-line */
            if (!static::$tableList[$connectionName] = $cache?->read($connectionName . '_tables')) {
                static::$tableList[$connectionName] = $connection->getSchemaCollection()->listTables();
                $cache?->write($cacheKey, static::$tableList[$connectionName]);
            }
        }

        /** @phpstan-ignore-next-line */
        return static::$tableList[$connectionName] ?? [];
    }


    /**
     * Copy a table
     *
     * @param string|Table $from
     * @param string $to
     * @param array<string, string> $options
     * @return StatementInterface
     */
    public static function copyTable(
        string|Table $from,
        string $to,
        array $options = []
    ): StatementInterface {

        if ($from instanceof Table) {
            $connection = $from->getConnection();
            $from = $from->getTable();
        } else {
            $connection = ConnectionManager::get($options['connection'] ?? 'default');
        }

        /** @var Connection $connection */
        $connection->execute(sprintf('CREATE TABLE `%s` LIKE `%s`;', $to, $from));

        return $connection->execute(sprintf('INSERT INTO `%s` SELECT * FROM `%s`;', $to, $from));
    }

    /**
     * Drop a table from its real name
     * ```
     *      TableLocator::dropTable('Gbg/Core.Options');
     *                  -> will throw an exception
     *
     *      TableLocator::dropTable(TableLocator::resolveTableName('Gbg/Core.Options'));
     *                  -> will drop wp_gbg_core_options
     *
     *      TableLocator::dropTable('wp_gbg_core_options');
     *                  -> will drop table wp_gbg_core_options
     * ```
     *
     * @param string|Table $table
     * @param ConnectionInterface|null $connection
     *
     * @return StatementInterface
     * @throws Exception|\Throwable
     */
    public static function dropTable(string|Table $table, ?ConnectionInterface $connection = null): StatementInterface
    {
        if ($table instanceof Table) {
            $connection = $table->getConnection();
            $table = $table->getTable();
        } else {
            $connection = $connection ?? ConnectionManager::get('default');
        }

        /** @var Connection $connection */
        return $connection->execute(sprintf('DROP TABLE `%s`;', $table));
    }

    /**
     * @param array<string, mixed> $config
     * @param ConnectionInterface|null $connection
     *
     * @return string
     * @throws Exception
     */
    protected static function resolveTableFromConfig(array $config, ?ConnectionInterface $connection = null): string
    {
        $tableNames = $tableNames2 = [];
        $connection = $connection ?? ConnectionManager::get('default');

        /** @var array{className: ?string, registryAlias: string} $config */
        $className = $config['className'] ?? static::class;

        /** @var array<string, string> $classParsed */
        $classParsed = gbgParseClassName($className);

        // create a list of possible table names

        // 1/ use registry alias if any

        if (!empty($config['registryAlias'])) {
            $registryAliasParts = explode('.', $config['registryAlias']);
            if (count($registryAliasParts) === 1) {
                $tableNames2[] = static::resolveTableName(
                    $registryAliasParts[0],
                    $classParsed['plugin'],
                    $connection
                );
            } else {
                $tableNames[] = static::resolveTableName(
                    $registryAliasParts[count($registryAliasParts) - 1],
                    $registryAliasParts[0],
                    $connection
                );
            }
        }

        // 2/ use declared class or real class name

        if ($classParsed['final'] !== 'Table') {
            $tableNames[] = static::resolveTableName(
                substr($classParsed['final'], 0, strlen($classParsed['final']) - 5),
                $classParsed['plugin'],
                $connection
            );
        }

        $tableNames = array_unique(array_merge($tableNames, $tableNames2));

        // then load all database table names
        $allTables = static::listTables($connection);


        // try to find table in database
        foreach ($tableNames as $tableName) {
            if (in_array($tableName, $allTables)) {
                return $tableName;
            }
        }

        // return the most accurate because we can create a table
        // without a Table class, and without a real MySQL table
        return $tableNames[0];
    }

    /**
     * Resolve table name from a class name
     *
     * @param string $className
     * @param string|null $prefix
     *
     * @return string|null
     */
    //    public static function resolveTableFromClassName(string $className, ?string $prefix = null): ?string
    //    {
    //        $parts = explode('\\', $className);
    //        $tablePart = end($parts);
    //        $pluginParts = [];
    //
    //        for ($i = 0; $i < count($parts); $i++) {
    //            if (in_array(strtolower($parts[$i]), ['model', 'table'])) {
    //                break;
    //            }
    //            $pluginParts[] = strtolower($parts[$i]);
    //        }
    //
    //        $tablePart = substr($tablePart, 0, strlen($tablePart) - 5);
    //
    //        return $prefix . implode('_', $pluginParts) . '_' . strtolower(Inflector::underscore($tablePart));
    //    }

    /**
     * Resolve real table name for an alias (doesn't check the table existence)
     *
     *      If `wp` is the WP prefix for MySQL tables :
     * ```
     *      TableLocator::resolveTableName('Options);               // throws an exception
     *      TableLocator::resolveTableName('Wp.Options);            // returns "wp_options";
     *      TableLocator::resolveTableName('Gbg/Core.Options);      // returns "wp_gbg_core_options";
     *      TableLocator::resolveTableName('Gbg.Options);           // returns "wp_gbg_core_options";
     * ```
     *
     * @param string|array<string, mixed> $param
     * @param string|null $plugin
     * @param ConnectionInterface|null $connection
     *
     * @return string
     * @throws Exception
     */
    public static function resolveTableName(
        string|array $param,
        ?string $plugin = '',
        ?ConnectionInterface $connection = null
    ): string {

        $connection = $connection ?? ConnectionManager::get('default');

        // if we come from a table initiator
        if (is_array($param)) {
            return static::resolveTableFromConfig($param, $connection);
        }

        // check if plugin is already in the alias
        $paramParts = explode('.', $param, 2);
        if ($plugin && count($paramParts) > 1 && strtolower($paramParts[0]) === strtolower($plugin)) {
            $plugin = null;
        }

        // check if plugin is `wp`
        if ($plugin && strtolower($plugin) === 'wp') {
            $plugin = null;
        }
        if (strtolower($paramParts[0]) === 'wp') {
            $param = $paramParts[1];
        }

        // build table name
        $table = ($connection->config()['tablePrefix'] ?? '') .
            ($plugin ? strtolower(Inflector::underscore($plugin)) . '_' : '') .
            strtolower(Inflector::underscore($param));

        return str_replace(['.', '/'], '_', $table);
    }


    /**
     * Try to resolve the table class name from the alias
     * Useful for Gbg junctions with tables that do not have model definitions
     *
     * @param string $alias
     * @param array<string, mixed> $options
     * @param string|null $type
     *
     * @return string
     */
    public static function resolveClassName(string $alias, array $options, ?string $type = 'Table'): string
    {

        /** @var array{className: string, targetTable: ?Table} $options */
        // if `className` is set
        if (!empty($options['className'])) {
            return $options['className'];
        }

        // if $alias is already a class name
        if (str_contains($alias, '\\') && class_exists($alias)) {
            return $alias;
        }

        if ($type === 'Entity') {
            $alias = Inflector::singularize($alias);
        }

        $aliasParts = explode('.', $alias, 2);
        if (count($aliasParts) === 1) {
            $aliasParts = [null, $aliasParts[0]];
        }

        [$plugin, $alias] = $aliasParts;
        $plugins = [];

        if (!$plugin) {
            /*
             * if CakePHP is building BelongsToMany associations,
             * it doesn't send us the registryAlias including the plugin name
             * so, this is a sad workaround 😭 because we are assuming that there
             * to avoid this problem, declare all relations in your Table instance :
             * all relations are :
             * "source -> through", "through" -> "target", "target" -> "through", "through" -> "source"
             */
            if (isset($options['targetTable'])) {
                if ($registryAlias = $options['targetTable']->getRegistryAlias()) {
                    $parsed = explode('.', $registryAlias);
                    if (count($parsed) > 1) {
                        $plugins[] = $parsed[0];
                    }
                }
            }

            if (empty($plugins)) {
                $plugins = [ 'Wp', 'Gbg/Cake5', 'Gbg/Core5' ];
            }
        } else {
            $plugins = [ $plugin ];
        }

        foreach ($plugins as $plugin) {
            $isWp = strtolower($plugin) === 'wp';
            $classNames = [];

            if (!$isWp) {
                $plugin = str_replace('/', '\\', $plugin);
                $classNames[] = $plugin . "\\Model\\$type\\$alias" . ($type === 'Entity' ? '' : $type);
                $classNames[] = $plugin . "\\Model\\$type\\$type";
            } else {
                //$classNames[] = "Gbg\\Cake5\\Model\\$type\\Wp\\$alias" . ($type === 'Entity' ? '' : $type);
                //$classNames[] = "Gbg\\Cake5\\Model\\$type\\Wp\\$type";
                $classNames[] = "Gbg\\Cake5\\Model\\$type\\Wp\\$alias" . ($type === 'Entity' ? '' : $type);
                $classNames[] = "Gbg\\Cake5\\Model\\$type\\Wp\\$type";
                $classNames[] = "Gbg\\Cake5\\Model\\$type\\Wp$alias" . ($type === 'Entity' ? '' : $type);
                $classNames[] = "Gbg\\Cake5\\Model\\$type\\Wp$type";
            }

            foreach ($classNames as $className) {
                if (class_exists($className)) {
                    return $className;
                }
            }
        }

        // too strong for us !!!
        return "Gbg\\Cake5\\Orm\\$type";
    }
}
