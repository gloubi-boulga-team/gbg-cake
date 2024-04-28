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

use Cake5\Core\InstanceConfigTrait;
use Cake5\Database\Schema\TableSchemaInterface;
use Cake5\Datasource\EntityInterface;
use Cake5\Datasource\QueryInterface;
use Cake5\ORM\Association\BelongsTo;
use Cake5\ORM\Association\BelongsToMany;
use Cake5\ORM\Association\HasMany;
use Cake5\ORM\Association\HasOne;
use Cake5\ORM\Query;
use Cake5\Utility\Inflector;
use Closure;
use Exception;
use Gbg\Cake5\Wrapper\Cache;
use Gbg\Cake5\Wrapper\Text;

/**
 * Table class extending CakePHP Table
 */
class Table extends \Cake5\ORM\Table
{
    use InstanceConfigTrait;

    /**
     * @var array<string, mixed>
     *
     * ### Example
     *
     * ```
     * protected $_defaultConfig = [
     *
     *      'behaviors' => [
     *          'Gbg/Cake5.Archivable',
     *          'Gbg/Cake5.Trackable'    => [ createdByProperty' => 'createdBy', ]
     *      ],
     *
     *      'types' => [
     *          // json fields are automatically encoded/decoded
     *          'json'      => 'json_field',
     *          // serialize fields are automatically serialized/deserialized
     *          'serialize' => ['serialize_field1', 'serialize_field2'],
     *      ],
     *
     *      'hasOne' => [
     *          'Blabla/Core.Adresses' => [
     *              'foreignKey'    => 'user_id',
     *              'propertyName'  => 'address'
     *          ]
     *      ],
     *
     *      'belongsTo' => [
     *          'Blabla/Core.Companies',
     *      ]
     *
     * ];
     * ```
     */
    // phpcs:disable PSR2.Classes.PropertyDeclaration.Underscore -- CakePHP Core variable
    protected array $_defaultConfig = [];
    // phpcs:enable

    /**
     * Get an array that can be used to describe the internal state of this object.
     *
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {

        return [
            'registryAlias'     => $this->_registryAlias ?? '[[ not initialized ]]',
            'table'             => $this->_table ?? '[[ not initialized ]]',
            'alias'             => $this->_alias ?? '[[ not initialized ]]',
            'entityClass'       => $this->_entityClass ?? '[[ not initialized ]]',
            'associations'      => $this->_associations->keys(),
            'behaviors'         => $this->_behaviors->loaded(),
            'defaultConnection' => static::defaultConnectionName(),
            'connectionName'    => $this->getConnection()->configName(),
        ];
    }

    /**
     * Table initialization
     *
     * @param array<string, mixed> $config
     *
     * @throws Exception
     */
    public function initialize(array $config): void
    {
        $this->setConfig($config);

        // ensure real table name
        if (!$this->_table) {
            if (!$tableName = $this->getConfig('table')) {
                $tableName = TableLocator::resolveTableName($config);
            }
            /** @var string $tableName*/
            $this->setTable($tableName);
        }

        // add behaviors
        if ($behaviors = (array)$this->getConfig('behaviors', [])) {
            $behaviors2 = [];
            foreach ($behaviors as $key => $value) {
                if (is_int($key) && is_string($value)) {
                    $behaviors2[$value] = [];
                } else {
                    $behaviors2[$key] = $value;
                }
            }
            $behaviors = $behaviors2;

            $this->setConfig('behaviors', $behaviors, false);
            foreach ($behaviors as $name => $config) {
                $this->addBehavior($name, $config);
            }
        }

        // add relations
        foreach (['belongsTo', 'belongsToMany', 'hasOne', 'hasMany'] as $type) {
            if ($relations = (array)$this->getConfig($type, [])) {
                foreach ($relations as $relationKey => $relationDef) {
                    if (is_int($relationKey)) {
                        $relationKey = $relationDef;
                        $relationDef = [];
                    }
                    $this->{$type}($relationKey, $relationDef);
                }
            }
        }
    }

    /**
     * Get field names included in primary key
     *
     * @return string[]
     * @throws Exception
     */
    public function getPrimaryKeyFields(): array
    {
        return $this->getSchema()->getPrimaryKey();
    }


    /**
     * Get field name included in primary key, ONLY if primary key is based on a unique field !
     *  If primary key is based on several fields, then returns false
     *
     * @return false|string
     * @throws Exception
     */
    public function getUniquePrimaryKeyField(): bool|string
    {
        $fields = $this->getPrimaryKeyFields();
        if (count($fields) > 1) {
            return false;
        }

        return $fields[0];
    }


    /**
     * Get field values included in primary key
     *
     * @param EntityInterface $entity
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     * @throws Exception
     */
    public function getPrimaryKeyValues(EntityInterface $entity, array $options = []): array
    {
        $return = [];
        if ($primaryKey = $this->getSchema()->getPrimaryKey()) {
            foreach ($primaryKey as $key) {
                $value = $entity[$key];
                if (!empty($options['aliasColumn'])) {
                    $key = $this->aliasField($key);
                }
                $return[$key] = $value;
            }
        }

        return $return;
    }


    /**
     * Get field value included in primary key for this entity
     *  If primary key is based on several fields, then returns null
     *
     * @param EntityInterface $entity
     * @param mixed $defaultVal
     *
     * @return mixed
     * @throws Exception
     */
    public function getUniquePrimaryKeyValue(EntityInterface $entity, mixed $defaultVal = null): mixed
    {
        $fields = $this->getPrimaryKeyFields();
        if (count($fields) > 1) {
            return $defaultVal;
        }

        if ($entity->isNew()) {
            return 0;
        }

        return $entity[$fields[0]];
    }

    /**
     * @inheritdoc
     *
     * @return TableSchemaInterface
     * @throws Exception
     */
    public function getSchema(): TableSchemaInterface
    {

        if ($this->_schema === null) {
            if ($cache = Cache::get('Gbg/Cake5.dbSchemas')) {
                /** @var array<string, TableSchemaInterface> $schemaDef */
                $schemaDef = $cache->read($this->getConnection()->configName(), []);
                if (empty($schemaDef[$this->_table])) {
                    $schemaDef[$this->_table] = parent::getSchema();
                    $cache->write($this->getConnection()->configName(), $schemaDef);
                }
                $this->_schema = $schemaDef[$this->_table];
            } else {
                $this->_schema = parent::getSchema();
            }
        }

        /** @var array<string, string|array<string>> $types */
        $types = $this->getConfig('types', []);
        foreach ($types as $type => $columns) {
            foreach ((array)$columns as $column) {
                $this->_schema->setColumnType($column, $type);
            }
        }

        return $this->_schema;
    }

    /**
     * @inheritDoc
     * @return string
     */
    public function getEntityClass(): string
    {
        if (!$this->_entityClass) {
            /** @var class-string<EntityInterface>|null $entityClass */
            $entityClass = $this->getConfig('entityClass');
            if (!$this->_entityClass = $entityClass) {
                $config = (array)$this->getConfig();
                if (!empty($config['className']) && is_string($config['className'])) {
                    $className = $config['className'];
                    $className = Text::removeTrailing($className, 'Table');
                    $className = str_replace('\\Table\\', '\\Entity\\', $className);
                    $className = Inflector::singularize($className);
                    if (class_exists($className)) {
                        /** @phpstan-ignore-next-line */
                        $this->_entityClass = $className;
                    }
                    unset($config['className']);
                }

                if (!$this->_entityClass) {
                    /** @phpstan-ignore-next-line  */
                    $this->_entityClass = TableLocator::resolveClassName(
                        $this->_registryAlias ?? '',
                        $config,
                        'Entity'
                    );
                }
            }
        }

        return $this->_entityClass;
    }

    /**
     * Equivalent of mysql_real_escape_string (according to the driver in use)
     *
     * @param mixed $value
     * @param bool $removeWrappingQuotes
     *
     * @return string
     */
    public function escapeValue(mixed $value, bool $removeWrappingQuotes = false): string
    {
        $value = $this->getConnection()->getDriver()->schemaValue($value);
        if ($removeWrappingQuotes) {
            $value = Text::removeWrapping($value, '"');
            $value = Text::removeWrapping($value, "'");
        }

        return $value;
    }


    /**
     * Get an entity by fieldName / value
     *
     * @param string[]|string $column Searchable database column (can be an array, then searches in all columns)
     * @param null|array<mixed>|string|int|float $value Search value
     * @param array<string, mixed>|null $options
     *      - Query options. Ex :
     *          . 'contain' => ['Clients' => ...]
     *
     * @return QueryInterface|null
     */
    public function findBy(
        array|string $column,
        null|array|string|int|float $value,
        ?array $options = []
    ): ?QueryInterface {
        $options += ['finder' => 'all', 'finderOptions' => []];
        $conditions = [];

        if (is_array($column) && is_array($value)) {
            $column = array_values($column);
            $value = array_values($value);
            for ($i = 0; $i < count($column); $i++) {
                $conditions[$column[$i] . ' IS'] = $value[$i];
            }
        } elseif (is_array($column)) {
            foreach ($column as $columnItem) {
                $conditions[$columnItem . ' IS'] = $value;
            }
            $conditions = ['OR' => $conditions];
        } elseif (is_array($value)) {
            $value = array_values($value);
            for ($i = 0; $i < count($value); $i++) {
                if ($value[$i] === null) {
                    $conditions[$column . ' IS'] = null;
                } else {
                    if (empty($conditions[$column . ' IN'])) {
                        $conditions[$column . ' IN'] = [];
                    }
                    $conditions[$column . ' IN'][] = $value[$i];
                }
            }
            $conditions = ['OR' => $conditions];
        } else {
            $conditions[$column] = $value;
        }

        $query = $this->find($options['finder'], $options['finderOptions'])->where($conditions);
        foreach ($options as $key => $value) {
            if (method_exists($query, $key)) {
                $query->{$key}($value);
            }
        }

        return $query;
    }

    /**
     * Just to resolve Table classname for mysql tables that doesn't have defined model
     *
     * @param string $associated
     * @param array<string, mixed> $options
     *
     * @return HasMany
     */
    public function hasMany(string $associated, array $options = []): HasMany
    {
        if (empty($options['className'])) {
            $options['className'] = TableLocator::resolveClassName($associated, $options);
        }
        return parent::hasMany($associated, $options);
    }

    /**
     * @param string $associated
     * @param array<string, mixed> $options
     *
     * @return HasOne
     */
    public function hasOne(string $associated, array $options = []): HasOne
    {
        if (empty($options['className'])) {
            $options['className'] = TableLocator::resolveClassName($associated, $options);
        }

        return parent::hasOne($associated, $options);
    }

    /**
     * @param string $associated
     * @param array<string, mixed> $options
     *
     * @return BelongsTo
     */
    public function belongsTo(string $associated, array $options = []): BelongsTo
    {
        if (empty($options['className'])) {
            $options['className'] = TableLocator::resolveClassName($associated, $options);
        }

        // force target because otherwise, CakePHP send incomplete $options to resolve table class
        return parent::belongsTo($associated, $options);
    }

    /**
     * @param string $associated
     * @param array<string, mixed> $options
     *
     * @return BelongsToMany
     */
    public function belongsToMany(string $associated, array $options = []): BelongsToMany
    {
        if (empty($options['className'])) {
            $options['className'] = TableLocator::resolveClassName($associated, $options);
        }

        return parent::belongsToMany($associated, $options);
    }

    /**
     * FindListExt (meaning findListExtended)
     *
     * Difference with Cake Core findList function is that :
     *      - when $options['valueField'] is not set, then the whole entity is returned
     *      - when $options['valueField'] is an array, then entities are bind with these fields
     *
     * If keyField is omitted, then the primaryKey is used
     * If valueField is omitted, then the whole entity is returned
     *
     * @param \Cake5\ORM\Query\SelectQuery $query The query to find with
     * @param Closure|array<string|string[]>|string|null $keyField
     * @param Closure|array<string|string[]>|string|null $valueField
     * @param Closure|array<string|string[]>|string|null $groupField
     * @param string $valueSeparator
     *
     * @return \Cake5\ORM\Query\SelectQuery The query builder
     *
     * @throws Exception
     *
     *@see \Cake5\ORM\Table::findList
     *
     */
    public function findGbgList(
        Query\SelectQuery $query,
        Closure|array|string|null $keyField = null,
        Closure|array|string|null $valueField = null,
        Closure|array|string|null $groupField = null,
        string $valueSeparator = ';'
    ): QueryInterface {

        if ($valueField === null) {
            $valueField = function ($item) {
                return $item;
            };
        }

        return $this->findList(
            $query,
            $keyField,
            $valueField,
            $groupField,
            $valueSeparator
        );
    }

    /**
     * Only for mysql
     *
     * @return string|null
     */
    public function getUpdateTime(): ?string
    {
        $connectionConfig = $this->getConnection()->config();

        try {
            /** @var array<int, bool|float|int|resource|string|null> $result */
            // try using timezone
            $result = $this->getConnection()
                ->execute(
                    'SELECT CONVERT_TZ(UPDATE_TIME, IF(@@session.time_zone = "SYSTEM", @@system_time_zone, '
                    . '@@session.time_zone), "UTC") FROM information_schema.tables'
                    . ' WHERE TABLE_SCHEMA = "' . $connectionConfig['database'] . '"  AND TABLE_NAME = "'
                    . $this->getTable() . '"'
                )->fetch();

            /** @var array<int, int|string> $result */
            if (empty($result[0])) {
                // try without timezone if timezones not installed
                $result = $this->getConnection()
                    ->execute(
                        'SELECT UPDATE_TIME FROM information_schema.tables'
                        . ' WHERE TABLE_SCHEMA = "' . $connectionConfig['database'] . '"  AND TABLE_NAME = "'
                        . $this->getTable() . '"'
                    )->fetch();
            }

            /** @var array<int, int|string> $result */
            if (!isset($result[0])) {
                return null;
            }

            return ($result[0] ? strval($result[0]) : null);
        } catch (\Throwable $ex) {
            return null;
        }
    }

    /**
     * @return int|null
     */
    public function getUpdateTimestamp(): ?int
    {
        try {
            if (
                (!$time = $this->getUpdateTime())
                || (!$time = \DateTime::createFromFormat('Y-m-d H:i:s', $time))
            ) {
                return null;
            }
            return $time->getTimestamp();
        } catch (\Throwable $ex) {
            return null;
        }
    }

    //    public function optimizeTable(string|\Cake5\ORM\Table $table): static {
    //        $this->getConnection()->execute('OPTIMIZE TABLE ' . $this->getTable() .'; ');
    //    }
}
