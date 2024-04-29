## Gbg Wordpress CakePHP 5 adapter -- Wordpress plugin

[![license](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](https://github.com/gloubi-boulga-team/gbg-cake/blob/5.dev/LICENSE)
[![PHP](https://img.shields.io/badge/php->=8.1-blue.svg?style=flat-square)]()
[![stable](https://img.shields.io/badge/stable-5.0.0-7d7d7d.svg?style=flat-square)]()
[![PHPStan](https://img.shields.io/badge/PHPStan-level%208-4ead51.svg?style=flat-square)](https://phpstan.org/)
[![PHPCS](https://img.shields.io/badge/PHPCS-PSR12-4ead51.svg?style=flat-square)](https://github.com/PHPCSStandards/PHP_CodeSniffer/)
[![PHPUnit](https://img.shields.io/badge/PHPUnit-passed-4ead51.svg?style=flat-square)](https://phpunit.de/index.html)
[![coverage](https://img.shields.io/badge/coverage-96.95%25-4ead51.svg?style=flat-square)](https://phpunit.de/index.html)

This Wordpress plugin allows WP developers to use [CakePHP](https://cakephp.org) framework functionalities 
such as [ORM (↗)](https://book.cakephp.org/5/en/orm.html), [Cache (↗)](https://book.cakephp.org/5/en/core-libraries/caching.html), [Log (↗)](https://book.cakephp.org/5/en/core-libraries/logging.html).

This plugin is NOT part of CakePHP development framework and NOT managed by CakePHP developers.

---

**If you want to use/test/evaluate the WP plugin (inside a Wordpress installation), 
download/install/activate the WP plugin from the archive `/dist/gbg-cake5.zip`**

(gbg-cake5 has been submitted to https://wordpress.org/plugins/ but has not been reviewed yet)

---

# Table of Contents

1. [How to use the CakePHP ORM](#cakephp-orm)

   • [Create/manipulate queries](#cakephp-first-query)

   • [Create/manipulate tables](#cakephp-create-tables)

   • [Use validators](#cakephp-validators)

   • [Connect multiple databases (why not ?)](#cakephp-multiple-databases)

2. [How to use the CakePHP Cache](#cakephp-cache)

4. [How to use the CakePHP File Logger](#cakephp-log)

---

## 1. How to use the CakePHP ORM <a name="cakephp-orm"></a> ([see documentation 🔗](https://book.cakephp.org/5/en/orm.html))


### • Create/manipulate queries <a name="cakephp-first-query"></a>

 Keep in mind that this code is only for demo, for `users`, `usermeta`, `posts`, it is recommended to use Wordpress functions... (or not ?)

```
add_action('Gbg/Cake5.Orm.loaded', function() {

  // get `Users` table instance
  $usersTable = \Cake5\ORM\TableRegistry::getTableLocator()->get('Wp.Users');

  // get 10 users with ID between -1 and 1000, having posts, ordered by `user_login`, and get for each one their 5 last posts
  $users = $usersTable

        // create SELECT statement : see https://book.cakephp.org/5/fr/orm/query-builder.html
        ->find()

        // add fields
        ->select(['Users.user_nicename', 'Users.ID'])

        // add conditions
        ->where(['Users.ID >' => -1, 'Users.ID <' => 1000])

        // add limit
        ->limit(10)

        // add junctions
        ->contain([
            'Usermeta', 'Posts' => function($q) {
                // get last 5 posts
                return $q->order(['Posts.post_date' => 'DESC'])->limit(5)->contain(['Postmeta']);
            }
        ])

        // add GROUP BY - useless here, just for the demo
        ->groupBy(['Users.ID'])

        // add ORDER BY
        ->orderBy(['Users.user_login'])

        // only users having posts
        ->matching('Posts')

       // execute query
        ->all()

        // then play with results : see https://book.cakephp.org/5/fr/core-libraries/collections.html
        ->combine('ID', function($item) { return $item; })
        ->toArray();
}
```
Result array `$users` looks like this :

```
Array (
    [1984] => Gbg\Cake5\Model\Entity\Wp\User Object (
        [user_nicename] => George Orwell
        [ID] => 1984
        [posts] => Array (
            [0] => Gbg\Cake5\Model\Entity\Wp\Post Object (
                [ID] => 4891
                [post_author] => 1984
                [post_date] => DateTimeImmutable Object (
                    [date] => 1984-01-01 00:00:01.000000
                    [timezone_type] => 3
                    [timezone] => UTC
                )
                [post_content] => Content for post 1984
                [postmeta] => Array (
                    [0] => Gbg\Cake5\Orm\Entity Object (
                        [meta_id] => 2024
                        [meta_key] => sub_title
                        [meta_value] => 👁️Watching you👁️...
                    )
                    ...
                )
            ...
            )
        )
        [usermeta] => Array (
            [0] => Gbg\Cake5\Orm\Entity Object (
                [umeta_id] => 1
                [user_id] => 1984
                [meta_key] => nickname
                [meta_value] => Big Brother
            )
            [1] => Gbg\Cake5\Orm\Entity Object (
                [umeta_id] => 2
                [user_id] => 1984
                [meta_key] => first_name
                [meta_value] => Big
            )
            ...
        )
    )
    ...
)
```


### • Create/manipulate tables <a name="cakephp-create-tables"></a>

#### Create the database tables
```
global $wpdb;

$tableName = $wpdb->prefix . 'gbg_cake5_demo_things';
$sql = "CREATE TABLE `$tableName` (
    `id` int(11) unsigned NOT NULL,
    `column_text` varchar(255) DEFAULT NULL,
    `column_bool` tinyint(1) DEFAULT 0,
    `column_datetime` datetime DEFAULT NULL,
    `column_json` varchar(500) DEFAULT NULL,
    `created_at` datetime DEFAULT NULL,
    `created_by` int(11) DEFAULT NULL,
    `modified_at` datetime DEFAULT NULL,
    `modified_by` int(11) DEFAULT NULL,
    `archived_at` datetime DEFAULT NULL,
    `archived_by` int(11) DEFAULT NULL,
    PRIMARY KEY(id)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8;";

 if ($wpdb->get_var("SHOW TABLES LIKE '$tableName'") !== $tableName) {
     require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
     dbDelta($sql);
 }

$tableName = $wpdb->prefix . 'gbg_cake5_demo_thing_metas';
$sql = "CREATE TABLE `$tableName` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `thing_id` int(11) unsigned NOT NULL,
    `meta_name` varchar(255) DEFAULT NULL,
    `meta_value` varchar(255) DEFAULT NULL,
    `created_at` datetime DEFAULT NULL,
    `created_by` int(11) DEFAULT NULL,
    `modified_at` datetime DEFAULT NULL,
    `modified_by` int(11) DEFAULT NULL,
    `archived_at` datetime DEFAULT NULL,
    `archived_by` int(11) DEFAULT NULL,
    PRIMARY KEY(id)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8;";

if ($wpdb->get_var("SHOW TABLES LIKE '$tableName'") !== $tableName) {
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
```

#### Create the table classes ThingsTable.php and ThingMetasTable.php in src/Model/Table

```
namespace Gbg\Cake5Demo\Model\Table;

class ThingsTable extends \Gbg\Cake5\Orm\Table
{
    protected array $_defaultConfig = [
    
         // Behaviors are optional but can help : 
         //    For ex, ArchivableBehavior will automatically set `archived_at` rather than deleting the row
         //    and it will add a condition `archived_at IS NULL` to all SELECT queries unless you specify 
         //    an option ['ignoreCallbacks' => 'ArchivableBehavior'] to the query builder
        'behaviors' => [ 'Gbg/Cake5.Trackable', 'Gbg/Cake5.Archivable' ],
        
        // Types are optional but can help :
        //   For ex, 'json' type will automatically encode/decode the column at read/write to database
        'types' => ['json' => [ 'column_json' ]],
        
        // Define relations         
        'hasMany' => [
            'Gbg/Cake5Demo.ThingMetas' => [
                'foreignKey' => 'thing_id',
                'dependent' => true
            ],
        ],
    ];
}
```

```
namespace Gbg\Cake5Demo\Model\Table;

class ThingMetasTable extends \Gbg\Cake5\Orm\Table
{
    protected array $_defaultConfig = [
        'behaviors' => [ 'Gbg/Cake5.Trackable', 'Gbg/Cake5.Archivable' ],
        'hasOne' => [
            'Gbg/Cake5Demo.Things' => [
                'foreignKey' => 'id'
            ],
        ],
    ];
}
```

#### Insert new records

```
$thingsTable = TableRegistry::getTableLocator()->get('Gbg/Cake5Demo.Things');
$thingMetasTable = TableRegistry::getTableLocator()->get('Gbg/Cake5Demo.ThingMetas');

for ($i = 1; $i <= 5; $i++) {
    $data = [
        'id' => $i,
        'column_text' => 'text ' . $i,
        'column_bool' => $i % 2,
        'column_datetime' => new DateTime(),
        'column_json' => ['id' => $i, 'value' => 'val ' . $i],
        'thing_metas' => [
            [
                'meta_name' => 'meta_1',
                'meta_value' => 'meta_value_1',
            ],
            [
                'meta_name' => 'meta_2',
                'meta_value' => 'meta_value_2',
            ]
        ]
    ];

    // Save items to both `Things` and `ThingMetas` tables
    $entity = $thingsTable->newEntity($data, ['associated' => ['ThingMetas']]);
    $thingsTable->save($entity);
}
```

#### Delete some records

```
    // As ThingsTable has ArchivableBehavior, then the rows will not be deleted but automatically marked as `archived`
    $delete = $thingsTable->find()->where(['Things.id IN' => [3, 5]])->toArray();
    $thingsTable->deleteMany($delete);
```

#### Delete all records

```
    $thingsTable->deleteAll(null);
    $thingMetasTable->deleteAll(null);
```

### • Use validators <a name="cakephp-validators"></a>

In your src/Model/Table/DemosTable.php, you can define validators

```
class DemosTable extends Table
{
   ...
    
   public function validationDefault(Validator $validator): Validator
    {
        $validator
            // column `name` : length between 1 and 120
            ->lengthBetween('name', [1, 120], 'Name must be between 1 and 120 characters')
            // column `status` : value between 0 and 2
            ->greaterThanOrEqual('status', 0, 'Status must be a non negative integer between 0 and 2')
            // column `secret` : check ascii
            ->ascii('secret', 'Secret must be ascii');
   
        return $validator;
    }
 }
```

Then, in your controller, you can validate data before saving

```
$demosTable = TableRegistry::getTableLocator()->get('Gbg/Cake5Demo.Demos');
$entity = $demosTable->newEntity($data);
if ($entity->hasErrors()) {
    // do something about $entity->getErrors()
}
```


### • Connect multiple databases <a name="cakephp-multiple-databases"></a>

The `default` connection is calculated through WP configuration. But early in your code, you can define other connections.

```
add_action('Gbg/Cake5.Orm.loaded', function() {
    ConnectionManager::setConfig('connection2', [
        'className' => 'Cake\Database\Connection',
        'driver' => 'Cake\Database\Driver\Mysql',
        'host' => 'host2',
        'username' => 'user2',
        'password' => 'pwd2',
        'database' => 'wordpress2',
        'encoding' => 'utf8',
        'timezone' => 'UTC',
        'cacheMetadata' => true,
        'quoteIdentifiers' => false,
    ]);
});
```

In your src/Model/Table/DemoTable.php, you can define a connection to another database

```
namespace Gbg\Cake5Demo\Model\Table;

class DemoTable extends \Gbg\Cake5\Orm\Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setConnection(ConnectionManager::get('connection2'));
    }
}
```



---

## 2. How to use the CakePHP Cache <a name="cakephp-cache"></a>

The CakePHP Cache is wrapped into a Gbg class. By default, cache files are stored in WP_CONTENT_DIR/.gbg/cache.

```
use Gbg\Cake5\Wrapper\Cache;

add_action('Gbg/Cake5.Cache.loaded', function() {

    $cachePath = Filesystem::concat(WP_CONTENT_DIR, '.gbg', 'cache-demo') . DIRECTORY_SEPARATOR;

    Cache::addConfig('Gbg/Cake5Demo.data', [
        'scope'         => 'user',      // can also be `app` (`session` requires gbg-cake5-core)
        'path'          => $cachePath,
        'duration'      => '1 day'
    ]);

    // load cache instance for previously declared config "Gbg/Cake5Demo.data"
    $cache = Cache::get('Gbg/Cake5Demo.data');

    // `remember` tries to read, but if it fails, calls the callback
    $cache->remember('rememberMe', function() {
       return 'It will only pass here once a day by user.';
    });

    if (!$cache->read('cacheMe')) {
        // It will only pass here once a day by user (again)
        $cache->write('cacheMe', 'If you can...');
    }

    // $cache->clear();                 // will remove all files for this config "Gbg/Cake5Demo.data"
    // $cache->delete('writeThis');     // will remove file for this config "Gbg/Cake5Demo.data" and for this key "writeThis"

    Cache::garbageCollect();            // will remove obsolete files of all declared configs
    // Cache::clearAll();               // will remove all files of all declared configs
    // Cache::clearSessions();          // will remove all files of all declared configs with scope `session`
    // Cache::clearUsers();             // will remove all files of all declared configs with scope `user`
});
```


---

## 3. How to use the CakePHP File Logger <a name="cakephp-log"></a>

The CakePHP Logger is wrapped into a Gbg class. By default, log files are stored in WP_CONTENT_DIR/.gbg/log.

```
use Gbg\Cake5\Wrapper\Log;

add_action('Gbg/Cake5.Log.loaded', function() {
    // write in different files according to the level
    Log::notice('This is a `notice` log from gbg-cake5-demo');
    Log::debug('This is a `debug` log from gbg-cake5-demo');
    Log::info('This is a `info` log from gbg-cake5-demo');

    // write in `debug` file using variable-length argument lists
    Log::debugDefault(
        'This is message #1 written using default config',
        'This is message #2 written using default config',
        'This is message #3 written using default config'
    );

    // write in a `blabla` file
    Log::info(
        'This is a message written using on-the-fly created `blabla` config',
        ['blabla']
    );

    // trigger a notice. If no other plugin changed default config, it will be written in php.log file
    trigger_error('This is a demo for PHP notice');
});
```

Have a good day ☮️ !
