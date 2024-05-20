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

use DateTime;
use Exception;
use JetBrains\PhpStorm\Pure;
use Throwable;
use Transliterator;

/**
 * Class Text
 *
 * A lot of functions to manipulate strings
 */

class Text
{
    /**
     * The quote pattern that will be used by method `quoteLabel`
     * @var string
     */
    protected static string $quotePattern = '« %s »';

    /**
     * Determine if a string has no char in it
     *
     * @param string|null $string
     *
     * @return bool
     */
    public static function isVoid(?string $string = null): bool
    {
        return $string === null || (strlen($string) === 0);
    }

    /**
     * Ensure a string begins with another one
     *
     * @param string $haystack
     * @param string|null $needle
     *
     * @return string
     */
    public static function ensureLeading(string $haystack, ?string $needle = ''): string
    {
        if (static::isVoid($needle)) {
            return $haystack;
        }
        /** @phpstan-ignore-next-line */
        if (!str_starts_with($haystack, $needle)) {
            return $needle . $haystack;
        }
        return $haystack;
    }

    /**
     * Ensure a string ends with another one
     *
     * @param string $haystack
     * @param string|null $needle
     *
     * @return string
     */
    public static function ensureTrailing(string $haystack, ?string $needle = ''): string
    {
        if (static::isVoid($needle)) {
            return $haystack;
        }
        /** @phpstan-ignore-next-line */
        if (!str_ends_with($haystack, $needle)) {
            return $haystack . $needle;
        }
        return $haystack;
    }

    /**
     * Ensure a string begins and ends with another one
     *
     * @param string $haystack
     * @param string|null $needle
     *
     * @return string
     */
    public static function ensureWrapping(string $haystack, ?string $needle = ''): string
    {
        return static::ensureLeading(
            static::ensureTrailing($haystack, $needle),
            $needle
        );
    }

    /**
     * Remove a leading string in a string
     *
     * @param string $haystack
     * @param string|string[] $needle
     *
     * @return string
     */
    public static function removeLeading(string $haystack, string|array $needle = ''): string
    {
        $result = $haystack;
        if (is_array($needle)) {
            foreach ($needle as $item) {
                $result = static::removeLeading($result, $item);
            }
        } elseif (static::startsWith($result, $needle)) {
            $result = mb_substr($result, mb_strlen($needle));
        }

        return $result;
    }

    /**
     * Remove beginning and ending strings in a string
     *
     * @param string $haystack
     * @param string|string[] $needle
     *
     * @return string
     */
    public static function removeWrapping(string $haystack, string|array $needle = ''): string
    {
        return static::removeLeading(static::removeTrailing($haystack, $needle), $needle);
    }


    /**
     * Remove a trailing string in a string
     *
     * @param string $haystack
     * @param string|string[] $needle
     *
     * @return string
     */
    public static function removeTrailing(string $haystack, string|array $needle = ''): string
    {
        $result = $haystack;
        if (is_array($needle)) {
            foreach ($needle as $item) {
                $result = static::removeTrailing($result, $item);
            }
        } elseif (static::endsWith($result, $needle)) {
            $result = mb_substr($result, 0, -mb_strlen($needle));
        }

        return $result;
    }

    /**
     * Detect if a string contains one of several strings
     *
     * @param string $haystack
     * @param string[] $needle
     *
     * @return bool
     */
    public static function containsOne(string $haystack, array $needle): bool
    {
        foreach ($needle as $needleItem) {
            if (str_contains($haystack, $needleItem)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if a string starts with another string (or an array of other strings)
     *
     * @param string $haystack
     * @param null|string|string[] $needle
     *
     * @return bool
     */
    public static function startsWith(string $haystack, null|string|array $needle = null): bool
    {
        if (is_array($needle)) {
            foreach ($needle as $item) {
                if (self::startsWith($haystack, $item)) {
                    return true;
                }
            }
            return false;
        }

        if (static::isVoid($needle)) {
            return false;
        }

        /** @phpstan-ignore-next-line */
        return str_starts_with($haystack, $needle);
    }


    /**
     * Determine if a string ends with another string
     *
     * @param string $haystack
     * @param string|string[] $needle
     *
     * @return bool
     */
    public static function endsWith(string $haystack, null|string|array $needle): bool
    {
        if (is_array($needle)) {
            foreach ($needle as $item) {
                if (self::endsWith($haystack, $item)) {
                    return true;
                }
            }
            return false;
        }

        if (static::isVoid($needle)) {
            return false;
        }

        /** @phpstan-ignore-next-line */
        return str_ends_with($haystack, $needle);
    }

    /**
     * Get the string contained between two other strings
     *  Returns only the first result
     *
     * ### Example
     *
     * ```
     *      Text::getBetween('I did [this] but not [that]', '[', ']')
     *          returns 'this'
     * ```
     *
     * @param string $string $string
     * @param string $left
     * @param string $right
     *
     * @return string|null
     * @throws Exception
     */

    public static function getBetween(string $string, string $left, string $right): ?string
    {
        $leftPos = $rightPos = [];

        // check if $left exists
        if (
            static::isVoid($left)
            || static::isVoid($right)
            || ($pos = mb_strpos($string, $left)) === false
        ) {
            return null;
        }

        $leftPos[] = $lastLeftPos = $pos + mb_strlen($left);
        $lastRightPos = 0;
        $letMeThink = true;

        while ($letMeThink) {
            // calculate $startingPos
            $startingPos = max($lastLeftPos, $lastRightPos + mb_strlen($right));

            // calculate next pos for $right
            if (($nextRightPos = mb_strpos($string, $right, $startingPos)) === false) {
                break;
            }

            // calculate next pos for $left
            if (($nextLeftPos = mb_strpos($string, $left, $startingPos)) === false || ($nextRightPos < $nextLeftPos)) {
                $rightPos[] = $lastRightPos = $nextRightPos;
            } else {
                $leftPos[] = $lastLeftPos = $nextLeftPos + mb_strlen($left);
            }

            $letMeThink = (count($rightPos) < count($leftPos));
            //if (++$i > 50) {
            //  $exception = new Exception();
            //  Log::error(['Text::getBetween infinite loop', $left, $right, $leftPos, $rightPos, $string]);
            //  Log::error($exception->getTraceAsString());
            //  exit($i);
            //}
        }

        if (empty($rightPos)) {
            return null;
        }

        return mb_substr($string, $leftPos[0], $rightPos[count($rightPos) - 1] - $leftPos[0]);
    }

    /**
     * Get all the strings contained between two other strings and returns them all in several formats
     *
     * ### Example
     *
     * ```
     *         Text::getBetweenAll('[test1] [test2] [test3] [test4a [test4b]]', '[', ']',
     *                      ['captureOffset' => false, 'nested' => false]);
     * ```
     *
     *              returns ['test1', 'test2', 'test3', 'test4a [test4b]']
     *
     * ```
     *         Text::getBetweenAll('[test1] [test2] [test3] [test4a [test4b]]', '[', ']',
     *                      ['captureOffset' => true, 'nested' => false]);
     * ```
     *
     *              returns    [
     *                              ['val' => 'test1', 'pos' => 1],
     *                              ['val' => 'test2', 'pos' => 9],
     *                              ['val' => 'test3', 'pos' => 17],
     *                              ['val' => 'test4a [test4b]', 'pos' => 25]
     *                          ]
     *
     * ```
     *         Text::getBetweenAll('[test1] [test2] [test3] [test4a [test4b]]', '[', ']',
     *                      ['captureOffset' => false, 'nested' => true]);
     * ```
     *
     *          returns    [
     *                          ['val' => 'test1'],
     *                          ['val' => 'test2'],
     *                          ['val' => 'test3'],
     *                          [
     *                              'val' => 'test4a ',
     *                              'fullVal' => 'test4a [test4b]',
     *                              'children => [ ['val' => 'test4b'] ]
     *                          ]
     *                      ]
     *
     * ```
     *         Text::getBetweenAll('[test1] [test2] [test3] [test4a [test4b]]', '[', ']',
     *                      ['captureOffset' => true, 'nested' => true]);
     * ```
     *
     *          returns    [
     *                          ['val' => 'test1', 'pos' => 1, 'absPos' => 1],
     *                          ['val' => 'test2', 'pos' => 9, 'absPos' => 9],
     *                          ['val' => 'test3', 'pos' => 17, 'absPos' => 17],
     *                          [
     *                              'val' => 'test4a ',
     *                              'pos' => 24,
     *                              'absPos' => 24,
     *                              'fullVal' => 'test4a [test4b]',
     *                              'children => [ ['val' => 'test4b', 'pos' => 8, 'absPos' => 33] ]
     *                          ]
     *                      ]
     *
     * @param string $string
     * @param string $left
     * @param string $right
     * @param array<string, mixed>|null $options
     *      - bool `captureOffset`
     *      - bool `nested`
     *
     * @return array<mixed>
     * @throws Exception
     */
    public static function getBetweenAll(string $string, string $left, string $right, array $options = null): array
    {
        $options = ($options ?? []) + ['captureOffset' => false, 'nested' => false, '_absPos' => 0, '_level' => 0];
        $return = [];
        $currentString = $string;
        $curPos = 0;

        while (($between = static::getBetween($currentString, $left, $right)) !== null) {
            $curLocalPos = mb_strpos($currentString, $left . $between . $right);
            $curPos = $curPos + $curLocalPos + mb_strlen($left);

            if (!$options['captureOffset'] && !$options['nested']) {
                $return[] = $between;
            } else {
                $result = ['val' => $between];
                if ($options['captureOffset']) {
                    $result['pos'] = $curPos;
                    if ($options['nested']) {
                        $result['absPos'] = $options['_absPos'] + $result['pos'];
                    }
                }

                if ($options['nested'] && mb_strpos($between, $left) !== false) {
                    $result['children'] =
                    static::getBetweenAll(
                        $between,
                        $left,
                        $right,
                        ['_absPos' => $result['absPos'] ?? 0, '_level' => $options['_level'] + 1] + $options
                    );
                }

                $return[] = $result;
            }

            $currentString = mb_substr($currentString, $curLocalPos + mb_strlen($left . $between . $right));
            $curPos += mb_strlen($between . $right);
            //            if (++$i > 50) {
            //                $exception = new Exception();
            //                Log::error(['Text::getBetween infinite loop', $left, $right, $string]);
            //                Log::error($exception->getTraceAsString());
            //                exit($i);
            //            }
        }

        return $return;
    }

    /**
     * Replace all strings contained between two $left and $right strings
     *
     * @param string $string
     * @param string $left
     * @param string $right
     * @param string|callable $replacement
     * @param array<string, mixed>|null $options
     *
     * @return string
     * @throws Exception
     */
    public static function replaceBetweenAll(
        string $string,
        string $left,
        string $right,
        string|callable $replacement,
        ?array $options = null
    ): string {
        $all = static::getBetweenAll($string, $left, $right, $options);

        foreach ($all as $one) {
            if (is_callable($replacement)) {
                $replacementValue = $replacement($string, $one);
            } else {
                $replacementValue = $replacement;
            }
            $string = str_replace($left . $one . $right, $replacementValue, $string);
        }

        return $string;
    }

    /**
     * Replace a string contained between a $left and $right string (only first occurrence)
     *
     * @param string $string
     * @param string $left
     * @param string $right
     * @param string $replacement
     *
     * @return string
     * @throws Exception
     */
    public static function replaceBetween(string $string, string $left, string $right, string $replacement): string
    {
        if ($between = static::getBetween($string, $left, $right)) {
            if (($pos = mb_strpos($string, $left . $between . $right)) !== false) {
                $string = mb_substr($string, 0, $pos) . $replacement .
                    mb_substr($string, $pos + mb_strlen($left . $between . $right));
            }
        }

        return $string;
    }

    /**
     * Replace all break lines by a $replacement string
     *
     * @param string $string
     * @param string $replacement
     *
     * @return string
     */
    public static function replaceNl(string $string, string $replacement): string
    {
        $string = self::explodeNl($string);
        return implode($replacement, $string);
    }

    /**
     * Explode a string with multiple separators
     *
     * @param string $string $string
     * @param array<string> $separators
     * @param array<string, mixed>|null $options
     *
     * @return string[]|false
     */
    public static function explodeMultiple(
        string $string,
        array $separators,
        ?array $options = [
            'asWords' => false,
            'regexpDelimiter' => '~',
            'regexpOptions' => ['i', 's', 'u'],
            'captureOffset' => false,
            'captureDelimiter' => false,
            'limit' => -1
        ]
    ): array|false {

        $pattern = [];
        $options = ($options ?? []) + [
                'asWords' => false,
                'regexpOptions' => ['i', 's'],
                'regexpDelimiter' => '~',
                'captureOffset' => false,
                'captureDelimiter' => false,
                'limit' => -1
            ];

        /** @var string $delimiter */
        $delimiter = $options['regexpDelimiter'];

        /** @var array<int, string> $regexpOptions */
        $regexpOptions = $options['regexpOptions'];
        /** @var int $limit */
        $limit = $options['limit'];

        foreach ($separators as $separator) {
            $pattern[] = '(' . ($options['asWords'] ? '\b' : '') . preg_quote($separator, $delimiter) .
                ($options['asWords'] ? '\b' : '') . ')';
        }

        $pattern = $delimiter . implode('|', $pattern) . $delimiter .
            implode('', $regexpOptions);

        $flags = 0;
        if (!empty($options['captureOffset'])) {
            $flags += PREG_SPLIT_OFFSET_CAPTURE;
        }

        if (!empty($options['captureDelimiter'])) {
            $flags += PREG_SPLIT_DELIM_CAPTURE;
        }

        return preg_split($pattern, $string, $limit, $flags);
    }

    /**
     * Explode a string using break lines chars as delimiters (\r and \n)
     *
     * @param string $string The string to be exploded
     * @param int $limit
     *
     * @return string[] The found array
     */
    public static function explodeNl(string $string, int $limit = PHP_INT_MAX): array
    {
        $string = str_replace(["\r", "\n\n"], "\n", $string);
        return explode("\n", $string, $limit);
    }

    /**
     * Quote a label with nice quote wrappers
     *
     * @param string $label
     * @param string|null $quotePattern
     *
     * @return string
     */
    public static function quoteLabel(string $label, ?string $quotePattern = '« %s »'): string
    {
        return sprintf($quotePattern ?? static::$quotePattern, $label);
    }

    /**
     * Make a string's first character uppercase, when all other chars are made lowercase if $lowerTheRest is true
     * MB compatible (php ucfirst is not)
     * @param string $string
     * @param bool $lowerTheRest
     *
     * @return string
     */
    public static function ucFirst(string $string, bool $lowerTheRest = true): string
    {
        if ($lowerTheRest) {
            $string = mb_strtolower($string);
        }

        $firstChar = mb_substr($string, 0, 1);
        $theRest = mb_substr($string, 1);

        return mb_strtoupper($firstChar) . $theRest;
    }

    /**
     * Make all string's words first character uppercase,
     * when all other chars are made lowercase if $lowerTheRest is true
     *
     * @param string $string
     * @param bool $lowerTheRest
     *
     * @return string
     */
    public static function ucWords(string $string, bool $lowerTheRest = true): string
    {
        $stringWords = explode(' ', $string);
        foreach ($stringWords as $k => $word) {
            $stringWords[$k] = static::ucFirst($word, $lowerTheRest);
        }

        return implode(' ', $stringWords);
    }

    /**
     * Function to reverse a print_r outputted thingy - usefull for debugging
     * Note that objects will be converted to arrays
     *
     *  From lib https://gist.github.com/simivar/037b13a9bbd53ae5a092d8f6d9828bc3
     *  Modified following divinity76 commented on Mar 10, 2020
     *
     * ### Example
     *      Text::reversePrintR(print_r(['a' => 'blabla'], true));
     *
     * @param string $input
     *
     * @return mixed
     */
    public static function reversePrintR(string $input): mixed
    {
        if (!$split = preg_split('#\r?\n#', trim($input))) {
            return $input;
        }

        if (!$lines = array_filter($split)) {
            return '';
        }

        if (trim($lines[ 0 ]) !== 'Array' && !preg_match('/^\S+? Object$/', trim($lines[ 0 ]))) {
            // bottomed out to something that isn't an array or object
            return ($input === '' ? null : $input);
        } else {
            // this is an array or object, lets parse it
            $match = array();
            if (preg_match("/(\s{5,})\(/", $lines[ 1 ], $match)) {
                // this is a tested array/recursive call to this function
                // take a set of spaces off the beginning
                $spaces = $match[ 1 ];
                $spaces_length = strlen($spaces);
                $lines_total = count($lines);
                for ($i = 0; $i < $lines_total; $i++) {
                    if (substr($lines[ $i ], 0, $spaces_length) == $spaces) {
                        $lines[ $i ] = substr($lines[ $i ], $spaces_length);
                    }
                }
            }

            //$split = array_map('trim', explode(' ', $lines[ 0 ]));
            //$is_object = $split[count($split)-1] === 'Object';

            $is_object = trim($lines[ 0 ]) === 'stdClass Object';
            array_shift($lines);
            array_shift($lines);
            array_pop($lines);

            $input = implode("\n", $lines);
            $matches = array();

            // make sure we only match stuff with 4 preceding spaces (stuff for this array and not a nested one)
            preg_match_all("/^\s{4}\[(.+?)] \=\> /m", $input, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
            $pos = array();
            $previous_key = '';
            $in_length = strlen($input);

            // store the following in $pos:
            // array with key = key of the parsed array's item
            // value = array(start position in $in, $end position in $in)
            foreach ($matches as $match) {
                $key = $match[ 1 ][ 0 ];
                $start = $match[ 0 ][ 1 ] + strlen($match[ 0 ][ 0 ]);
                $pos[ $key ] = array($start, $in_length);
                if ($previous_key != '') {
                    $pos[ $previous_key ][ 1 ] = $match[ 0 ][ 1 ] - 1;
                }
                $previous_key = $key;
            }

            $ret = array();
            foreach ($pos as $key => $where) {
                // recursively see if the parsed out value is an array too
                $ret[ $key ] = static::reversePrintR(substr($input, $where[ 0 ], $where[ 1 ] - $where[ 0 ]));
            }

            return $is_object ? (object)$ret : $ret;
        }
    }

    /**
     * Check if a string is a json encoding
     *
     * @param string $string
     *
     * @return mixed
     */
    public static function isJson(string $string = ''): mixed
    {
        if (static::isVoid($string)) {
            return false;
        }
        $return = json_decode($string, true);

        return ($return === null && json_last_error() === JSON_ERROR_NONE ? false : $return);
    }

    /**
     * @param string $date
     * @param string $format
     *
     * @return bool
     */
    public static function isDate(string $date, string $format = 'Y-m-d'): bool
    {
        try {
            $d = DateTime::createFromFormat($format, $date);
            return $d && ($d->format($format) === $date);
        } catch (Throwable $ex) {
        }

        return false;
    }

    /**
     * @param string $string
     *
     * @return bool
     */
    public static function isSerialized(string $string): bool
    {
        return (@unserialize($string) !== false);
    }

    /**
     * Ah ! You will laugh. Find a reason to say if a string is a boolean, searching for keywords
     *      like 'yes', 'no', 'true', 'false', 'vrai', 'verdadero', 'wahr' etc.
     *
     * @param string $val
     * @param mixed $default
     *
     * @return mixed Returns true/false or $default
     */
    public static function extractBoolean(string $val, mixed $default = null): mixed
    {
        $val = mb_strtolower($val);

        if (
            in_array(
                $val,
                ['1', 'true', 'vrai', 'verdadero', 'verdadeiro', 'wahr', 'ok', 'yes', 'y', 'oui', 'si', 'ya', 'on']
            )
        ) {
            return true;
        }

        if (
            in_array(
                $val,
                ['0', 'false', 'faux', 'falso', 'falsch', 'nok', 'ko', 'no', 'n', 'non', 'nein', 'off']
            )
        ) {
            return false;
        }

        return $default;
    }

    /**
     * Concatenate multiple strings with glue(s) if strings are not empty
     *
     * @param string|string[] $glue The glue that will be added between strings
     * @param string ...$texts The texts to concatenate
     *
     * @return string
     *
     * ### Example
     *  ```
     *      Text::concatenate(' : ', 'first', 'second')
     *          returns 'first : second'
     *
     *      Text::concatenate(' : ', 'first', '', 'third')
     *          returns 'first : third'
     *
     *      Text::concatenate([':', '::'], 'first', 'second', 'third', 'fourth')
     *          returns 'first:second::third::fourth'
     * ```
     */
    public static function concatenate(string|array $glue, string ...$texts): string
    {
        $glue = (array)$glue;
        $args = func_get_args();
        array_shift($args);
        $result = '';

        foreach ($args as $index => $arg) {
            /** @var string $arg */
            if (trim(strval($arg)) !== '') {
                $sep = $glue[max(0, min($index - 1, count($glue) - 1))];
                $result = ($result !== '' ? $result . $sep . $arg : $arg);
            }
        }

        return $result;
    }

    /**
     * Detect if a string contain emojis
     * @param string $text
     *
     * @return string[]|bool
     */
    //    public static function containsEmojis(string $text): bool|array
    //    {
    //        $regex = "/[\\x{1F600}-\\x{1F64F}" .          // Emoticons
    //            "\\x{1F680}-\\x{1F6FF}" .                 // Transport And Map Symbols
    //            "\\x{24C2}-\\x{1F251}" .
    //            "\\x{1F30D}-\\x{1F567}" .
    //            "\\x{1F900}-\\x{1F9FF}" .                 // Supplemental Symbols and Pictographs
    //            "\\x{1F300}-\\x{1F5FF}" .                 // Miscellaneous Symbols and Pictographs
    //            "\\x{2600}-\\x{26ff}" .                   // Miscellaneous Symbols
    //            "\\x{2700}-\\x{27BF}" .                   // Dingbats
    //            "\\x{1f1e6}-\\x{1f1ff}" .                 // Regional indicator symbol
    //            "\\x{1f191}-\\x{1f251}" .
    //            "\\x{1f004}\\x{1f0cf}" .
    //            "\\x{1f170}-\\x{1f171}" .
    //            "\\x{1f17e}-\\x{1f17f}" .
    //            "\\x{1f18e}\\x{3030}\\x{2b50}\\x{2b55}" .
    //            "\\x{2934}-\\x{2935}" .
    //            "\\x{2b05}-\\x{2b07}" .
    //            "\\x{2b1b}-\\x{2b1c}" .
    //            "\\x{3297}\\x{3299}\\x{303d}\\x{00a9}\\x{00ae}\\x{2122}\\x{23f3}\\x{24c2}" .
    //            "\\x{23e9}-\\x{23ef}\\x{25b6}" .
    //            "\\x{23f8}-\\x{23fa}]/u";
    //
    //        preg_match_all($regex, $text, $matches);
    //        print_r($text);
    //        echo "\n";
    //        print_r(strlen($matches[0][2]));
    //        echo "\n";
    //        print_r(mb_strlen($matches[0][2]));
    //        exit;
    //
    //        return empty($matches) ? false : (array_filter($matches[0]) ?? false);
    //    }


    /**
     * Get bytes (as integer) from human-readable string like '5Mb'
     *
     * ### Example
     *
     * ```
     *      Text::parseSizeToBytes('15G'); // will return 15 * 1024 * 1024 * 1024
     * ```
     *
     * @param  string $val
     *
     * @return integer
     */
    public static function parseSizeToBytes(string $val): int
    {
        $val = strtolower(trim($val));
        // remove last "b"
        if (str_ends_with($val, 'b')) {
            $val = substr($val, 0, -1);
        }

        $exponents = ['k' => 1, 'm' => 2, 'g' => 3, 't' => 4, 'p' => 5, 'e' => 6, 'z' => 7, 'y' => 8];
        $lastChar = substr($val, -1);
        if (ctype_digit($lastChar)) {
            $exponent = 0;
        } elseif (!isset($exponents[$lastChar])) {
            return -1;
        } else {
            $exponent = $exponents[$lastChar];
            $val = substr($val, 0, -1);
        }

        return intval(round(floatval($val) * pow(1024, $exponent)));
    }

    /**
     * Get human-readable string like '5Mb' from bytes (as integer)
     *
     * ### Example
     * ```
     *      Text::parseBytesToSize(15 * 1024 * 1024 * 1024); // will '15G'
     * ```
     *
     * @param int|float $val
     * @param int $decimals
     *
     * @return string
     */
    public static function parseBytesToSize(int|float $val, int $decimals = 0): string
    {

        $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        $bytes = max($val, 0);

        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $decimals) . '' . $units[$pow];
    }

    /**
     * Executes sprintf function with a test on $arguments count
     *
     * @param string $format
     * @param array<string|null> $arguments
     *
     * @return string
     */
    public static function safePrintF(string $format, array $arguments = []): string
    {
        $expected = preg_match_all(
            "~%(?:(\d+)[$])?[-+]?(?:[ 0]|['].)?(?:[-]?\d+)?(?:[.]\d+)?[%bcdeEufFgGosxX]~",
            $format,
            $expected
        );

        while ($expected < count($arguments)) {
            array_pop($arguments);
        }

        return vsprintf($format, $arguments);
    }

    /**
     * (C) CakePHP
     * Converts the decimal value of a multibyte character string
     * to a string
     *
     * @param array<int> $array Array
     *
     * @return string
     */
    #[Pure] public static function ascii(array $array): string
    {
        return \Cake5\Utility\Text::ascii($array);
    }

    /**
     * (C) CakePHP
     * Get the default transliterator.
     *
     * @return Transliterator|null Either a Transliterator instance, or `null`
     *   in case no transliterator has been set yet.
     */
    #[Pure] public static function getTransliterator(): ?Transliterator
    {
        return \Cake5\Utility\Text::getTransliterator();
    }

    /**
     * (C) CakePHP
     * Set the default transliterator.
     *
     * @param Transliterator $transliterator A `Transliterator` instance.
     *
     * @return void
     */
    public static function setTransliterator(Transliterator $transliterator): void
    {
        \Cake5\Utility\Text::setTransliterator($transliterator);
    }

    /**
     * (C) CakePHP
     * Get default transliterator identifier string.
     *
     * @return string Transliterator identifier.
     */
    public static function getTransliteratorId(): string
    {
        return \Cake5\Utility\Text::getTransliteratorId();
    }

    /**
     * (C) CakePHP
     * Set default transliterator identifier string.
     *
     * @param string $transliteratorId Transliterator identifier.
     *
     * @return void
     */
    public static function setTransliteratorId(string $transliteratorId): void
    {
        \Cake5\Utility\Text::setTransliteratorId($transliteratorId);
    }

    /**
     * (C) CakePHP
     * Transliterate string.
     *
     * @param string $string String to transliterate.
     * @param Transliterator|string|null $transliterator Either a Transliterator
     *   instance, or a transliterator identifier string. If `null`, the default
     *   transliterator (identifier) set via `setTransliteratorId()` or
     *   `setTransliterator()` will be used.
     *
     * @return string
     * @see https://secure.php.net/manual/en/transliterator.transliterate.php
     */
    public static function transliterate(string $string, Transliterator|string|null $transliterator = null): string
    {
        return \Cake5\Utility\Text::transliterate($string, $transliterator);
    }

    /**
     * (C) CakePHP
     * Returns a string with all spaces converted to dashes (by default),
     * characters transliterated to ASCII characters, and non word characters removed.
     *
     * ### Options:
     *
     * - `replacement`: Replacement string. Default '-'.
     * - `transliteratorId`: A valid transliterator id string.
     *   If `null` (default) the transliterator (identifier) set via
     *   `setTransliteratorId()` or `setTransliterator()` will be used.
     *   If `false` no transliteration will be done, only non words will be removed.
     * - `preserve`: Specific non-word character to preserve. Default `null`.
     *   For e.g. this option can be set to '.' to generate clean file names.
     *
     * @param string $string the string you want to slug
     * @param array<string, mixed>|string $options If string it will be use as replacement character
     *   or an array of options.
     *
     * @return string
     * @see setTransliterator()
     * @see setTransliteratorId()
     */
    public static function slug(string $string, array|string $options = []): string
    {
        return \Cake5\Utility\Text::slug($string, $options);
    }

    /**
     * (C) CakePHP
     * Tokenizes a string using $separator, ignoring any instance of $separator that appears between
     * $leftBound and $rightBound.
     *
     * @param string $data The data to tokenize.
     * @param string $separator The token to split the data on.
     * @param string $leftBound The left boundary to ignore separators in.
     * @param string $rightBound The right boundary to ignore separators in.
     *
     * @return array<string> Array of tokens in $data.
     */
    public static function tokenize(
        string $data,
        string $separator = ',',
        string $leftBound = '(',
        string $rightBound = ')'
    ): array {
        return \Cake5\Utility\Text::tokenize($data, $separator, $leftBound, $rightBound);
    }

    /**
     * (C) CakePHP
     * Generate a random UUID version 4
     *
     * Warning: This method should not be used as a random seed for any cryptographic operations.
     * Instead, you should use `Security::randomBytes()` or `Security::randomString()` instead.
     *
     * It should also not be used to create identifiers that have security implications, such as
     * 'unguessable' URL identifiers. Instead, you should use {@link \Cake5\Utility\Security::randomBytes()}` for that.
     *
     * @see https://www.ietf.org/rfc/rfc4122.txt
     *
     * @return string RFC 4122 UUID
     */
    public static function uuid(): string
    {
        return \Cake5\Utility\Text::uuid();
    }

    /**
     * Check if the string contain multibyte characters
     *
     * @param string $string value to test
     *
     * @return bool
     */
    public static function isMultibyte(string $string): bool
    {
        $length = strlen($string);

        for ($i = 0; $i < $length; $i++) {
            $value = ord($string[$i]);
            if ($value > 128) {
                return true;
            }
        }

        return false;
    }


    /**
     * Php substr_replace with multibyte support
     *
     * @param string $string
     * @param string $replacement
     * @param int $start
     * @param int|null $length
     * @param string|null $encoding
     *
     * @return string
     */
    public static function mbSubstrReplace(
        string $string,
        string $replacement,
        int $start = 0,
        int $length = null,
        string $encoding = null
    ): string {
        if (!extension_loaded('mbstring') === true) {
            return (is_null($length) === true)
                ? substr_replace($string, $replacement, $start)
                : substr_replace($string, $replacement, $start, $length);
        }

        $stringLength = (is_null($encoding) === true) ? mb_strlen($string) : mb_strlen($string, $encoding);

        if ($start < 0) {
            $start = max(0, $stringLength + $start);
        } elseif ($start > $stringLength) {
            $start = $stringLength;
        }

        if ($length < 0) {
            $length = max(0, $stringLength - $start + $length);
        } elseif ((is_null($length) === true) || ($length > $stringLength)) {
            $length = $stringLength;
        }

        if (($start + $length) > $stringLength) {
            $length = $stringLength - $start;
        }

        if (is_null($encoding) === true) {
            return mb_substr($string, 0, $start) . $replacement
                . mb_substr($string, $start + $length, $stringLength - $start - $length);
        }

        return mb_substr($string, 0, $start, $encoding) . $replacement
            . mb_substr($string, $start + $length, $stringLength - $start - $length, $encoding);
    }
}
