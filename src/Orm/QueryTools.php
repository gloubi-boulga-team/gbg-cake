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

use Cake5\Datasource\QueryInterface;
use Cake5\ORM\Query;
use DateTime;

class QueryTools
{
    /**
     * Based on DebugKit algorithm to calculate the final Sql that will be executed
     * https://github.com/cakephp/debug_kit
     *
     * @param \Cake5\Database\Query $query
     * @return string
     * @throws \Exception
     */
    public static function getQueryCompiledSql(\Cake5\Database\Query $query): string
    {
        $sql = static::interpolate((string)$query, $query->getValueBinder()->bindings());
        $newLines = [
            " FROM ",
            " INNER JOIN ",
            " LEFT JOIN ",
            " WHERE ",
            " ORDER BY ",
            " GROUP BY ",
            " HAVING ",
            " LIMIT "
        ];

        $lineSeparator = "\n";
        foreach ($newLines as $newLine) {
            $sql = str_replace($newLine, $lineSeparator . $newLine, $sql);
        }

        return $sql;
    }


    /**
     * Helper function used to replace query placeholders by the real
     * params used to execute the query.
     *
     * @param string $sql The SQL statement
     * @param array<string|int, array<string, null|string|int|bool|DateTime>> $bindings The Query bindings
     * @return string
     */
    protected static function interpolate(string $sql, array $bindings): string
    {
        $params = array_map(function ($binding) {

            $p = $binding['value'];
            if ($p === null) {
                return 'NULL';
            }

            if ($binding['type'] === 'json') {
                return wp_json_encode($p);
            } elseif ($binding['type'] === 'serialize') {
                return serialize($p);
            } elseif ($binding['type'] === 'ip') {
                return $p;
            } elseif ($p instanceof DateTime) {
                return $p->format('Y-m-d H:i:s');
            } elseif (is_bool($p)) {
                return $p ? '1' : '0';
            } elseif (is_string($p)) {
                $replacements = [
                    '$'  => '\\$',
                    '\\' => '\\\\\\\\',
                    "'"  => "''",
                ];
                $p = strtr($p, $replacements);
                return "'$p'";
            }

            return $p;
        }, $bindings);

        $keys = [];
        $limit = is_int(key($params)) ? 1 : -1;

        foreach ($params as $key => $param) {
            $keys[] = is_string($key) ? "/$key\b/" : '/[?]/';
        }

        //        try {
        //            return preg_replace($keys, $params, $sql, $limit) ?? '';
        //        } catch(\Exception $ex) {
        //            print_r($keys);
        //            echo "\n";
        //            print_r($bindings);
        //            echo "\n";
        //            print_r( $params);
        //            echo "\n";
        //            print_r($sql);
        //            echo "\n";
        //            print_r($limit);
        //            echo "\n";
        //            exit;
        //        }
        return preg_replace($keys, $params, $sql, $limit) ?? '';
    }

    /**
     * @param string $pdoQuery
     * @param array<string> $params
     * @param string $placeHolder
     *
     * @return string
     * @throws \Exception
     */
    public static function getPdoCompiledSql(string $pdoQuery, array $params, string $placeHolder = '?'): string
    {
        if ($placeHolder === '?') {
            return static::getPdoCompiledSqlPlaceholderInterrogation($pdoQuery, $params, $placeHolder);
        } else {
            throw new \Exception('Bad request', 400);
        }


        /*foreach ($params as $key => $value) {
            // Si la clé est numérique, c'est une liaison positionnelle, sinon c'est nommée
            if (is_numeric($key)) {
                $key = $key + 1; // Les paramètres positionnels commencent à 1 dans PDO
                $pdoQuery = preg_replace('/\?/', $value, $pdoQuery, 1);
            } else {
                // Assurer que la clé commence par ':'
                if (strpos($key, ':') !== 0) {
                    $key = ':' . $key;
                }
                $pdoQuery = str_replace($key, $value, $pdoQuery);
            }
        }*/
    }

    /**
     *
     * Queries formatted like this : `SHOW TABLE STATUS WHERE Name = ?`
     *
     * @param string $pdoQuery
     * @param array<string> $params
     * @param string $placeHolder
     *
     * @return string
     */
    protected static function getPdoCompiledSqlPlaceholderInterrogation(
        string $pdoQuery,
        array $params,
        string $placeHolder = '?'
    ): string {
        // This function only replaces '?' characters that are not enclosed in quotation marks.
        $tokens = [];

        $length = strlen($pdoQuery);
        $inSingleQuote = false;
        $inDoubleQuote = false;
        $lastPos = 0;

        for ($i = 0; $i < $length; $i++) {
            $char = $pdoQuery[$i];
            if ($char == "'" && !$inDoubleQuote) {
                $inSingleQuote = !$inSingleQuote;
            } elseif ($char == '"' && !$inSingleQuote) {
                $inDoubleQuote = !$inDoubleQuote;
            }

            // If you find a '?' and you're not in a character string
            if ($char == '?' && !$inSingleQuote && !$inDoubleQuote) {
                // Add the part of the query so far to the array
                $tokens[] = substr($pdoQuery, $lastPos, $i - $lastPos);

                // Add the '?' found on the array
                $tokens[] = '?';

                // Update starting position for next iteration
                $lastPos = $i + 1;
            }
        }

        // Add the rest of the query to the array
        if ($lastPos < $length) {
            $tokens[] = substr($pdoQuery, $lastPos, $length - $lastPos);
        }

        // Now, replace only the '?' that are placeholders
        $finalQuery = '';
        foreach ($tokens as $token) {
            if ($token === '?') {
                // Replace the '?' with the following parameter
                $value = array_shift($params) ?? '';
                $value = is_numeric($value) ? $value : "'" . addslashes($value) . "'";
                $finalQuery .= $value;
            } else {
                // Otherwise, simply add the query chunk to the final result
                $finalQuery .= $token;
            }
        }

        return $finalQuery;
    }

    /**
     * Queries formatted like this : `SHOW TABLE STATUS WHERE Name = :name`
     *
     * @param string $pdoQuery
     * @param string[] $params
     * @param string $placeHolder
     *
     * @return string
     */
    //    protected static function getPdoCompiledSqlPlaceholderColon(
    //        string $pdoQuery,
    //        array $params,
    //        string $placeHolder = '?'
    //    ): string {
    //        foreach ($params as $key => $value) {
    //            // If the key is numeric, it's a positional link, otherwise it's named
    //            if (is_numeric($key)) {
    //                $key = $key + 1;
    //
    //                // Positional parameters start at 1 in PDO
    //                $pdoQuery = preg_replace('/\?/', $value, $pdoQuery, 1) ?? '';
    //            } else {
    //                // Make sure the key starts with ':'.
    //                if (!str_starts_with($key, ':')) {
    //                    $key = ':' . $key;
    //                }
    //                $pdoQuery = str_replace($key, $value, $pdoQuery);
    //            }
    //        }
    //
    //        return $pdoQuery;
    //    }


    /**
     * @var string
     */
    public static string $quoteChar = '`';

    /**
     * Quote a field
     *
     * @param string $field1
     * @param string|null $field2
     *
     * @return string
     */
    public static function quoteField(string $field1, string $field2 = null): string
    {
        foreach (['field1', 'field2'] as $attr) {
            if (!$$attr) {
                continue;
            }
            if (!str_starts_with($$attr, static::$quoteChar)) {
                $$attr = static::$quoteChar . $$attr;
            }
            if (!str_ends_with($$attr, static::$quoteChar)) {
                $$attr .= static::$quoteChar;
            }
        }

        return $field1 . ($field2 ? '.' . $field2 : '');
    }
}
