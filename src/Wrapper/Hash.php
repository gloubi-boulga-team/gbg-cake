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

namespace Gbg\Cake5\Wrapper;

use ArrayAccess;

/**
 * Gbg CakePHP(tm) Hash wrapper Utility class
 * Simplifies/enriches Cakephp Hash usage
 */
class Hash extends \Cake5\Utility\Hash
{
    /**
     * Check key existence. If keys doesn't exist, then fill with $values.
     *
     * @param array<mixed> $thingy
     * @param array<string>|string $keys Array of $keys to search for
     * @param mixed $values Value or array of values to set if keys not found
     *      If array, then all not existing keys are expected to be set with the same-indexed item of $values
     *
     * @return array<mixed> Modified $thingy
     *
     * ### Example
     *
     *      Hash::ensureKey( ['key1' => 'value1', 'key2' => 'value2'], ['key1', 'key3'], 'missing' )
     *          returns ['key1' => 'value1', 'key2' => 'value2', 'key3' => 'missing']
     *
     *      Hash::ensureKey(
     *              ['key1' => 'value1', 'key2' => 'value2'],
     *              ['key1', 'key2', 'key3'],
     *              ['missing1', 'missing2', 'missing3']
     *      )
     *         returns ['key1' => 'value1', 'key2' => 'value2', 'key3' => 'missing3']
     */
    public static function ensureKey(
        array $thingy,
        array|string $keys,
        mixed $values = null
    ): array {

        $keys = (array)$keys;
        $values = (array)$values;

        /** @var array<string> $keys */
        foreach ($keys as $keyIndex => $key) {
            if (static::get($thingy, $key, GBG_CAKE5_TEXT_DEFAULT) === GBG_CAKE5_TEXT_DEFAULT) {
                /** @var array<mixed> $values */
                $valueIndex = min($keyIndex, count($values) - 1);
                $thingy = static::insert($thingy, $key, $values[$valueIndex]);
            }
        }

        return $thingy;
    }


    /**
     *
     * ## Example :
     *
     *      Text::resolveFormula(['field1' => 'toto', 'sub' => ['field2' => 'titi']], '{{field1}} - {{sub.field2}}')
     *          returns 'toto - titi'
     *
     * @param array<mixed> $thingy Can be an Entity, or an array (containing field1/sub.field2 columns)
     * @param string $formula Can be something like "{{field1}} - {{sub.field2}}"
     * @param array<mixed> $options
     *
     * @return string|null
     * @throws \Exception
     */
    public static function resolveFormula(
        array $thingy,
        string $formula,
        array $options = [
            'placeholders'        => [['{{', '}}'], ['{', '}']],
            'removeNotFound'      => true,
            'removeNotFoundSpace' => true
        ]
    ): ?string {

        /** @var array{
         *     placeholders: string[],
         *     removeNotFound: bool,
         *     removeNotFoundSpace: bool,
         *     variables: ?string[]
         * } $options */

        $options += [
            'placeholders'        => [['{{', '}}'], ['{', '}']],
            'removeNotFound'      => true,
            'removeNotFoundSpace' => true
        ];

        $placeholderFound = false;

        // search for placeholders (only one couple allowed)
        foreach ($options['placeholders'] as $allowedPlaceholder) {
            if ($keywords = Text::getBetweenAll($formula, $allowedPlaceholder[0], $allowedPlaceholder[1])) {
                /** @var array<string> $keywords */
                $placeholderFound = true;

                foreach ($keywords as $keyword) {
                    // placeholder can be "multiple". Ex: "Hello[ first_name| last_name],"
                    $keywordExploded = explode('|', $keyword);
                    $done = false;

                    // if $options['variables'] is set, then at least one
                    // of found placeholders must be in $options['variables']
                    //                    if (!empty($options['variables'])) {
                    //                        $keywordExplodedTrimmed = array_map('trim', $keywordExploded);
                    //                        if (!array_intersect($keywordExplodedTrimmed, $options['variables'])) {
                    //                            continue;
                    //                        }
                    //                    }

                    foreach ($keywordExploded as $keywordItem) {
                        // placeholder can contain spaces that will be removed if nothing found
                        $keywordItemTrimmed = trim($keywordItem);
                        $thingyResult = static::get($thingy, $keywordItemTrimmed);
                        if (is_string($thingyResult)) {
                            $formula = str_replace(
                                $allowedPlaceholder[0] . $keyword . $allowedPlaceholder[1],
                                str_replace($keywordItemTrimmed, $thingyResult, $keywordItem),
                                $formula
                            );
                            $done = true;
                        }
                    }

                    if (!$done && $options['removeNotFound']) {
                        if ($options['removeNotFoundSpace']) {
                            while (
                                ($pos = strpos(
                                    $formula,
                                    $allowedPlaceholder[0] . $keyword . $allowedPlaceholder[1]
                                )) !== false
                            ) {
                                $search = $allowedPlaceholder[0] . $keyword . $allowedPlaceholder[1];
                                $formula = Text::mbSubstrReplace($formula, '', $pos, mb_strlen($search));
                                if (
                                    in_array(
                                        trim(mb_substr($formula, $pos - 1, 2)),
                                        ['', ':', ',', ';', '.', '!', '?']
                                    )
                                ) {
                                    $formula = mb_substr($formula, 0, $pos - 1) . mb_substr($formula, $pos);
                                }
                            }
                        }
                    }
                }
            }
        }

        // if no placeholder found, then search for $thingy[$text]
        if (!$placeholderFound) {
            if (
                ($result = static::get($thingy, $formula, GBG_CAKE5_TEXT_DEFAULT))
                    !== GBG_CAKE5_TEXT_DEFAULT
            ) {
                return (is_null($result) || is_string($result)) ? $result : print_r($result, true);
            } else {
                return $formula;
            }
        }

        return $formula;
    }

    /**
     * @inheritDoc
     *
     * @param ArrayAccess|array<mixed> $data
     * @param array<string>|string|int|null $path
     * @param mixed|null $default
     *
     * @return mixed
     */
    public static function get(
        ArrayAccess|array $data,
        array|string|int|null $path,
        mixed $default = null
    ): mixed {
        // avoid that a key literally named '0.1.2' is not found
        if (is_string($path) && !empty($data) && isset($data[$path])) {
            return $data[$path];
        }
        return parent::get($data, $path, $default);
    }
}
