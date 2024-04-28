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

defined('WPINC') || die;

/**
 * ### Usage example for Cake\ORM
 *
 * WARNING : For `users`, `usermeta`, `posts` tables, it is recommended to use Wordpress functions
 * Following example is just for demonstration purpose
 */

use Cake5\ORM\TableRegistry;
use Cake5\Utility\Hash;
use Gbg\Cake5\Wrapper\Log;

add_action('Gbg/Cake5.Orm.loaded', function () {

    try {
        // Get table instances
        $postsTable = TableRegistry::getTableLocator()->get('Wp.Posts');
        $usersTable = TableRegistry::getTableLocator()->get('Wp.Users');

        // Search some users -------------------------------------------------------------------------------------------

        $users = $usersTable
            // create SELECT statement : see https://book.cakephp.org/5/fr/orm/query-builder.html
            ->find()
            // add columns that will be selected - if no columns set, all columns will be automatically added
            ->select(['Users.user_nicename', 'Users.ID'])
            // add where conditions
            ->where(['Users.ID >' => -1, 'Users.ID <' => 1000])
            // add limit
            ->limit(10)
            // add modifiers
            ->modifier(['SQL_NO_CACHE'])
            // add junctions
            ->contain([
                'Usermeta', 'Posts' => function ($q) {
                    // only get last 5 posts
                    return $q->order(['Posts.post_date' => 'DESC'])->limit(5)->contain(['Postmeta']);
                }
            ])
            // add GROUP BY - useless here, just for the demo
            ->groupBy(['Users.ID'])
            // add ORDER BY
            ->orderBy('Users.user_login')
            // only users having posts
            ->matching('Posts')
            // execute query
            ->all()
            // then, play with results : see https://book.cakephp.org/5/fr/core-libraries/collections.html
            ->combine('ID', function ($item) {
                return $item;
            })
            ->toArray();

        //      Result array `$users` looks like :
        //
        //      Array (
        //          [1984] => Gbg\Cake5\Model\Entity\Wp\User Object (
        //              [user_nicename] => George Orwell
        //              [ID] => 1984
        //              [posts] => Array (
        //                  [0] => Gbg\Cake5\Model\Entity\Wp\Post Object (
        //                      [ID] => 1984
        //                      [post_author] => 1984
        //                      [post_date] => DateTimeImmutable Object (
        //                          [date] => 1984-01-01 00:00:01.000000
        //                          [timezone_type] => 3
        //                          [timezone] => UTC
        //                      )
        //                      [post_content] => Content for post 1984
        //                      [postmeta] => Array (
        //                          [0] => Gbg\Cake5\Orm\Entity Object (
        //                              [meta_id] => 2024
        //                              [meta_key] => title_color
        //                              [meta_value] => black/white
        //                          )
        //                          ...
        //                      )
        //                  ...
        //                  )
        //              )
        //              [usermeta] => Array (
        //                  [0] => Gbg\Cake5\Orm\Entity Object (
        //                      [umeta_id] => 1
        //                      [user_id] => 1984
        //                      [meta_key] => nickname
        //                      [meta_value] => Big Brother
        //                  )
        //                  [1] => Gbg\Cake5\Orm\Entity Object (
        //                      [umeta_id] => 2
        //                      [user_id] => 1984
        //                      [meta_key] => first_name
        //                      [meta_value] => George
        //                  )
        //                  ...
        //              )
        //          )
        //      )

        // Then manipulate objects

        foreach ($users as $user) {
            Log::info(
                sprintf(
                    __(
                        'User %s - %s has at least %s posts and %s usermetas',
                        'gbg-cake5'
                    ),
                    $user->ID,
                    $user->user_nicename,
                    count($user->posts ?? []),
                    count($user->usermeta ?? [])
                )
            );

            // Hash : see https://book.cakephp.org/5/fr/core-libraries/hash.html
            $titles = Hash::extract($user->posts, '{n}.post_title');

            Log::info(
                sprintf(
                    __('His latest posts have these titles : %s', 'gbg-cake5'),
                    implode(', ', $titles)
                )
            );

            // and modify it (you should avoid uncommenting following lines 😜)
            // $user->user_nicename = \Gbg\Cake5\Utility\Text::ensureTrailing($user->user_nicename, '...);
            // $usersTable->save($user);
        }

        // Search some posts -------------------------------------------------------------------------------------------

        $post = $postsTable
            ->find()
            ->where([
                'OR' => [
                    'Posts.post_title LIKE' => '%mention%',
                    'Posts.post_title LIKE ' => '%first%',
                    'Posts.post_title LIKE  ' => '%draft%',
                ]
            ])
            ->contain(['Postmeta'])
            ->orderBy(['Posts.post_date' => 'DESC'])
            ->groupBy(['Posts.post_title'])
            ->all()
            // only get first this time
            ->first();

        Log::info($post);

        // Work on custom tables ---------------------------------------------------------------------------------------

        include_once 'src/Model/Table/ThingsTable.php';
        include_once 'src/Model/Table/ThingMetasTable.php';
        include_once 'src/Model/Entity/Thing.php';

        $thingsTable = TableRegistry::getTableLocator()->get('Gbg/Cake5Demo.Things');
        $thingMetasTable = TableRegistry::getTableLocator()->get('Gbg/Cake5Demo.ThingMetas');

        // Delete everything

        $thingsTable->deleteAll(null);
        $thingMetasTable->deleteAll(null);

        // Create some new items ------------------------------------

        // As tables have TrackableBehavior, columns `created_at`, `created_by`,
        // `modified_at`, `modified_by` will be automatically filled
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

        // Delete some new items ------------------------------------

        // As ThingsTable has ArchivableBehavior,
        // then the rows will not be deleted but automatically marked as `archived`
        $delete = $thingsTable->find()->where(['Things.id IN' => [3, 5]])->toArray();
        $thingsTable->deleteMany($delete);

        // Select items ------------------------------------

        // ArchivableBehavior avoids loading archived items by default
        $items = $thingsTable->find()->toArray();
        Log::debug('Item count without ignoreCallbacks is 3 : ' . count($items));

        // `ignoreCallbacks` option avoids ArchivableBehavior filter
        $items = $thingsTable->find(type: 'all', ignoreCallbacks: true)->toArray();
        Log::debug('Item count with ignoreCallbacks is 5 : ' . count($items));

        // Use virtual property - see Things::_getVirtualValue
        Log::debug('Item virtualValue before set : ' . $items[0]->virtualValue);
        $items[0]->virtualValue = 'test virtual value';
        Log::debug('Item virtualValue after set : ' . $items[0]->virtualValue);
    } catch (\Throwable $ex) {
        Log::error('Gbg/Cake5Demo error - ' . $ex->getMessage() . ' at ' . $ex->getFile() . ':' . $ex->getLine());
        Log::error($ex->getTraceAsString());
    }
});
