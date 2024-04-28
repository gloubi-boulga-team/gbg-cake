<?php

/**
 * Gloubi Boulga WP CakePHP(tm) 5 adapter
 * Copyright (c) Gloubi Boulga Team (https://github.com/gloubi-boulga-team).
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright 2024 - now | Gloubi Boulga Team (https://github.com/gloubi-boulga-team)
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 *
 * @see      https://github.com/gloubi-boulga-team
 * @since     5.0
 */

declare(strict_types=1);

namespace Gbg\Cake5\Orm;

use Cake5\Database\Driver\Mysql;
use Cake5\Datasource\ConnectionManager;
use Cake5\Datasource\EntityInterface;
use Cake5\Datasource\Exception\MissingDatasourceConfigException;
use Cake5\ORM\TableRegistry;
use Cake5\Utility\Text;
use Gbg\Cake5\Model\Behavior\ArchivableBehavior;
use Gbg\Cake5\Model\Table\Wp\UsersTable;
use Gbg\Cake5\Orm\Database\Connection;
use Gbg\Cake5\Orm\Database\Log\QueryLogger;
use Gbg\Cake5\Orm\Database\Type\IpType;
use Gbg\Cake5\Orm\Database\Type\SerializeType;
use Gbg\Cake5\TestCase;

class OrmTest extends TestCase
{
    use TableLocatorTrait;

    /**
     * @inheritDoc
     *
     * @throws \Exception|\Throwable
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        static::includeFiles();
        static::createTables();
    }

    /**
     * @inheritDoc
     *
     * @throws \Exception
     */
    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        static::dropTables();
    }

    /**
     * Test TableLocator.
     *
     * @test TableLocator
     */
    public function testTableLocator(): void
    {
        $locator = TableRegistry::getTableLocator();

        // test `resolveRealTableName` function ------------------------------------------------------------------------

        $tests = [
            'Options'                  => [
                'table'         => 'wp_options',
                'alias'         => 'Options',
                'registryAlias' => 'Options',
                'tableClass'    => 'Gbg\Cake5\Model\Table\Wp\OptionsTable',
                'entityClass'   => 'Gbg\Cake5\Model\Entity\Wp\Option',
            ],
            'Wp.Options'               => [
                'table'         => 'wp_options',
                'alias'         => 'Options',
                'registryAlias' => 'Wp.Options',
                'tableClass'    => 'Gbg\\Cake5\\Model\\Table\\Wp\\OptionsTable',
                'entityClass'   => 'Gbg\\Cake5\\Model\\Entity\\Wp\\Option',
            ],
            'Gbg/Cake5.WpOptions'      => [
                'table'         => gbgGetWpdb()->prefix . 'options',
                'alias'         => 'WpOptions',
                'registryAlias' => 'Gbg/Cake5.WpOptions',
                'tableClass'    => 'Gbg\\Cake5\\Model\\Table\\WpOptionsTable',
                'entityClass'   => 'Gbg\\Cake5\\Model\\Entity\\WpOption',
            ],
            'Gbg/Cake5.TestOrmAuthors' => [
                'table'         => gbgGetWpdb()->prefix . 'gbg_cake5_test_orm_authors',
                'alias'         => 'TestOrmAuthors',
                'registryAlias' => 'Gbg/Cake5.TestOrmAuthors',
                'tableClass'    => 'Gbg\\Cake5\\Model\\Table\\TestOrmAuthorsTable',
                'entityClass'   => 'Gbg\\Cake5\\Model\\Entity\\TestOrmAuthor',
            ],
            'XXX.YYY'                  => [
                'table'         => gbgGetWpdb()->prefix . 'x_x_x_y_y_y',
                'alias'         => 'YYY',
                'registryAlias' => 'XXX.YYY',
                'tableClass'    => 'Gbg\Cake5\Orm\Table',
                'entityClass'   => 'Gbg\Cake5\Orm\Entity',
            ],
            'Wp.Users'                 => [
                'table'         => 'wp_users',
                'alias'         => 'Users',
                'registryAlias' => 'Wp.Users',
                'tableClass'    => 'Gbg\Cake5\Model\Table\Wp\UsersTable',
                'entityClass'   => 'Gbg\Cake5\Model\Entity\Wp\User',
            ],
        ];

        foreach ($tests as $alias => $expected) {
            try {
                $actual = $locator->get($alias);
            } catch (\Exception $ex) {
                $this->assertEquals($expected, '~error');

                break;
            }

            $this->assertEquals($expected['table'], $actual->getTable());
            $this->assertEquals($expected['alias'], $actual->getAlias());

            $this->assertEquals(
                $expected['registryAlias'],
                $actual->getRegistryAlias(),
                'Err for ' . $alias
            );

            $this->assertEquals(
                $expected['tableClass'],
                $actual::class,
                'Err for ' . $alias
            );

            $this->assertEquals(
                $expected['entityClass'],
                $actual->getEntityClass(),
                'Err for ' . $alias
            );
        }

        // test `listTables` function ----------------------------------------------------------------------------------

        $tables = TableLocator::listTables(ConnectionManager::get('default'), true);
        $this->assertTrue(in_array('wp_gbg_cake5_test_orm_authors', $tables, true));
        $this->assertTrue(in_array('wp_options', $tables, true));
        $this->assertFalse(in_array('wp_should_not_do_that!!!', $tables, true));

        $tables = TableLocator::listTables('default', true);
        $this->assertTrue(in_array('wp_gbg_cake5_test_orm_authors', $tables, true));
        $this->assertTrue(in_array('wp_options', $tables, true));
        $this->assertFalse(in_array('wp_should_not_do_that!!!', $tables, true));

        TableLocator::copyTable('wp_gbg_cake5_test_orm_authors', 'wp_gbg_cake5_test_orm_authors2');
        $tables = TableLocator::listTables('default', true);
        $this->assertTrue(in_array('wp_gbg_cake5_test_orm_authors2', $tables, true));

        TableLocator::dropTable('wp_gbg_cake5_test_orm_authors2');
        $tables = TableLocator::listTables('default', true);
        $this->assertFalse(in_array('wp_gbg_cake5_test_orm_authors2', $tables, true));

        $table = TableRegistry::getTableLocator()->get('Gbg/Cake5.TestOrmAuthors');
        TableLocator::copyTable($table, 'wp_gbg_cake5_test_orm_authors2');
        $tables = TableLocator::listTables('default', true);
        $this->assertTrue(in_array('wp_gbg_cake5_test_orm_authors2', $tables, true));

        $table = TableRegistry::getTableLocator()->get('Gbg/Cake5.TestOrmAuthors2');
        TableLocator::dropTable($table);
        $tables = TableLocator::listTables('default', true);
        $this->assertFalse(in_array('wp_gbg_cake5_test_orm_authors2', $tables, true));

        $connection = ConnectionManager::get('default');
        $name = TableLocator::resolveTableName('Users', 'Wp', $connection);
        $this->assertSame('wp_users', $name);

        $name = TableLocator::resolveTableName('Wp.Users', 'Wp', $connection);
        $this->assertSame('wp_users', $name);

        $class = TableLocator::resolveClassName(UsersTable::class, []);
        $this->assertSame(UsersTable::class, $class);
    }

    /**
     * Test based on `wp_options` table.
     */
    public function testWpOptions(): void
    {
        $optionName = 'Gbg/Cake/tests/key_' . Text::uuid();
        $optionValue = new \DateTime();

        $table = TableRegistry::getTableLocator()->get('Wp.Options');

        // test deleting an old orphan row
        $table->deleteAll(['Options.option_name LIKE' => '%Gbg/Cake/tests/key_%']);

        // test find a non-existing option
        $options = $table->find()->where(['Options.option_name' => $optionName])->first();
        $this->assertEquals(true, empty($options), 'Non-existing exists');

        // test insert a new option
        $newOption = $table->newEntity([
            'option_name' => $optionName,
            'option_value' => $optionValue->format('Y-m-d H:i:s'),
            'auto_load' => 'no',
        ]);
        $result = $table->save($newOption);
        $this->assertEquals(false, empty($result), 'Saving new entity');

        // test deleting new option
        $result = $table->delete($newOption);
        $this->assertEquals(false, empty($result), 'Deleting new entity');

        // test find a non-existing option
        $options = $table->find()->where(['Options.option_name' => $optionName])->first();
        $this->assertEquals(true, empty($options), 'Non-existing deleted exists');
    }

    /**
     * Test based on `Gbg/Cake5.TestOrmAuthors` table.
     */
    public function testAuthorFind(): void
    {
        // test simple find --------------------------------------------------------------------------------------------
        $table = TableRegistry::getTableLocator()->get('Gbg/Cake5.TestOrmAuthors');
        $count = $table->find()->where(['TestOrmAuthors.id >' => 10])->count();

        $this->assertEquals(32, $count, 'Author count');
        $authors = $table->find()->where(['TestOrmAuthors.id >' => 10])
            ->groupBy('TestOrmAuthors.id')
            ->orderBy(['TestOrmAuthors.id' => 'desc'])->limit(5)->toArray()
        ;

        $this->assertEquals(49, $authors[0]->id, 'Author id');
        $this->assertStringContainsString(
            'TestOrmAuthorsTable:beforeSave',
            $authors[0]->details
        );

        $this->assertEquals(true, is_array($authors[0]->serialize_col));
        $this->assertEquals(true, is_string($authors[0]->ip_col));
        $this->assertEquals(true, is_array($authors[0]->json_col));

        // test advanced find ------------------------------------------------------------------------------------------
        $authors = $table->find()->where(['TestOrmAuthors.id >' => 10])
            ->contain(['TestOrmAuthorTypes', 'TestOrmAuthorPosts', 'TestOrmPosts',
                'TestOrmAuthorMetas', 'TestOrmAuthorMetasAlias'])
            ->limit(5)->toArray();

        $this->assertEquals(
            false,
            empty($authors[0]->test_orm_author_type)
        );
        $this->assertEquals(
            true,
            $authors[0]->test_orm_author_type instanceof EntityInterface
        );
        $this->assertEquals(false, empty($authors[0]->test_orm_author_posts));
        $this->assertEquals(true, is_array($authors[0]->test_orm_author_posts));
        $this->assertEquals(false, empty($authors[0]->test_orm_posts));
        $this->assertEquals(true, is_array($authors[0]->test_orm_posts));
        $this->assertEquals(false, empty($authors[0]->test_orm_author_meta));

        $this->assertEquals(
            true,
            $authors[0]->test_orm_author_meta instanceof EntityInterface
        );
        $this->assertEquals(
            false,
            empty($authors[0]->test_orm_author_metas_alias)
        );
        $this->assertEquals(
            true,
            $authors[0]->test_orm_author_metas_alias instanceof EntityInterface
        );

        $this->assertEquals(false, empty($authors[0]->virtual_property));
        $authors[0]->virtual_property = 'xyz';
        $this->assertEquals('xyz', $authors[0]->virtual_property);
    }

    /**
     * Test based on `Gbg/Cake5.TestOrmAuthors` table.
     */
    public function testUpdateDelete(): void
    {
        $authorsTable = TableRegistry::getTableLocator()->get('Gbg/Cake5.TestOrmAuthors');
        $authorMetasTable = TableRegistry::getTableLocator()->get('Gbg/Cake5.TestOrmAuthorMetas');
        $authorPostsTable = TableRegistry::getTableLocator()->get('Gbg/Cake5.TestOrmAuthorPosts');
        $postsTable = TableRegistry::getTableLocator()->get('Gbg/Cake5.TestOrmAuthorPosts');

        // test simple delete ------------------------------------------------------------------------------------------
        $authorCount = $authorsTable->find()->where(['TestOrmAuthors.id >' => 10])->count();
        $this->assertEquals(32, $authorCount, 'Author count');
        $entity = $authorsTable->find()->where(['TestOrmAuthors.id >' => 10])->first();
        // @phpstan-ignore-next-line
        $authorsTable->delete($entity);

        $authorCount = $authorsTable->find()->where(['TestOrmAuthors.id >' => 10])->count();
        $this->assertEquals(31, $authorCount);

        // test dependent delete with archivable behavior --------------------------------------------------------------
        $authorCountBefore = $authorsTable->find()->count();
        $metasCountBefore = $authorMetasTable->find()->count();
        $entity = $authorsTable->find()->contain(['TestOrmAuthorMetas'])->first();

        // @phpstan-ignore-next-line
        $authorsTable->delete($entity, ['associated' => 'TestOrmAuthorMetas']);
        $authorCountAfter = $authorsTable->find()->count();
        $metasCountAfter = $authorMetasTable->find()->count();

        $this->assertEquals($authorCountBefore - 1, $authorCountAfter);
        $this->assertEquals($metasCountBefore, $metasCountAfter);

        // test dependent delete without archivable behavior -----------------------------------------------------------
        $authorsTable->removeBehavior('Archivable');
        $authorCountBefore = $authorsTable->find()->count();
        $authorPostsCountBefore = $authorPostsTable->find()->count();
        $entity = $authorsTable->find()->contain(['TestOrmAuthorPosts'])->first();

        /** @phpstan-ignore-next-line */
        $postCount = count($entity->test_orm_author_posts);

        // @phpstan-ignore-next-line
        $authorsTable->delete($entity, ['associated' => 'TestOrmAuthorPosts']);
        $authorCountAfter = $authorsTable->find()->count();
        $authorPostsCountAfter = $authorPostsTable->find()->count();
        $this->assertEquals($authorCountBefore - 1, $authorCountAfter);
        $this->assertEquals($authorPostsCountBefore - $postCount, $authorPostsCountAfter);
    }

    /**
     * Test Cake validators
     *
     * @return void
     */
    public function testCakeValidators(): void
    {
        $postsTable = TableRegistry::getTableLocator()->get('Gbg/Cake5.TestOrmPosts');

        $post = $postsTable->newEntity([
            'meta_id' => 7,
            'secret'  => '💥123456🙃',
        ]);
        $result = $postsTable->save($post);
        $this->assertEquals(false, $result);
        $this->assertEquals(
            'Secret must be ascii',
            $post->getError('secret')['ascii']
        );

        $post = $postsTable->newEntity([
            'meta_id' => 7,
            'name' => 'string-with-251-chars-string-with-251-chars-string-with-251-chars-string-with-251-chars-string-with-251-chars-string-with-251-chars-string-with-251-chars-string-with-251-chars-string-with-251-chars-string-with-251-chars-string-with-251-chars-string-wi', // phpcs:ignore Generic.Files.LineLength
        ]);
        $result = $postsTable->save($post);
        $this->assertEquals(false, $result, 'testCakeValidators #3');
        $this->assertEquals(
            'Name must be between 1 and 250 characters',
            $post->getError('name')['lengthBetween']
        );

        $post = $postsTable->newEntity([
            'meta_id' => 7,
            'name'    => 'x',
            'status'  => 4
        ]);
        $result = $postsTable->save($post);

        $this->assertEquals(false, $result);
        $this->assertEquals(
            'Status must be a non negative integer between 0 and 2',
            $post->getError('status')['lessThanOrEqual']
        );

        $post = $postsTable->newEntity([
            'meta_id' => 7,
            'name'    => 'x',
            'status'  => -1
        ]);
        $result = $postsTable->save($post);

        $this->assertEquals(false, $result);
        $this->assertEquals(
            'Status must be a non negative integer between 0 and 2',
            $post->getError('status')['nonNegativeInteger']
        );
    }

    /**
     * Create tables for testing.
     *
     * @throws \Exception|\Throwable
     */
    protected static function createTables(): void
    {
        static::createTable('Gbg/Cake5.TestOrmAuthors', [
            'json_col' => 'VARCHAR(255) NULL',
            'serialize_col' => 'VARCHAR(255) NULL',
            'ip_col' => 'BINARY(16) NULL',
            'meta_id' => 'INT(11) UNSIGNED NULL COMMENT \'{"null-if":"id-in:4,8,12", "value":"id"}\'',
            'type_id' => 'INT(11) UNSIGNED NULL COMMENT \'{"null-if":"id-in:4,8,12", "value":"id"}\'',
            'tag_id' => 'INT(11) UNSIGNED NULL COMMENT \'{"null-if":"id%6","value":"id/3"}\'',
            'main_page_id' => 'INT(11) UNSIGNED NULL COMMENT \'{"null-if":"id-in:8,15", "value":"id/2"}\'',
        ]);
        static::fillTable('Gbg/Cake5.TestOrmAuthors', ['rows' => 50]);

        static::createTable('Gbg/Cake5.TestOrmPosts', [
            'meta_id' => 'INT(11) UNSIGNED NULL COMMENT \'{"null-if":"id-in:7,14", "value":"id"}\'',
        ]);
        static::fillTable('Gbg/Cake5.TestOrmPosts', ['rows' => 50]);

        static::createTable('Gbg/Cake5.TestOrmAuthorPosts', [
            'author_id' => 'INT(11) UNSIGNED NULL COMMENT \'{"null-if":"id-in:2,7", "value":"id/3"}\'',
            'post_id' => 'INT(11) UNSIGNED NOT NULL COMMENT \'{"value":"id/2"}\'',
            'archived_at' => 'datetime DEFAULT NULL',
        ]);
        static::fillTable('Gbg/Cake5.TestOrmAuthorPosts', ['rows' => 50]);

        static::createTable('Gbg/Cake5.TestOrmAuthorTypes');
        static::fillTable('Gbg/Cake5.TestOrmAuthorTypes', ['rows' => 50]);

        static::createTable('Gbg/Cake5.TestOrmAuthorMetas');
        static::fillTable('Gbg/Cake5.TestOrmAuthorMetas', ['rows' => 50]);
    }

    /**
     * Create test table.
     *
     * @param array<string, string> $extraFields
     * @param array<string>         $primary
     *
     * @throws \Throwable
     */
    protected static function createTable(string $tableAlias, array $extraFields = [], array $primary = ['id']): bool
    {
        /**
         * @var Connection $connection
         */
        $connection = ConnectionManager::get('default');
        $tables = TableLocator::listTables($connection, true);
        $tableRealName = TableLocator::resolveTableName($tableAlias, 'Gbg/Cake5', $connection);
        if (in_array($tableRealName, $tables, true)) {
            TableLocator::dropTable($tableRealName, $connection);
        }

        $commonFields = [
            'id' => 'INT(11) UNSIGNED NOT NULL AUTO_INCREMENT',
            'name' => 'VARCHAR(255) NOT NULL',
            'description' => 'LONGTEXT NULL',
            'status' => 'TINYINT(4) UNSIGNED NOT NULL DEFAULT 0',
            'secret' => 'VARCHAR(255) NULL DEFAULT NULL',
            'created_at' => 'datetime DEFAULT NULL',
            'modified_at' => 'datetime DEFAULT NULL',
            'modified_by' => 'INT(11) UNSIGNED NULL DEFAULT NULL',
            'deleted_at' => 'datetime DEFAULT NULL',
            'deleted_by' => 'INT(11) UNSIGNED NULL DEFAULT NULL',
            'details' => 'LONGTEXT DEFAULT NULL',
        ];
        $fields = $commonFields + $extraFields;
        foreach ($fields as $key => $value) {
            $fields[$key] = QueryTools::quoteField($key) . ' ' . $value;
        }
        foreach ($primary as $key => $value) {
            $primary[$key] = QueryTools::quoteField($value);
        }

        $sql = sprintf(
            'CREATE TABLE %s ( %s, PRIMARY KEY (%s) ) ' .
            'ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;',
            $tableRealName,
            join(', ', array_values($fields)),
            join(', ', array_values($primary))
        );
        $connection->execute($sql, [], []);

        try {
            $connection->getCacher()->clear();
        } catch (\Throwable $ex) {
        }

        // refresh tables
        $tables = TableLocator::listTables($connection, true);

        return true;
    }

    /**
     * Fill test tables.
     *
     * @param array<string, mixed> $params
     */
    protected static function fillTable(string $name, array $params): void
    {
        $table = TableRegistry::getTableLocator()->get($name);
        $columns = $table->getSchema()->columns();
        $entities = ['added' => [], 'deleted' => []];

        for ($i = 1; $i <= $params['rows']; ++$i) {
            $entityData = [];
            foreach ($columns as $columnName) {
                /** @var array{autoIncrement: mixed, type: string, comment: string} $column */
                $column = $table->getSchema()->getColumn($columnName);
                if (!empty($column['autoIncrement'])) {
                    $entityData[$columnName] = 0;
                } elseif ('ip_col' === $columnName) {
                    $entityData[$columnName] = ($i % 2 ? '192.168.0.175' : '0000:0000:0000:0000:0000:ffff:c0a8:00af');
                } elseif ('json_col' === $columnName) {
                    $entityData[$columnName] = ['x' => 'y', 'y' => new \DateTime()];
                } elseif ('serialize_col' === $columnName) {
                    $entityData[$columnName] = ['x' => 'y', 'y' => new \DateTime()];
                } elseif (in_array($column['type'], ['text', 'varchar', 'string'])) {
                    $entityData[$columnName] = str_replace('gbg_core_test_orm_', '', $name)
                        . ' ' . $columnName . ' ' . $i;
                } elseif ('status' === $columnName) {
                    $entityData[$columnName] = ($i % 2 ? 1 : 0);
                } elseif ('deleted_at' === $columnName || 'archived_at' === $columnName) {
                    // $entity_data[$column->name] = ($i % 5 == 0 ? '2000-01-01 01:01:00' : null);
                } elseif ('integer' === $column['type'] && str_starts_with($column['comment'], '{')) {
                    /** @var string[] $comment */
                    $comment = json_decode($column['comment'], true);
                    $entityData[$columnName] = static::computeVal($i, $comment);
                }
            }

            $entity = $table->newEntity($entityData);
            $entities['added'][] = $table->save($entity);
        }

        // test deleteRecords
        for ($i = 1; $i <= $params['rows']; ++$i) {
            if (0 == $i % 5) {
                $entity = $table->find(type: 'all', ignoreCallbacks: true)
                    ->where(['id' => $i])
                    ->first()
                ;
                if ($entity instanceof EntityInterface) {
                    $entities['deleted'][] = $table->delete($entity);
                }
            }
        }
    }

    /**
     * @param array<string> $args
     */
    protected static function computeVal(int $id, array $args): ?int
    {
        // null process
        if (!empty($args['null-if'])) {
            $args['null-if'] = str_replace(' ', '', $args['null-if']);
            if (str_starts_with($args['null-if'], 'id-in:')) {
                $param = substr($args['null-if'], strlen('id-in:'));
                $param = explode(',', $param);
                if (in_array($id, $param)) {
                    return null;
                }
            } elseif (str_starts_with($args['null-if'], 'id%')) {
                $param = substr($args['null-if'], strlen('id%'));
                if (0 == $id % intval($param)) {
                    return null;
                }
            }
        }

        // id process
        if (!empty($args['value'])) {
            $args['value'] = str_replace(' ', '', $args['value']);
            if (str_starts_with($args['value'], 'id/')) {
                $param = substr($args['value'], strlen('id/'));

                return intval(ceil($id / intval($param)));
            }
            if (str_starts_with($args['value'], 'id%')) {
                $param = substr($args['value'], strlen('id/'));

                return intval(ceil($id % intval($param)));
            }
            if (str_starts_with($args['value'], 'id*')) {
                $param = substr($args['value'], strlen('id*'));

                return intval(floor($id * intval($param)));
            }
        }

        return intval($id);
    }

    /**
     * Remove test tables
     *
     * @throws \Exception
     */
    protected static function dropTables(): void
    {
        foreach (
            [
                'Gbg/Cake5.TestOrmAuthors',
                'Gbg/Cake5.TestOrmPosts',
                'Gbg/Cake5.TestOrmAuthorPosts',
                'Gbg/Cake5.TestOrmAuthorMetas',
                'Gbg/Cake5.TestOrmAuthorTypes',
            ] as $table
        ) {
            TableLocator::dropTable(TableLocator::resolveTableName($table));
        }
    }

    /**
     * Include test php files.
     */
    protected static function includeFiles(): void
    {
        foreach ([__DIR__ . '/Model/Table/*Table.php', __DIR__ . '/Model/Entity/*.php'] as $pattern) {
            $pattern = str_replace('/', DIRECTORY_SEPARATOR, $pattern);
            $files = glob($pattern);
            if (false !== $files) {
                foreach ($files as $file) {
                    include_once $file;
                }
            }
        }
    }

    /**
     * Test multi databases connections
     * Can only work if you have a MySql/MariaDB 3306 and 3307 running with same credentials and same database name
     * If default connection is 3306, then the new connection will use 3307 (and vice-versa)
     */
    public function testMultipleDbConnections(): void
    {
        $configured = ConnectionManager::configured();
        if (!in_array('default', $configured)) {
            return;
        }

        /** @var array<string> $config */
        $config = ConnectionManager::getConfig('default');
        $config['port'] = ($config['port'] === '3307' ? '3306' : '3307');
        $connection = @fsockopen($config['host'], intval($config['port']));
        if (!is_resource($connection)) {
            $this->markTestSkipped('No connection to ' . $config['host'] . ':' . $config['port']);
        }

        ConnectionManager::setConfig('connection2', $config);

        /** @var Connection $connection1 */
        $connection1 = ConnectionManager::get('default');
        /** @var Connection $connection2 */
        $connection2 = ConnectionManager::get('connection2');

        $table1 = TableRegistry::getTableLocator()->get('Wp.Options');
        $table2 = (clone $table1)->setConnection($connection2);

        /** @var array<string> $siteUrl1 */
        $siteUrl1 = $table1->find()->where(['Options.option_name' => 'siteurl'])->first();
        /** @var array<string> $siteUrl2 */
        $siteUrl2 = $table2->find()->where(['Options.option_name' => 'siteurl'])->first();

        $this->assertNotEquals(
            $siteUrl1['option_value'],
            $siteUrl2['option_value']
        );

        $oldCount1 = $table1->find()->count();
        $oldCount2 = $table2->find()->count();

        $new = $table2->newEntity([
            'option_name'  => 'gbg-cake5-test-options',
            'option_value' => 'optionion',
            'auto_load'    => 'no',
        ]);
        $table2->save($new);

        $newCount1 = $table1->find()->count();
        $newCount2 = $table2->find()->count();

        $this->assertEquals($oldCount1, $newCount1);
        $this->assertEquals($oldCount2 + 1, $newCount2);

        $table2->delete($new);

        $newCount1 = $table1->find()->count();
        $newCount2 = $table2->find()->count();

        $this->assertEquals($oldCount1, $newCount1);
        $this->assertEquals($oldCount2, $newCount2);
    }

    /**
     * Test TrackableBehavior
     *
     * @return void
     */
    public function testTrackableBehavior(): void
    {
        // find a user to test

        /** @var array{ID:string} $user Not really an array but phpstan requires it */
        $user = TableRegistry::getTableLocator()->get('Wp.Users')->find()->first();

        $table = TableRegistry::getTableLocator()->get('Gbg/Cake5.TestOrmAuthors');

        $newEntity = $table->newEntity([
            'name' => 'Test',
            'description' => 'Test',
            'status' => 1,
            'modified_by' => $user['ID']
        ]);

        $table->save($newEntity);

        $entity = $table->find()->where(['TestOrmAuthors.id' => $newEntity['id']])
            ->contain('ModifiedBy')->first();

        /** @phpstan-ignore-next-line - because not compatible with Entity or ArrayAccess */
        $this->assertEquals(false, empty($entity['modifiedBy']));
        /** @phpstan-ignore-next-line - because not compatible with Entity or ArrayAccess */
        $this->assertEquals($user['ID'], $entity['modifiedBy']['ID']);

        // try it  without "contain"
        $entity = $table->find()->where(['TestOrmAuthors.id' => $newEntity['id']])
            ->first();

        /** @phpstan-ignore-next-line - because not compatible with Entity or ArrayAccess */
        $this->assertEquals(true, empty($entity['modifiedBy']));
    }

    /**
     * Test ArchivableBehavior
     *
     * @return void
     */
    public function testArchivableBehavior(): void
    {
        // find a user to test
        /** @var array{ID:string} $user Not really an array but phpstan requires it */
        $user = TableRegistry::getTableLocator()->get('Wp.Users')->find()->first();

        // find a user to test
        $table = TableRegistry::getTableLocator()->get('Gbg/Cake5.TestOrmAuthors');

        // add behavior because it may have been remove in an earlier test
        if (!$table->hasBehavior('Archivable')) {
            $table->addBehavior('Gbg/Cake5.Archivable', [
                'archivedAt' => ['field' => 'deleted_at'],
                'archivedBy' => ['field' => 'deleted_by']
            ]);
        }

        // first test beforefind
        $entity = $table->find()
            ->where(['TestOrmAuthors.deleted_at IS NOT' => null])
            ->first();

        $this->assertEquals(null, $entity);

        $entity = $table->find(type: 'all', ignoreCallbacks: true)
            ->where(['TestOrmAuthors.deleted_at IS NOT' => null])
            ->first();

        $this->assertNotEmpty($entity);

        $entity = $table->find(type: 'all', ignoreCallbacks: [ArchivableBehavior::class])
            ->where(['TestOrmAuthors.deleted_at IS NOT' => null])
            ->first();

        $this->assertNotEmpty($entity);

        $entity = $table->find(type: 'all', ignoreCallbacks: ['ArchivableBehavior'])
            ->where(['TestOrmAuthors.deleted_at IS NOT' => null])
            ->first();

        $this->assertNotEmpty($entity);

        // try to exhume
        $table->exhume($entity); //@phpstan-ignore-line

        $entity = $table->find(type: 'all', ignoreCallbacks: ['ArchivableBehavior'])
            ->where(['TestOrmAuthors.id' => $entity['id']]) //@phpstan-ignore-line
            ->first();
        $this->assertEmpty($entity['deleted_at']); //@phpstan-ignore-line

        // bury for next processes
        $table->delete($entity);

        // deleted_by has not been set before
        /** @phpstan-ignore-next-line - because not compatible with Entity or ArrayAccess */
        $entity['deleted_by'] = $user['ID'];
        /** @phpstan-ignore-next-line - because not compatible with Entity or ArrayAccess */
        $table->save($entity);

        /** @phpstan-ignore-next-line - because not compatible with Entity or ArrayAccess */
        $entityId = $entity['id'];

        // retest beforeFind
        $entity = $table->find()->where(['TestOrmAuthors.id' => $entityId])
            ->contain('ArchivedBy')->first();

        $this->assertEquals(null, $entity);

        $entity = $table->find(type: 'all', ignoreCallbacks: ['ArchivableBehavior'])
            ->contain('ArchivedBy')
            ->where(['TestOrmAuthors.id' => $entityId])
            ->first();

        /** @phpstan-ignore-next-line */
        $this->assertEquals($user['ID'], $entity['archivedBy']['ID']);
    }

    /**
     * @return void
     */
    public function testEntity(): void
    {
        $table = TableRegistry::getTableLocator()->get('Gbg/Cake5.TestOrmAuthors');
        $entity = $table->find()->where(['id' => 41])->first();

        $this->assertSame($table, $entity->getTable());

        $this->assertSame(true, $entity->hasField('id'));
        $this->assertSame(true, $entity->hasField('created_at'));
        $this->assertSame(false, $entity->hasField(''));
        $this->assertSame(false, $entity->hasField('xyz'));

        $this->assertSame([41], $entity->getPrimaryValue());
        $this->assertSame(false, $entity->oneIsDirty(['id', 'created_at']));

        $entity['created_at'] = '2020-01-01 00:00:00';
        $this->assertSame(true, $entity->oneIsDirty(['id', 'created_at']));
        $entity->saveMe();
        $entity = $table->find()->where(['id' => 41])->first();
        $this->assertSame('2020-01-01 00:00:00', $entity['created_at']->format('Y-m-d H:i:s'));

        $entity = new Entity();
        $table = $entity->getTable();
        $this->assertSame('Entities', $table->getAlias());
    }

    public function testTableLocatorTrait(): void
    {
        try {
            $table = $this->getTable('Wp.Users');
            $this->assertSame('Wp.Users', $table->getRegistryAlias());
        } catch (\Exception $ex) {
            $this->assertEquals('Wp.Users', '~error');
        }

        try {
            $table = $this->getTable('Users');
            $this->assertSame('Users', $table->getRegistryAlias());
        } catch (\Exception $ex) {
            $this->assertEquals('Users', '~error');
        }
    }

    public function testTables(): void
    {
        $table = TableRegistry::getTableLocator()->get('Wp.Posts');
        $entity = $table->find()->first();

        $debugInfo = print_r($table, true);

        $this->assertStringContainsString('[registryAlias] => Wp.Posts', $debugInfo);
        $this->assertStringContainsString('[table] => wp_posts', $debugInfo);

        // @todo test combined PK
        $value = $table->getPrimaryKey();
        $this->assertSame('ID', $value);

        $value = $table->getPrimaryKeyFields();
        $this->assertSame(['ID'], $value);

        $value = $table->getUniquePrimaryKeyField();
        $this->assertSame('ID', $value);

        $value = $table->getPrimaryKeyValues($entity);
        $this->assertSame(['ID' => $entity['ID']], $value);

        $value = $table->getPrimaryKeyValues($entity, ['aliasColumn' => true]);
        $this->assertSame(['Posts.ID' => $entity['ID']], $value);

        $value = $table->getUniquePrimaryKeyValue($entity);
        $this->assertSame($entity['ID'], $value);

        $newEntity = $table->newEntity(['name' => 'x']);
        $value = $table->getUniquePrimaryKeyValue($newEntity);
        $this->assertSame(0, $value);

        $this->assertSame("'\' OR 1=1 /*'", $table->escapeValue("' OR 1=1 /*", false));
        $this->assertSame("\' OR 1=1 /*", $table->escapeValue("' OR 1=1 /*", true));

        $table = TableRegistry::getTableLocator()->get('Gbg/Cake5.TestOrmAuthors');
        $firstAuthor = $table->find()->first();

        $authors = $table->findBy('id', [$firstAuthor['id']])->toArray();
        $this->assertSame(1, count($authors));

        $authors = $table->findBy(['id'], [$firstAuthor['id']])->toArray();
        $this->assertSame(1, count($authors));
        $this->assertSame($firstAuthor['id'], $authors[0]['id']);

        $authors = $table->findBy(['id'], $firstAuthor['id'])->toArray();
        $this->assertSame(1, count($authors));
        $this->assertSame($firstAuthor['id'], $authors[0]['id']);

        $authors = $table->findBy('id', [null])->toArray();
        $this->assertSame(0, count($authors));

        $authors = $table->findBy('id', $firstAuthor['id'])->toArray();
        $this->assertSame(1, count($authors));

        $authors = $table->findBy('id', $firstAuthor['id'], ['orderBy' => ['id']])->toArray();
        $this->assertSame(1, count($authors));

//        $authors = $table->find(type: 'gbgList', keyField: 'id')
//            ->contain(['TestOrmAuthorPosts' => function (SelectQuery $q) {
//                return $q->find('gbgList', ['keyField' => 'id'])
//                    ->select(['TestOrmAuthorPosts.id', 'TestOrmAuthorPosts.name', 'TestOrmAuthorPosts.author_id']);
//            }])->limit(5)->toArray();
//
//        print_r($authors); exit;

        $authors = $table->find(type: 'gbgList', keyField: 'id')->limit(5)->toArray();

        $this->assertSame(5, count($authors));
        $this->assertSame(array_keys($authors)[0], $authors[array_keys($authors)[0]]['id']);


        $authors = $table
            ->find(type: 'gbgList', keyField: 'id', valueField: 'name')
            ->limit(5)->toArray();
        $this->assertSame(5, count($authors));
        $this->assertTrue(is_string($authors[array_keys($authors)[0]]));

        $authors = $table
            ->find(type: 'gbgList', keyField: 'id', valueField: ['id', 'name'])
            ->limit(5)->toArray();

        $this->assertSame(5, count($authors));
        $this->assertTrue(is_string($authors[array_keys($authors)[0]]));

        $authors = $table
            ->find(type: 'gbgList', keyField: 'id', valueField: ['id', 'name'], valueSeparator: '::')
            ->limit(5)->toArray();
        $this->assertSame(5, count($authors));
        $this->assertTrue(is_string($authors[array_keys($authors)[0]]));
        $this->assertTrue(str_contains($authors[array_keys($authors)[0]], '::'));


        // test last modif time
        $this->assertNotEmpty($table->getUpdateTime());
        $this->assertNotEmpty($table->getUpdateTimestamp());

        // test last modif time exception
        $this->assertNotEmpty($table->getUpdateTime());
        $this->assertNotEmpty($table->getUpdateTimestamp());
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testQueryTools(): void
    {
        $table = TableRegistry::getTableLocator()->get('Gbg/Cake5.TestOrmAuthors');

        $authors = $table->find()->where(['id' => 50]);
        $sql = QueryTools::getQueryCompiledSql($authors);
        $this->assertStringContainsString(
            'SELECT TestOrmAuthors.id AS TestOrmAuthors__id, TestOrmAuthors.name',
            $sql
        );

        $authors = $table->find()->where([
            'serialize_col' => ['xyz'],
            'json_col' => ['xyz'],
            'name' => 'xyz',
            'ip_col' => '192.168.1.1',
            'created_at' => new \DateTime(),
            'status' => true
        ]);

        $sql = QueryTools::getQueryCompiledSql($authors);
        $this->assertStringContainsString('SELECT TestOrmAuthors.id AS TestOrmAuthors__id, TestOrmAuthors.name', $sql);

        $value = QueryTools::quoteField('id');
        $this->assertSame('`id`', $value);
        $value = QueryTools::quoteField('table', 'id');
        $this->assertSame('`table`.`id`', $value);

        $table = TableRegistry::getTableLocator()->get('Wp.Postmeta');
        $first = $table->find()->first();
        $this->assertNotEmpty($first);

        $table = TableRegistry::getTableLocator()->get('Wp.Usermeta');
        $first = $table->find()->first();
        $this->assertNotEmpty($first);
    }

    public function testDatatype()
    {
        $table = TableRegistry::getTableLocator()->get('Gbg/Cake5.TestOrmAuthors');

        $author = $table->find()->first();
        $author['serialize_col'] = ['xyz'];
        $author['json_col'] = ['xyz'];
        $author['ip_col'] = '192.168.0.1';

        $result = $table->save($author);
        $this->assertNotEmpty($result);

        $author = $table->find()->first();
        $this->assertSame(['xyz'], $author['serialize_col']);

        $type = new SerializeType();
        $serialize = serialize(['abc']);
        $this->assertSame($serialize, $type->toDatabase(['abc'], new Mysql()));
        $this->assertSame(['abc'], $type->toPhp($serialize, new Mysql()));
        $this->assertSame(['abc'], $type->marshal(['abc']));

        $this->testException(
            \InvalidArgumentException::class,
            function () use ($type) {
                $type->toDatabase(fopen('php://temp', 'r+'), new Mysql());
            }
        );

        $type = new IpType();
        $ipize = inet_pton('192.168.1.1');
        $this->assertSame($ipize, $type->toDatabase('192.168.1.1', new Mysql()));
        $this->assertSame('192.168.1.1', $type->toPhp($ipize, new Mysql()));
        $this->assertSame('192.168.1.1', $type->marshal('192.168.1.1'));

        $this->testException(
            \InvalidArgumentException::class,
            function () use ($type) {
                $type->toDatabase(fopen('php://temp', 'r+'), new Mysql());
            }
        );
    }

    public function testQueryLogger()
    {
        QueryLogger::initialize([]);
        QueryLogger::finalize([]);

        $queryLogger = new QueryLogger();

        $this->assertTrue($queryLogger->evaluateCondition(1, '==', 1));
        $this->assertTrue($queryLogger->evaluateCondition(1, '===', 1));
        $this->assertFalse($queryLogger->evaluateCondition(1, '===', '1'));
        $this->assertTrue($queryLogger->evaluateCondition(1, '!==', '1'));
        $this->assertFalse($queryLogger->evaluateCondition(1, '!=', '1'));
        $this->assertTrue($queryLogger->evaluateCondition(1, '>', 0));
        $this->assertTrue($queryLogger->evaluateCondition(1, '>=', 1));
        $this->assertTrue($queryLogger->evaluateCondition(1, '<', 2));
        $this->assertTrue($queryLogger->evaluateCondition(1, '<=', 1));

        $this->assertTrue($queryLogger->evaluateCondition(1, 'in', [1, 2, 3]));
        $this->assertTrue($queryLogger->evaluateCondition(1, 'notIn', [2, 3]));
        $this->assertTrue($queryLogger->evaluateCondition(1, 'in', ['1']));
        $this->assertFalse($queryLogger->evaluateCondition(1, 'inStrict', ['1']));
        $this->assertTrue($queryLogger->evaluateCondition(1, 'inStrict', [1]));
        $this->assertTrue($queryLogger->evaluateCondition(1, 'notIn', [2]));
        $this->assertTrue($queryLogger->evaluateCondition(1, 'notInStrict', [2]));

        $this->assertTrue($queryLogger->evaluateCondition('abcdef', 'contains', 'a'));
        $this->assertFalse($queryLogger->evaluateCondition('abcdef', 'contains', 'z'));
        $this->assertFalse($queryLogger->evaluateCondition('abcdef', 'notContains', 'c'));
        $this->assertTrue($queryLogger->evaluateCondition('abcdef', 'notContains', 'z'));

        $this->assertTrue($queryLogger->evaluateCondition('abcdef', 'startsWith', 'a'));
        $this->assertFalse($queryLogger->evaluateCondition('abcdef', 'startsWith', 'b'));
        $this->assertTrue($queryLogger->evaluateCondition('abcdef', 'notStartsWith', 'b'));
        $this->assertFalse($queryLogger->evaluateCondition('abcdef', 'notStartsWith', 'a'));

        $this->assertTrue($queryLogger->evaluateCondition('abcdef', 'endsWith', 'f'));
        $this->assertFalse($queryLogger->evaluateCondition('abcdef', 'endsWith', 'b'));
        $this->assertFalse($queryLogger->evaluateCondition('abcdef', 'notEndsWith', 'f'));
        $this->assertTrue($queryLogger->evaluateCondition('abcdef', 'notEndsWith', 'b'));

        $this->assertTrue($queryLogger->evaluateCondition('abcdef', 'regexp', '/bcd/'));
        $this->assertFalse($queryLogger->evaluateCondition('abcdef', 'regexp', '/bce/'));

        $this->assertTrue($queryLogger->evaluateCondition('abcdef', 'notRegexp', '/z/'));
        $this->assertFalse($queryLogger->evaluateCondition('abcdef', 'notRegexp', '/b/'));

        $this->testException(
            \Exception::class,
            function () use ($queryLogger) {
                $queryLogger->evaluateCondition('abcdef', 'unknown', 'a');
            }
        );
    }

    public function testConnection(): void
    {

        $connection = ConnectionManager::get('default');
        $this->expectException(\Exception::class);
        $connection->execute('SELECT xx');

        $table = TableRegistry::getTableLocator()->get('Wp.Users');
        $this->expectException(\Exception::class);
        $table->find()->where(['xxx' => 1])->toArray();

        $this->testException(
            MissingDatasourceConfigException::class,
            function () use ($table) {
                return ConnectionManager::get('non-existing-datasource');
            }
        );
    }

    public function testTableException()
    {
        $table = TableRegistry::getTableLocator()->get('Gbg/Cake5.TestOrmAuthors');

        $oldTableName = $table->getTable();
        // raise internally catched exception
        $table->setTable('not-"existing"-table');
        $this->assertSame(null, $table->getUpdateTime());
        $this->assertSame(null, $table->getUpdateTimestamp());

        // just non existing table
        $table->setTable('not-existing-table');
        $this->assertSame(null, $table->getUpdateTime());
        $this->assertSame(null, $table->getUpdateTimestamp());

        $this->testException(
            \PDOException::class,
            function () use ($table) {
                return $table->find()->limit(1)->toArray();
            }
        );

        $table->setTable($oldTableName);
    }
}
