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

use Doctrine5\Inflector\InflectorFactory;
use Doctrine5\Inflector\Language;
use Gbg\Cake5\Orm\Entity;
use Gbg\Cake5\TestCase;
use Transliterator;

/**
 * @coversDefaultClass \Gbg\Cake5\Wrapper\Text
 */
class TextTest extends TestCase
{
    /**
     * @test Text::getBetweenAll
     *
     * @return void
     */
    public function testBetweenAll(): void
    {
        $tests = [
            // simple test
            [
                'getBetweenAll()',
                [Text::class, 'getBetweenAll'],
                ['xx {x1} {x2} yy', '{', '}'],
                ['x1', 'x2']
            ],
            // simple test
            [
                'getBetweenAll()',
                [Text::class, 'getBetweenAll'],
                ['xx {x1} {x2}}} yy', '{', '}}}'],
                ['x1} {x2']
            ],
            // simple test with capture Offset
            [
                'getBetweenAll()',
                [Text::class, 'getBetweenAll'],
                ['xx {x1} {x2} yy', '{', '}', ['captureOffset' => true]],
                [['val' => 'x1', 'pos' => 4], ['val' => 'x2', 'pos' => 9]]
            ],
            // simple test with capture Offset and Nested
            [
                'getBetweenAll()',
                [Text::class, 'getBetweenAll'],
                ['xx {x1} {x2} yy', '{', '}', ['captureOffset' => true, 'nested' => true]],
                [['val' => 'x1', 'pos' => 4, 'absPos' => 4], ['val' => 'x2', 'pos' => 9, 'absPos' => 9]]
            ],
            // simple test with Nested
            [
                'getBetweenAll()',
                [Text::class, 'getBetweenAll'],
                ['xx {x1} {x2} yy', '{', '}', ['nested' => true]],
                [['val' => 'x1'], ['val' => 'x2']]
            ],
            // simple test with Nested
            [
                'getBetweenAll()',
                [Text::class, 'getBetweenAll'],
                ['xx {x1{x1-1}} {x2} yy', '{', '}', ['nested' => true]],
                [['val' => 'x1{x1-1}', 'children' => [['val' => 'x1-1']]], ['val' => 'x2']]
            ],
            // simple test with Nested and captureOffset
            [
                'getBetweenAll()',
                [Text::class, 'getBetweenAll'],
                ['xx {x1{x1-1}} {x2} yy', '{', '}', ['nested' => true, 'captureOffset' => true]],

                [
                    [
                        'val' => 'x1{x1-1}',
                        'pos' => 4,
                        'absPos' => 4,
                        'children' => [['val' => 'x1-1', 'pos' => 3, 'absPos' => 7]]
                    ],
                    [
                        'val' => 'x2',
                        'pos' => 15,
                        'absPos' => 15
                    ]
                ]
            ],
            // complex nested tags
            [
                'getBetweenAll()',
                [Text::class, 'getBetweenAll'],
                [
                    'xx {x1 {x1-1 {x1-1-1 {x1-1-1-1} {x1-1-1-2}} {x1-1-2}} {x1-2}} {x2 {x2-1 {x2-1-1 {x2-1-1-1} ' .
                    '{x2-1-1-2}} {x2-1-2}} {x2-2}} {x3} yy', '{', '}', ['nested' => true]],
                [
                    [
                        'val'      => 'x1 {x1-1 {x1-1-1 {x1-1-1-1} {x1-1-1-2}} {x1-1-2}} {x1-2}',
                        'children' => [
                            [
                                'val'      => 'x1-1 {x1-1-1 {x1-1-1-1} {x1-1-1-2}} {x1-1-2}',
                                'children' => [
                                    [
                                        'val'      => 'x1-1-1 {x1-1-1-1} {x1-1-1-2}',
                                        'children' => [
                                            ['val' => 'x1-1-1-1'],
                                            ['val' => 'x1-1-1-2']
                                        ]
                                    ],
                                    [
                                        'val' => 'x1-1-2',
                                    ]
                                ]
                            ],
                            [
                                'val' => 'x1-2'
                            ]
                        ]
                    ],
                    [
                        'val'      => 'x2 {x2-1 {x2-1-1 {x2-1-1-1} {x2-1-1-2}} {x2-1-2}} {x2-2}',
                        'children' => [
                            [
                                'val'      => 'x2-1 {x2-1-1 {x2-1-1-1} {x2-1-1-2}} {x2-1-2}',
                                'children' => [
                                    [
                                        'val'      => 'x2-1-1 {x2-1-1-1} {x2-1-1-2}',
                                        'children' => [
                                            ['val' => 'x2-1-1-1'],
                                            ['val' => 'x2-1-1-2']
                                        ]
                                    ],
                                    [
                                        'val' => 'x2-1-2'
                                    ]
                                ]
                            ],
                            [
                                'val' => 'x2-2'
                            ]
                        ]
                    ],
                    [
                        'val' => 'x3'
                    ]
                ]
            ],
            // complex nested tags with captureOffset
            [
                'getBetweenAllY()',
                [Text::class, 'getBetweenAll'],
                ['xx {x1 {x1-1 {x1-1-1 {x1-1-1-1} {x1-1-1-2}} {x1-1-2}} {x1-2}} {x2 {x2-1 {x2-1-1 {x2-1-1-1} '
                . '{x2-1-1-2}} {x2-1-2}} {x2-2}} {x3} yy', '{', '}', ['nested' => true, 'captureOffset' => true]],
                [
                    [
                        'val'      => 'x1 {x1-1 {x1-1-1 {x1-1-1-1} {x1-1-1-2}} {x1-1-2}} {x1-2}',
                        'pos'      => 4,
                        'absPos'   => 4,
                        'children' => [
                            [
                                'val'      => 'x1-1 {x1-1-1 {x1-1-1-1} {x1-1-1-2}} {x1-1-2}',
                                'pos'      => 4,
                                'absPos'   => 8,
                                'children' => [
                                    [
                                        'val'      => 'x1-1-1 {x1-1-1-1} {x1-1-1-2}',
                                        'pos'      => 6,
                                        'absPos'   => 14,
                                        'children' => [
                                            ['val' => 'x1-1-1-1', 'pos' => 8, 'absPos' => 22],
                                            ['val' => 'x1-1-1-2', 'pos' => 19, 'absPos' => 33]
                                        ]
                                    ],
                                    [
                                        'val'    => 'x1-1-2',
                                        'pos'    => 37,
                                        'absPos' => 45,
                                    ]
                                ]
                            ],
                            [
                                'val'    => 'x1-2',
                                'pos'    => 51,
                                'absPos' => 55
                            ]
                        ]
                    ],
                    [
                        'val'      => 'x2 {x2-1 {x2-1-1 {x2-1-1-1} {x2-1-1-2}} {x2-1-2}} {x2-2}',
                        'pos'      => 63,
                        'absPos'   => 63,
                        'children' => [
                            [
                                'val'      => 'x2-1 {x2-1-1 {x2-1-1-1} {x2-1-1-2}} {x2-1-2}',
                                'pos'      => 4,
                                'absPos'   => 67,
                                'children' => [
                                    [
                                        'val'      => 'x2-1-1 {x2-1-1-1} {x2-1-1-2}',
                                        'pos'      => 6,
                                        'absPos'   => 73,
                                        'children' => [
                                            ['val' => 'x2-1-1-1', 'pos' => 8, 'absPos' => 81],
                                            ['val' => 'x2-1-1-2', 'pos' => 19, 'absPos' => 92]
                                        ]
                                    ],
                                    [
                                        'val'    => 'x2-1-2',
                                        'pos'    => 37,
                                        'absPos' => 104
                                    ]
                                ]
                            ],
                            [
                                'val'    => 'x2-2',
                                'pos'    => 51,
                                'absPos' => 114,
                            ]
                        ]
                    ],
                    [
                        'val'    => 'x3',
                        'pos'    => 122,
                        'absPos' => 122
                    ]
                ]
            ]
        ];

        $this->testBulkEquals($tests);
    }

    /**
     * @test Text::getBetween()
     *
     * @return void
     */
    public function testBetween(): void
    {
        $tests = [
            // starting by $left separator
            [
                'getBetween()',
                [Text::class, 'getBetween'],
                ['{{x1} {x2} {y1 {y2}}}', '{', '}'],
                '{x1} {x2} {y1 {y2}}'
            ],
            // starting by $left separator with line breaks
            [
                'getBetween()',
                [Text::class, 'getBetween'],
                ["{{x1} {x2} \n{y1 \n{\ny2}}}", '{', '}'],
                "{x1} {x2} \n{y1 \n{\ny2}}"
            ],
            // separators as emojis for UTF8
            [
                'getBetween()',
                [Text::class, 'getBetween'],
                ["are 😋 you OK ? 😀 #ok !", '😋', '😀'],
                " you OK ? "
            ],
            [
                'getBetween()',
                [Text::class, 'getBetween'],
                ['{{x1} {x2} {y1 {y2}}}', '{{', '}}'],
                'x1} {x2} {y1 {y2'
            ],
            [
                'getBetween()',
                [Text::class, 'getBetween'],
                ['{{x1} {x2} {y1 {y2}}}', '{{ ', ' }}'],
                null
            ],
            [
                'getBetween()',
                [Text::class, 'getBetween'],
                ['{{ x1} { }}x2} {y1 {y2}}}', '{{ ', ' }}'],
                'x1} {'
            ],
            // not starting by $left separator
            [
                'getBetween()',
                [Text::class, 'getBetween'],
                ['xx {{x1} {x2} {y1 {y2}}}', '{', '}'],
                '{x1} {x2} {y1 {y2}}'
            ],
            [
                'getBetween()',
                [Text::class, 'getBetween'],
                ['xx {{x1} {x2} {y1 {y2}}}', '{{', '}}'],
                'x1} {x2} {y1 {y2'
            ],
            [
                'getBetween()',
                [Text::class, 'getBetween'],
                ['xx {{x1} {x2} {y1 {y2}}}', '{{ ', ' }}'],
                null
            ],
            [
                'getBetween()',
                [Text::class, 'getBetween'],
                ['xx {{ x1} { }}x2} {y1 {y2}}}', '{{ ', ' }}'],
                'x1} {'
            ],
            // not ending by $left separator
            [
                'getBetween()',
                [Text::class, 'getBetween'],
                ['xx {{x1} {x2} {y1 {y2}}} yyy', '{', '}'],
                '{x1} {x2} {y1 {y2}}'
            ],
            [
                'getBetween()',
                [Text::class, 'getBetween'],
                ['xx {{x1} {x2} {y1 {y2}}} yyy', '{{', '}}'],
                'x1} {x2} {y1 {y2'
            ],
            [
                'getBetween()',
                [Text::class, 'getBetween'],
                ['xx {{x1} {x2} {y1 {y2}}} yyy', '{{ ', ' }}'],
                null
            ],
            [
                'getBetween()',
                [Text::class, 'getBetween'],
                ['xx {{ x1} { }}x2} {y1 {y2}}} yyy', '{{ ', ' }}'],
                'x1} {'
            ],
            // for comments with full utf8
            [
                'getBetween()',
                [Text::class, 'getBetween'],
                [
                    '🦄 this Дерипаска бандит is /* 😋 one Дерипаска бандит # string with // nothing to 😋*/',
                    '/*',
                    '*/'
                ],
                ' 😋 one Дерипаска бандит # string with // nothing to 😋'
            ],
            [
                'getBetween()',
                [Text::class, 'getBetween'],
                [
                    '🦄 this Дерипаска бандит is /* /*😋 one Дерипаска бандит # string*/ with // nothing to 😋*/',
                    '/*',
                    '*/'
                ],
                ' /*😋 one Дерипаска бандит # string*/ with // nothing to 😋'
            ],
            // with 2 $left and 1 $right
            [
                'getBetween()',
                [Text::class, 'getBetween'],
                [
                    '🦄 this Дерипаска бандит is /* /*😋 one Дерипаска бандит # string*/ with // nothing to 😋',
                    '/*',
                    '*/'
                ],
                ' /*😋 one Дерипаска бандит # string'
            ],
            // with 1 $left and 2 $right
            [
                'getBetween()',
                [Text::class, 'getBetween'],
                ['xx {x1} cc } yyy', '{', '}'],
                'x1'
            ],
            // with 2 $left and 0 $right
            [
                'getBetween()',
                [Text::class, 'getBetween'],
                ['xx {x1 cc { yyy', '{', '}'],
                null
            ],
            // with 0 $left and 2 $right
            [
                'getBetween()',
                [Text::class, 'getBetween'],
                ['xx x1 cc }} yyy', '{', '}'],
                null
            ],
            // with different sizes for $left and $right
            [
                'getBetween()',
                [Text::class, 'getBetween'],
                ['xx /*x1 cc */$$yyy***/ ', '/*', '***/'],
                'x1 cc */$$yyy'
            ],
            // with multiple tags - only return the first one
            [
                'getBetween()',
                [Text::class, 'getBetween'],
                ['xx {😋x1}} cc {x2}} aa {x3}}', '{', '}}'],
                '😋x1'
            ]
        ];

        $this->testBulkEquals($tests);
    }

    /**
     * @test Text::startsWith
     *
     * @return void
     */
    public function testStartsWith(): void
    {
        $tests = [
            // starting by $left separator
            [
                'startsWith()',
                [Text::class, 'startsWith'],
                ['🤬 Απαγορεύει ο κανονισμός', '🤬 Απαγορεύει'],
                true
            ],
            [
                'startsWith()',
                [Text::class, 'startsWith'],
                ['🤬 Απαγορεύει ο κανονισμός', [' Απαγορεύει']],
                false
            ],
            [
                'startsWith()',
                [Text::class, 'startsWith'],
                ['🤬 Απαγορεύει ο κανονισμός', ['🤬', ' Απαγορεύει']],
                true
            ],
            [
                'startsWith()',
                [Text::class, 'startsWith'],
                ['🤬 Απαγορεύει ο κανονισμός', null],
                false
            ],
        ];

        $this->testBulkEquals($tests);
    }

    /**
     * @test Text::endsWith
     *
     * @return void
     */
    public function testEndsWith(): void
    {
        $tests = [
            // starting by $left separator
            [
                'endsWith()',
                [Text::class, 'endsWith'],
                ['🤬 Απαγορεύει ο κανονισμός 👹', '🤬 Απαγορεύει 👹'],
                false
            ],
            [
                'endsWith()',
                [Text::class, 'endsWith'],
                ['🤬 Απαγορεύει ο κανονισμός 👹', ['👹']],
                true
            ],
            [
                'endsWith()',
                [Text::class, 'endsWith'],
                ['🤬 Απαγορεύει ο κανονισμός 👹', ['🤬', ' Απαγορεύει', '👹X']],
                false
            ],
            [
                'endsWith()',
                [Text::class, 'endsWith'],
                ['🤬 Απαγορεύει ο κανονισμός 👹', ['🤬', ' Απαγορεύει', '👹']],
                true
            ],
            [
                'endsWith()',
                [Text::class, 'endsWith'],
                ['🤬 Απαγορεύει ο κανονισμός', null],
                false
            ],
        ];

        $this->testBulkEquals($tests);
    }

    /**
     * @test Text::replaceBetween
     *
     * @return void
     */
    public function testReplaceBetween(): void
    {
        $tests = [
            [
                'replaceBetween()',
                [Text::class, 'replaceBetween'],
                ['🤬 Απαγορεύει ο κανονισμός 👹 🤬 Απαγορεύει ο κανονισμός 👹', '🤬', '👹', 'Nawak'],
                'Nawak 🤬 Απαγορεύει ο κανονισμός 👹'
            ],

            [
                'replaceBetweenAll()',
                [Text::class, 'replaceBetweenAll'],
                ['🤬 Απαγορεύει ο κανονισμός 👹 🤬 Απαγορεύει ο κανονισμός 👹', '🤬', '👹', 'Nawak'],
                'Nawak Nawak'
            ],
            [
                'replaceBetween()',
                [Text::class, 'replaceBetween'],
                ['Απαγορεύει 🤬 Απαγορεύει ο κανονισμός 👹 🤬 Απαγορεύει ο κανονισμός 👹 Απαγορεύει', '🤬', '👹', 'Nawak'],
                'Απαγορεύει Nawak 🤬 Απαγορεύει ο κανονισμός 👹 Απαγορεύει'
            ],
            [
                'replaceBetweenAll()',
                [Text::class, 'replaceBetweenAll'],
                ['Απαγορεύει 🤬 Απαγορεύει ο κανονισμός 👹 🤬 Απαγορεύει ο κανονισμός 👹 Απαγορεύει', '🤬', '👹', 'Nawak'],
                'Απαγορεύει Nawak Nawak Απαγορεύει'
            ],
            [
                'replaceBetweenAll()',
                [Text::class, 'replaceBetweenAll'],
                ['Απαγορεύει 🤬 Απαγορεύει ο κανονισμός 👹 🤬 Απαγορεύει ο κανονισμός 👹 Απαγορεύει', '🤬', '👹',
                    function (string $string, string $one) {
                        return 'Nawak';
                    }
                ],
                'Απαγορεύει Nawak Nawak Απαγορεύει'
            ],
        ];

        $this->testBulkEquals($tests);
    }

    /**
     * @test Text::ucFirst
     *
     * @return void
     */
    public function testUcFirst(): void
    {
        $tests = [
            [
                'testUcFirst()',
                [Text::class, 'ucFirst'],
                ['🤬 Απαγορεύει ο κανονισμός 👹 🤬 Απαγορεύει ο κανονισμός 👹', false],
                '🤬 Απαγορεύει ο κανονισμός 👹 🤬 Απαγορεύει ο κανονισμός 👹'
            ],
            [
                'testUcFirst()',
                [Text::class, 'ucFirst'],
                ['🤬 Απαγορεύει ο κανονισμός 👹 🤬 Απαγορεύει ο κανονισμός 👹', true],
                '🤬 απαγορεύει ο κανονισμός 👹 🤬 απαγορεύει ο κανονισμός 👹'
            ],
            [
                'testUcFirst()',
                [Text::class, 'ucFirst'],
                ['aπαγορεύει ο κανονισμός 👹 🤬 Απαγορεύει ο κανονισμός 👹', true],
                'Aπαγορεύει ο κανονισμός 👹 🤬 απαγορεύει ο κανονισμός 👹'
            ],
            [
                'testUcFirst()',
                [Text::class, 'ucFirst'],
                ['aπαγορεύει ο κανονισμός 👹 🤬 Απαγορεύει ο κανονισμός 👹', false],
                'Aπαγορεύει ο κανονισμός 👹 🤬 Απαγορεύει ο κανονισμός 👹'
            ],
            [
                'testUcFirst()',
                [Text::class, 'ucFirst'],
                ['life is whAt happens when you\'re busy making other plans. john Lennon', false],
                'Life is whAt happens when you\'re busy making other plans. john Lennon'
            ],
            [
                'testUcFirst()',
                [Text::class, 'ucFirst'],
                ['life is whAt happens when you\'re busy making other plans. john Lennon', true],
                'Life is what happens when you\'re busy making other plans. john lennon'
            ]

        ];

        $this->testBulkEquals($tests);
    }

    /**
     * @test Text::ucWords
     *
     * @return void
     */
    public function testUcWords(): void
    {
        $tests = [
            [
                'testUcWords()',
                [Text::class, 'ucWords'],
                ['🤬 ΑπΑγορεύει ο κανονισμός 👹 🤬 Απαγορεύει ο κανονισμός 👹', false],
                '🤬 ΑπΑγορεύει Ο Κανονισμός 👹 🤬 Απαγορεύει Ο Κανονισμός 👹'
            ],
            [
                'testUcWords()',
                [Text::class, 'ucWords'],
                ['🤬 ΑπΑγορεύει ο κανονισμός 👹 🤬 Απαγορεύει ο κανονισμός 👹', true],
                '🤬 Απαγορεύει Ο Κανονισμός 👹 🤬 Απαγορεύει Ο Κανονισμός 👹'
            ],
            [
                'testUcWords()',
                [Text::class, 'ucWords'],
                ['aπαγορεύει ο κανονισμός 👹 🤬 Απαγορεύει ο κανονισμός 👹', true],
                'Aπαγορεύει Ο Κανονισμός 👹 🤬 Απαγορεύει Ο Κανονισμός 👹'
            ],
            [
                'testUcWords()',
                [Text::class, 'ucWords'],
                ['aπαγορεύει ο κανονισμός 👹 🤬 Απαγορεύει ο κανονισμός 👹', false],
                'Aπαγορεύει Ο Κανονισμός 👹 🤬 Απαγορεύει Ο Κανονισμός 👹'
            ],
            [
                'testUcWords()',
                [Text::class, 'ucWords'],
                ['life is whAt happens when you\'re busy making other plans. john Lennon', false],
                'Life Is WhAt Happens When You\'re Busy Making Other Plans. John Lennon'
            ],
            [
                'testUcWords()',
                [Text::class, 'ucWords'],
                ['life is whAt happens when you\'re busy making other plans. john Lennon', true],
                'Life Is What Happens When You\'re Busy Making Other Plans. John Lennon'
            ]

        ];

        $this->testBulkEquals($tests);
    }

    /**
     * @test Text::slug
     *
     * @return void
     */
    public function testSlug(): void
    {
        $result = Text::slug('« Voix 📢 ambiguë © d’un cœur 💔 qui, au zé\'phyr, préfère les jattes de Kiwis 🥝 »');
        $this->assertEquals('Voix-ambigue-C-d-un-coeur-qui-au-ze-phyr-prefere-les-jattes-de-Kiwis', $result);
    }

    /**
     * @test Text::transliterate
     *
     * @return void
     */
    public function testTransliterate(): void
    {
        $this->assertEquals(
            '<< Voix 📢 ambigue (C) d\'un coeur 💔 qui, au ze\'phyr, prefere les jattes de Kiwis 🥝 >>',
            Text::transliterate('« Voix 📢 ambiguë © d’un cœur 💔 qui, au zé\'phyr, préfère les jattes de Kiwis 🥝 »')
        );
        $this->assertEquals(
            'A ae Ubermensch pa hoyeste niva! I a lublu PHP! est. fi ',
            Text::transliterate('A æ Übérmensch på høyeste nivå! И я люблю PHP! ест. ﬁ ¦')
        );
        $this->assertEquals(
            'posts/view/hangug-eo/page:1/sort:asc',
            Text::transliterate('posts/view/한국어/page:1/sort:asc')
        );
        $this->assertEquals(
            'non breaking space',
            Text::transliterate("non\xc2\xa0breaking\xc2\xa0space")
        );
    }

    /**
     * @test Text::isMultibyte
     *
     * @return void
     */
    public function testIsMultibyte(): void
    {
        $result = Text::isMultibyte('« Voix 📢 ambiguë © d’un cœur 💔 qui, au zé\'phyr, préfère les jattes de Kiwis 🥝 »');
        $this->assertEquals(true, $result);

        $result = Text::isMultibyte('Voix-ambigue-C-d-un-coeur-qui-au-ze-phyr-prefere-les-jattes-de-Kiwis');
        $this->assertEquals(false, $result);
    }

    /**
     * @test Text::pluralize
     *
     * @return void
     */
    public function testPluralize(): void
    {
        $inflector = InflectorFactory::createForLanguage(Language::FRENCH)->build();
        $result = $inflector->pluralize('cheval');
        $this->assertEquals('chevaux', $result);
    }

    /**
     * @test Text::singularize
     *
     * @return void
     */
    public function testSingularize(): void
    {
        $inflector = InflectorFactory::createForLanguage(Language::FRENCH)->build();
        $result = $inflector->singularize('cheval');
        $this->assertEquals('cheval', $result);
        $result = $inflector->singularize('chevaux');
        $this->assertEquals('cheval', $result);
    }

    /**
     * @test Text::tableize
     *
     * @return void
     */
    public function testTableize(): void
    {
        $inflector = InflectorFactory::createForLanguage(Language::FRENCH)->build();
        $result = $inflector->tableize('cheval🤔Blancs');
        $this->assertEquals('cheval🤔blancs', $result);
        $result = $inflector->tableize('chevalBlancs');
        $this->assertEquals('cheval_blancs', $result);
    }

    /**
     * @test Text::classify
     *
     * @return void
     */
    public function testClassify(): void
    {
        $inflector = InflectorFactory::create()->build();
        $result = $inflector->classify('model_name');
        $this->assertEquals('ModelName', $result);
    }

    /**
     * @test Text::capitalize
     *
     * @return void
     */
    public function testCapitalize(): void
    {
        $inflector = InflectorFactory::create()->build();
        $result = $inflector->capitalize('model_name');
        $this->assertEquals('Model_name', $result);
        $result = $inflector->capitalize('model name');
        $this->assertEquals('Model Name', $result);
    }

    /**
     * @test Text::unaccent
     *
     * @return void
     */
    public function testUnaccent(): void
    {
        $inflector = InflectorFactory::create()->build();
        $result = $inflector->unaccent(
            '« Voix 📢 ambiguë © d’un cœur 💔 qui, au zé\'phyr, préfère les jattes de Kiwis 🥝 »'
        );
        $this->assertEquals(
            '« Voix 📢 ambigue © d’un coeur 💔 qui, au ze\'phyr, prefere les jattes de Kiwis 🥝 »',
            $result
        );
    }

    /**
     * @test Text::urlize
     *
     * @return void
     */
    public function testUrlize(): void
    {
        $inflector = InflectorFactory::create()->build();
        $result = $inflector->urlize(
            '« Voix 📢 ambiguë © d’un cœur 💔 qui, au zé\'phyr, préfère les jattes de Kiwis 🥝 »'
        );
        $this->assertEquals(
            'voix-ambigue-d-un-coeur-qui-au-ze-phyr-prefere-les-jattes-de-kiwis',
            $result
        );
    }

    /**
     * @test Text::camelize
     *
     * @return void
     */
    public function testCamelize(): void
    {
        $inflector = InflectorFactory::create()->build();
        $result = $inflector->camelize('model_name');
        $this->assertEquals('modelName', $result);
    }

    public function testEnsure(): void
    {
        //$base = '🤬 ειρήνη και αγάπη 🤬';
        //$this->assertEquals('🤬 ειρήνη και αγάπη 🤬', Text::ensureLeading($base, '🤬', '🤬'));
        $tests = [
            [
                'ensureLeading()',
                [Text::class, 'ensureLeading'],
                ['💐 ειρήνη και αγάπη ☮️', '🤬'],
                '🤬💐 ειρήνη και αγάπη ☮️'
            ],
            [
                'ensureLeading()',
                [Text::class, 'ensureLeading'],
                ['💐 ειρήνη και αγάπη ☮️', ''],
                '💐 ειρήνη και αγάπη ☮️'
            ],
            [
                'ensureLeading()',
                [Text::class, 'ensureLeading'],
                ['💐 ειρήνη και αγάπη ☮️'],
                '💐 ειρήνη και αγάπη ☮️'
            ],
            [
                'ensureTrailing()',
                [Text::class, 'ensureTrailing'],
                ['💐 ειρήνη και αγάπη ☮️', '🤬'],
                '💐 ειρήνη και αγάπη ☮️🤬'
            ],
            [
                'ensureTrailing()',
                [Text::class, 'ensureTrailing'],
                ['💐 ειρήνη και αγάπη ☮️', ''],
                '💐 ειρήνη και αγάπη ☮️'
            ],
            [
                'ensureTrailing()',
                [Text::class, 'ensureTrailing'],
                ['💐 ειρήνη και αγάπη ☮️'],
                '💐 ειρήνη και αγάπη ☮️'
            ],
            [
                'ensureWrapping()',
                [Text::class, 'ensureWrapping'],
                ['💐 ειρήνη και αγάπη ☮️', '🤬'],
                '🤬💐 ειρήνη και αγάπη ☮️🤬'
            ],
            [
                'ensureWrapping()',
                [Text::class, 'ensureWrapping'],
                ['💐 ειρήνη και αγάπη ☮️', ''],
                '💐 ειρήνη και αγάπη ☮️'
            ],
            [
                'ensureWrapping()',
                [Text::class, 'ensureWrapping'],
                ['💐 ειρήνη και αγάπη ☮️'],
                '💐 ειρήνη και αγάπη ☮️'
            ]
        ];

        $this->testBulkEquals($tests);
    }

    public function testRemove(): void
    {
        $tests = [
            [
                'remove()',
                [Text::class, 'removeLeading'],
                ['💐 ειρήνη και αγάπη ☮️', '🤬'],
                '💐 ειρήνη και αγάπη ☮️'
            ],
            [
                'remove()',
                [Text::class, 'removeLeading'],
                ['💐 ειρήνη και αγάπη ☮️', '💐'],
                ' ειρήνη και αγάπη ☮️'
            ],
            [
                'remove()',
                [Text::class, 'removeLeading'],
                ['💐 ειρήνη και αγάπη ☮️', ['💐', ' ']],
                'ειρήνη και αγάπη ☮️'
            ],
            [
                'remove()',
                [Text::class, 'removeLeading'],
                ['💐 ειρήνη και αγάπη ☮️', ''],
                '💐 ειρήνη και αγάπη ☮️'
            ],
            [
                'remove()',
                [Text::class, 'removeLeading'],
                ['💐 ειρήνη και αγάπη ☮️'],
                '💐 ειρήνη και αγάπη ☮️'
            ],

            [
                'remove()',
                [Text::class, 'removeTrailing'],
                ['💐 ειρήνη και αγάπη ☮️', '🤬'],
                '💐 ειρήνη και αγάπη ☮️'
            ],
            [
                'remove()',
                [Text::class, 'removeTrailing'],
                ['💐 ειρήνη και αγάπη ☮️', '☮️'],
                '💐 ειρήνη και αγάπη '
            ],
            [
                'remove()',
                [Text::class, 'removeTrailing'],
                ['💐 ειρήνη και αγάπη ☮️', ['☮️', ' ']],
                '💐 ειρήνη και αγάπη'
            ],
            [
                'remove()',
                [Text::class, 'removeTrailing'],
                ['💐 ειρήνη και αγάπη ☮️', ''],
                '💐 ειρήνη και αγάπη ☮️'
            ],
            [
                'remove()',
                [Text::class, 'removeTrailing'],
                ['💐 ειρήνη και αγάπη ☮️'],
                '💐 ειρήνη και αγάπη ☮️'
            ],

            [
                'remove()',
                [Text::class, 'removeWrapping'],
                ['🤬💐 ειρήνη και αγάπη ☮️🤬🤬', '🤬'],
                '💐 ειρήνη και αγάπη ☮️🤬'
            ],
            [
                'remove()',
                [Text::class, 'removeWrapping'],
                ['💐 ειρήνη και αγάπη ☮️', '☮️'],
                '💐 ειρήνη και αγάπη '
            ],
            [
                'remove()',
                [Text::class, 'removeWrapping'],
                ['☮️💐 ειρήνη και αγάπη ☮️', ['☮️', ' ']],
                '💐 ειρήνη και αγάπη'
            ],
            [
                'remove()',
                [Text::class, 'removeWrapping'],
                ['💐 ειρήνη και αγάπη ☮️', ''],
                '💐 ειρήνη και αγάπη ☮️'
            ],
            [
                'remove()',
                [Text::class, 'removeWrapping'],
                ['💐 ειρήνη και αγάπη ☮️'],
                '💐 ειρήνη και αγάπη ☮️'
            ]
        ];

        $this->testBulkEquals($tests);
    }
    public function testContains(): void
    {

        $tests = [
            [
                'contains()',
                [Text::class, 'containsOne'],
                ['💐 ειρήνη και αγάπη ☮️', ['🤬', 'e']],
                false
            ],
            [
                'contains()',
                [Text::class, 'containsOne'],
                ['💐 ειρήνη και αγάπη ☮️', ['☮️', 'e']],
                true
            ],
            [
                'contains()',
                [Text::class, 'containsOne'],
                ['💐 ειρήνη και e αγάπη ☮️', ['x', 'e']],
                true
            ],
        ];

        $this->testBulkEquals($tests);
    }

    public function testReplaceNl(): void
    {
        $actual = Text::replaceNl("Hello\nWorld\r\nI am\rhere", '***');
        $this->assertEquals("Hello***World***I am***here", $actual);
    }

    public function testExplode(): void
    {
        $actual = Text::explodeMultiple("Hello World, Superman was here|but he left-too bad !", [',', '|', '-']);
        $this->assertEquals(['Hello World', ' Superman was here', 'but he left', 'too bad !'], $actual);

        $actual = Text::explodeMultiple("Hello World, Superman was here|but he ~ left-too bad !", [',', '|', '-', '~']);
        $this->assertEquals(['Hello World', ' Superman was here', 'but he ', ' left', 'too bad !'], $actual);

        $expected = [
            ['Hello World', 0],
            [' Superman was here', 12],
            ['but he ', 31],
            [' left', 39],
            ['too bad !', 45]
        ];
        $actual = Text::explodeMultiple(
            "Hello World, Superman was here|but he ~ left-too bad !",
            [',', '|', '-', '~'],
            ['captureOffset' => true]
        );

        $this->assertEquals($expected, $actual);

        $expected = [
            'Hello World', ',',
            ' Superman was here', '', '|',
            'but he ', '', '', '',
            '~', ' left', '', '',
            '-', 'too bad !'
        ];
        $actual = Text::explodeMultiple(
            "Hello World, Superman was here|but he ~ left-too bad !",
            [',', '|', '-', '~'],
            ['captureDelimiter' => true]
        );
        $this->assertEquals($expected, $actual);
    }

    public function testQuoteLabel(): void
    {
        $expected = 'Hello «World», Superman was here';
        $actual = 'Hello ' . Text::quoteLabel('World', '«%s»') . ', Superman was here';
        $this->assertEquals($expected, $actual);

        $expected = 'Hello « World », Superman was here';
        $actual = 'Hello ' . Text::quoteLabel('World') . ', Superman was here';
        $this->assertEquals($expected, $actual);
    }

    public function testReversePrintR(): void
    {
        $expected1 = ['x', 'entity' => [
            'name' => 'John',
            'age' => 25,
            'address' => [
                'street' => '5th Avenue',
                'city' => 'New York'
            ]
        ]];

        $actual = Text::reversePrintR(print_r($expected1, true));
        $this->assertEquals($expected1, $actual);

        $expected2 = ['x', 'entity' => new Entity([
            'name' => 'John',
            'age' => 25,
            'address' => [
                'street' => '5th Avenue',
                'city' => 'New York'
            ]
        ])];

        $actual = Text::reversePrintR(print_r($expected2, true));

        $expected2['entity'] = $expected2['entity']->toArray() +
            [
                '[new]'        => 1,
                '[accessible]' => [
                    '*'                => 1,
                ],
                '[dirty]'          => [
                    'name'    => 1,
                    'age'     => 1,
                    'address' => 1,
                ],
                '[original]'       => [],
                '[originalFields]' => ['name', 'age', 'address'],
                '[virtual]'        => [],
                '[hasErrors]'      => null,
                '[errors]'         => [],
                '[invalid]'        => [],
                '[repository]'     => null
            ];

        $this->assertEquals($expected2, $actual);
        $this->assertEquals('', Text::reversePrintR(''));
    }

    public function testIsSomething(): void
    {
        $value = 'xxx';
        $this->assertEquals(false, Text::isJson($value));

        $value = '';
        $this->assertEquals(false, Text::isJson($value));

        $value = json_encode([0, 1, 2]);
        // @phpstan-ignore-next-line - json_encode will not return false
        $this->assertEquals([0, 1, 2], Text::isJson($value));

        $value = '2024-01-01 00:00:00';
        $this->assertEquals(true, Text::isDate($value, 'Y-m-d H:i:s'));

        $value = 'noDate';
        $this->assertEquals(false, Text::isDate($value, 'Y-m-d H:i:s'));

        $value = 'x' . chr(0);
        $this->assertEquals(false, Text::isDate($value));

        $this->assertEquals(false, Text::isSerialized('$value'));
        $this->assertEquals(false, Text::isSerialized($value));
        $this->assertEquals(true, Text::isSerialized(serialize('xxx')));
    }

    public function testExtractBoolean(): void
    {
        $this->assertEquals(true, Text::extractBoolean('true'));
        $this->assertEquals(false, Text::extractBoolean(''));
        $this->assertEquals(false, Text::extractBoolean('true' . chr(0)));
        $this->assertEquals(true, Text::extractBoolean('oui'));
        $this->assertEquals(false, Text::extractBoolean('false'));
    }

    public function testConcatenate(): void
    {
        $tests = [
            [
                'concatenate()',
                [Text::class, 'concatenate'],
                [' : ', 'first', 'second'],
                'first : second'
            ],
            [
                'concatenate()',
                [Text::class, 'concatenate'],
                [[' : ', '-'], 'first', 'second', 'third'],
                'first : second-third'
            ],
            [
                'concatenate()',
                [Text::class, 'concatenate'],
                [[':', '::'], 'first', 'second', 'third', 'fourth'],
                'first:second::third::fourth'
            ],
            [
                'concatenate()',
                [Text::class, 'concatenate'],
                [['', '::'], 'first', 'second', 'third', 'fourth'],
                'firstsecond::third::fourth'
            ]
        ];

        $this->testBulkEquals($tests);
    }

    public function testParseSizeToBytes(): void
    {
        $tests = [
            [
                'parseSizeToBytes()',
                [Text::class, 'parseSizeToBytes'],
                ['15G'],
                15 * 1024 * 1024 * 1024
            ],
            [
                'parseSizeToBytes()',
                [Text::class, 'parseSizeToBytes'],
                ['15GB'],
                15 * 1024 * 1024 * 1024
            ],
            [
                'parseSizeToBytes()',
                [Text::class, 'parseSizeToBytes'],
                [''],
                -1
            ],
            [
                'parseSizeToBytes()',
                [Text::class, 'parseSizeToBytes'],
                ['0'],
                0
            ],
            [
                'parseSizeToBytes()',
                [Text::class, 'parseSizeToBytes'],
                ['0b'],
                0
            ],
            [
                'parseSizeToBytes()',
                [Text::class, 'parseSizeToBytes'],
                ['512B'],
                512
            ],
            [
                'parseSizeToBytes()',
                [Text::class, 'parseSizeToBytes'],
                ['1KB'],
                1024
            ],
            [
                'parseSizeToBytes()',
                [Text::class, 'parseSizeToBytes'],
                ['1.5GB'],
                1610612736
            ],
            [
                'parseSizeToBytes()',
                [Text::class, 'parseSizeToBytes'],
                ['2XB'],
                -1
            ]
        ];

        $this->testBulkEquals($tests);
    }

    public function testParseBytesToSize(): void
    {
        $tests = [
            [
                'parseBytesToSize()',
                [Text::class, 'parseBytesToSize'],
                [15 * 1024 * 1024 * 1024],
                '15GB'
            ],
            [
                'parseBytesToSize()',
                [Text::class, 'parseBytesToSize'],
                [15.1 * 1024 * 1024 * 1024, 2],
                '15.1GB'
            ],
            [
                'parseBytesToSize()',
                [Text::class, 'parseBytesToSize'],
                [102578],
                '100KB'
            ],
            [
                'parseBytesToSize()',
                [Text::class, 'parseBytesToSize'],
                [108578, 3],
                '106.033KB'
            ],
            [
                'parseBytesToSize()',
                [Text::class, 'parseBytesToSize'],
                [0],
                '0B'
            ],
            [
                'parseBytesToSize()',
                [Text::class, 'parseBytesToSize'],
                [PHP_INT_MAX],
                '8EB'
            ],
            [
                'parseBytesToSize()',
                [Text::class, 'parseBytesToSize'],
                [5],
                '5B'
            ],
        ];

        $this->testBulkEquals($tests);
    }

    public function testSafePrintF(): void
    {
        $expected = 'Hello World';

        $actual = Text::safePrintF('Hello %s', ['World']);
        $this->assertEquals($expected, $actual);

        $actual = Text::safePrintF('Hello %s', ['World', 'x']);
        $this->assertEquals($expected, $actual);

        $actual = Text::safePrintF('Hello %s', ['World', 'x', 'y']);
        $this->assertEquals($expected, $actual);

        $actual = Text::safePrintF('Hello %s', ['World', 'x', 'y', 'z']);
        $this->assertEquals($expected, $actual);

        $actual = Text::safePrintF('Hello %s', ['World', 'x', 'y', 'z', null]);
        $this->assertEquals($expected, $actual);
    }

    public function testAscii(): void
    {
        // reproduce CakPHP tests
        $input = [33, 34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48, 49, 50, 51, 52, 53, 54, 55, 56, 57,
                  58, 59, 60, 61, 62, 63, 64, 65, 66, 67, 68, 69, 70, 71, 72, 73, 74, 75, 76, 77, 78, 79, 80, 81, 82,
                  83, 84, 85, 86, 87, 88, 89, 90, 91, 92, 93, 94, 95, 96, 97, 98, 99, 100, 101, 102, 103, 104, 105, 106,
                  107, 108, 109, 110, 111, 112, 113, 114, 115, 116, 117, 118, 119, 120, 121, 122, 123, 124, 125, 126];
        $result = Text::ascii($input);

        $expected = '!"#$%&\'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\]^_`abcdefghijklmnopqrstuvwxyz{|}~';
        $this->assertSame($expected, $result);

        $input = [161, 162, 163, 164, 165, 166, 167, 168, 169, 170, 171, 172, 173, 174, 175, 176, 177, 178, 179, 180,
                  181, 182, 183, 184, 185, 186, 187, 188, 189, 190, 191, 192, 193, 194, 195, 196, 197, 198, 199, 200];
        $result = Text::ascii($input);

        $expected = '¡¢£¤¥¦§¨©ª«¬­®¯°±²³´µ¶·¸¹º»¼½¾¿ÀÁÂÃÄÅÆÇÈ';
        $this->assertSame($expected, $result);

        $input = [201, 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215, 216, 217, 218, 219, 220,
                  221, 222, 223, 224, 225, 226, 227, 228, 229, 230, 231, 232, 233, 234, 235, 236, 237, 238, 239, 240,
                  241, 242, 243, 244, 245, 246, 247, 248, 249, 250, 251, 252, 253, 254, 255, 256, 257, 258, 259, 260,
                  261, 262, 263, 264, 265, 266, 267, 268, 269, 270, 271, 272, 273, 274, 275, 276, 277, 278, 279, 280,
                  281, 282, 283, 284, 285, 286, 287, 288, 289, 290, 291, 292, 293, 294, 295, 296, 297, 298, 299, 300];
        $result = Text::ascii($input);
        $expected = 'ÉÊËÌÍÎÏÐÑÒÓÔÕÖ×ØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõö÷øùúûüýþÿĀāĂăĄąĆćĈĉĊċČčĎďĐđĒēĔĕĖėĘęĚěĜĝĞğĠġĢģĤĥĦħĨĩĪīĬ'; // @phpcs:ignore
        $this->assertSame($expected, $result);

        $input = [301, 302, 303, 304, 305, 306, 307, 308, 309, 310, 311, 312, 313, 314, 315, 316, 317, 318, 319, 320,
                  321, 322, 323, 324, 325, 326, 327, 328, 329, 330, 331, 332, 333, 334, 335, 336, 337, 338, 339, 340,
                  341, 342, 343, 344, 345, 346, 347, 348, 349, 350, 351, 352, 353, 354, 355, 356, 357, 358, 359, 360,
                  361, 362, 363, 364, 365, 366, 367, 368, 369, 370, 371, 372, 373, 374, 375, 376, 377, 378, 379, 380,
                  381, 382, 383, 384, 385, 386, 387, 388, 389, 390, 391, 392, 393, 394, 395, 396, 397, 398, 399, 400];
        $expected = 'ĭĮįİıĲĳĴĵĶķĸĹĺĻļĽľĿŀŁłŃńŅņŇňŉŊŋŌōŎŏŐőŒœŔŕŖŗŘřŚśŜŝŞşŠšŢţŤťŦŧŨũŪūŬŭŮůŰűŲųŴŵŶŷŸŹźŻżŽžſƀƁƂƃƄƅƆƇƈƉƊƋƌƍƎƏƐ'; // @phpcs:ignore
        $result = Text::ascii($input);
        $this->assertSame($expected, $result);

        $input = [401, 402, 403, 404, 405, 406, 407, 408, 409, 410, 411, 412, 413, 414, 415, 416, 417, 418, 419, 420,
                  421, 422, 423, 424, 425, 426, 427, 428, 429, 430, 431, 432, 433, 434, 435, 436, 437, 438, 439, 440,
                  441, 442, 443, 444, 445, 446, 447, 448, 449, 450, 451, 452, 453, 454, 455, 456, 457, 458, 459, 460,
                  461, 462, 463, 464, 465, 466, 467, 468, 469, 470, 471, 472, 473, 474, 475, 476, 477, 478, 479, 480,
                  481, 482, 483, 484, 485, 486, 487, 488, 489, 490, 491, 492, 493, 494, 495, 496, 497, 498, 499, 500];
        $expected = 'ƑƒƓƔƕƖƗƘƙƚƛƜƝƞƟƠơƢƣƤƥƦƧƨƩƪƫƬƭƮƯưƱƲƳƴƵƶƷƸƹƺƻƼƽƾƿǀǁǂǃǄǅǆǇǈǉǊǋǌǍǎǏǐǑǒǓǔǕǖǗǘǙǚǛǜǝǞǟǠǡǢǣǤǥǦǧǨǩǪǫǬǭǮǯǰǱǲǳǴ'; // @phpcs:ignore
        $result = Text::ascii($input);
        $this->assertSame($expected, $result);

        $input = [601, 602, 603, 604, 605, 606, 607, 608, 609, 610, 611, 612, 613, 614, 615, 616, 617, 618, 619, 620,
                  621, 622, 623, 624, 625, 626, 627, 628, 629, 630, 631, 632, 633, 634, 635, 636, 637, 638, 639, 640,
                  641, 642, 643, 644, 645, 646, 647, 648, 649, 650, 651, 652, 653, 654, 655, 656, 657, 658, 659, 660,
                  661, 662, 663, 664, 665, 666, 667, 668, 669, 670, 671, 672, 673, 674, 675, 676, 677, 678, 679, 680,
                  681, 682, 683, 684, 685, 686, 687, 688, 689, 690, 691, 692, 693, 694, 695, 696, 697, 698, 699, 700];
        $expected = 'əɚɛɜɝɞɟɠɡɢɣɤɥɦɧɨɩɪɫɬɭɮɯɰɱɲɳɴɵɶɷɸɹɺɻɼɽɾɿʀʁʂʃʄʅʆʇʈʉʊʋʌʍʎʏʐʑʒʓʔʕʖʗʘʙʚʛʜʝʞʟʠʡʢʣʤʥʦʧʨʩʪʫʬʭʮʯʰʱʲʳʴʵʶʷʸʹʺʻʼ'; // @phpcs:ignore
        $result = Text::ascii($input);
        $this->assertSame($expected, $result);

        $input = [1024, 1025, 1026, 1027, 1028, 1029, 1030, 1031, 1032, 1033, 1034, 1035, 1036, 1037, 1038, 1039, 1040,
                  1041, 1042, 1043, 1044, 1045, 1046, 1047, 1048, 1049, 1050, 1051];
        $expected = 'ЀЁЂЃЄЅІЇЈЉЊЋЌЍЎЏАБВГДЕЖЗИЙКЛ';
        $result = Text::ascii($input);
        $this->assertSame($expected, $result);

        $input = [1052, 1053, 1054, 1055, 1056, 1057, 1058, 1059, 1060, 1061, 1062, 1063, 1064, 1065, 1066, 1067, 1068,
                  1069, 1070, 1071, 1072, 1073, 1074, 1075, 1076, 1077, 1078, 1079, 1080, 1081, 1082, 1083, 1084, 1085,
                  1086, 1087, 1088, 1089, 1090, 1091, 1092, 1093, 1094, 1095, 1096, 1097, 1098, 1099, 1100];
        $expected = 'МНОПРСТУФХЦЧШЩЪЫЬЭЮЯабвгдежзийклмнопрстуфхцчшщъыь';
        $result = Text::ascii($input);
        $this->assertSame($expected, $result);

        $input = [1401, 1402, 1403, 1404, 1405, 1406, 1407];
        $expected = 'չպջռսվտ';
        $result = Text::ascii($input);
        $this->assertSame($expected, $result);

        $input = [1601, 1602, 1603, 1604, 1605, 1606, 1607, 1608, 1609, 1610, 1611, 1612, 1613, 1614, 1615];
        $expected = 'فقكلمنهوىيًٌٍَُ';
        $result = Text::ascii($input);
        $this->assertSame($expected, $result);

        $input = [10032, 10033, 10034, 10035, 10036, 10037, 10038, 10039, 10040, 10041, 10042, 10043, 10044,
                  10045, 10046, 10047, 10048, 10049, 10050, 10051, 10052, 10053, 10054, 10055, 10056, 10057,
                  10058, 10059, 10060, 10061, 10062, 10063, 10064, 10065, 10066, 10067, 10068, 10069, 10070,
                  10071, 10072, 10073, 10074, 10075, 10076, 10077, 10078];
        $expected = '✰✱✲✳✴✵✶✷✸✹✺✻✼✽✾✿❀❁❂❃❄❅❆❇❈❉❊❋❌❍❎❏❐❑❒❓❔❕❖❗❘❙❚❛❜❝❞';
        $result = Text::ascii($input);
        $this->assertSame($expected, $result);

        $input = [11904, 11905, 11906, 11907, 11908, 11909, 11910, 11911, 11912, 11913, 11914, 11915, 11916, 11917,
                  11918, 11919, 11920, 11921, 11922, 11923, 11924, 11925, 11926, 11927, 11928, 11929, 11931, 11932,
                  11933, 11934, 11935, 11936, 11937, 11938, 11939, 11940, 11941, 11942, 11943, 11944, 11945, 11946,
                  11947, 11948, 11949, 11950, 11951, 11952, 11953, 11954, 11955, 11956, 11957, 11958, 11959, 11960,
                  11961, 11962, 11963, 11964, 11965, 11966, 11967, 11968, 11969, 11970, 11971, 11972, 11973, 11974,
                  11975, 11976, 11977, 11978, 11979, 11980, 11981, 11982, 11983, 11984, 11985, 11986, 11987, 11988,
                  11989, 11990, 11991, 11992, 11993, 11994, 11995, 11996, 11997, 11998, 11999, 12000];
        $expected = '⺀⺁⺂⺃⺄⺅⺆⺇⺈⺉⺊⺋⺌⺍⺎⺏⺐⺑⺒⺓⺔⺕⺖⺗⺘⺙⺛⺜⺝⺞⺟⺠⺡⺢⺣⺤⺥⺦⺧⺨⺩⺪⺫⺬⺭⺮⺯⺰⺱⺲⺳⺴⺵⺶⺷⺸⺹⺺⺻⺼⺽⺾⺿⻀⻁⻂⻃⻄⻅⻆⻇⻈⻉⻊⻋⻌⻍⻎⻏⻐⻑⻒⻓⻔⻕⻖⻗⻘⻙⻚⻛⻜⻝⻞⻟⻠'; // @phpcs:ignore
        $result = Text::ascii($input);
        $this->assertSame($expected, $result);

        $input = [12101, 12102, 12103, 12104, 12105, 12106, 12107, 12108, 12109, 12110, 12111, 12112, 12113, 12114,
                  12115, 12116, 12117, 12118, 12119, 12120, 12121, 12122, 12123, 12124, 12125, 12126, 12127, 12128,
                  12129, 12130, 12131, 12132, 12133, 12134, 12135, 12136, 12137, 12138, 12139, 12140, 12141, 12142,
                  12143, 12144, 12145, 12146, 12147, 12148, 12149, 12150, 12151, 12152, 12153, 12154, 12155, 12156,
                  12157, 12158, 12159];
        $expected = '⽅⽆⽇⽈⽉⽊⽋⽌⽍⽎⽏⽐⽑⽒⽓⽔⽕⽖⽗⽘⽙⽚⽛⽜⽝⽞⽟⽠⽡⽢⽣⽤⽥⽦⽧⽨⽩⽪⽫⽬⽭⽮⽯⽰⽱⽲⽳⽴⽵⽶⽷⽸⽹⽺⽻⽼⽽⽾⽿';
        $result = Text::ascii($input);
        $this->assertSame($expected, $result);

        $input = [45601, 45602, 45603, 45604, 45605, 45606, 45607, 45608, 45609, 45610, 45611, 45612, 45613, 45614,
                  45615, 45616, 45617, 45618, 45619, 45620, 45621, 45622, 45623, 45624, 45625, 45626, 45627, 45628,
                  45629, 45630, 45631, 45632, 45633, 45634, 45635, 45636, 45637, 45638, 45639, 45640, 45641, 45642,
                  45643, 45644, 45645, 45646, 45647, 45648, 45649, 45650, 45651, 45652, 45653, 45654, 45655, 45656,
                  45657, 45658, 45659, 45660, 45661, 45662, 45663, 45664, 45665, 45666, 45667, 45668, 45669, 45670,
                  45671, 45672, 45673, 45674, 45675, 45676, 45677, 45678, 45679, 45680, 45681, 45682, 45683, 45684,
                  45685, 45686, 45687, 45688, 45689, 45690, 45691, 45692, 45693, 45694, 45695, 45696, 45697, 45698,
                  45699, 45700];
        $expected = '눡눢눣눤눥눦눧눨눩눪눫눬눭눮눯눰눱눲눳눴눵눶눷눸눹눺눻눼눽눾눿뉀뉁뉂뉃뉄뉅뉆뉇뉈뉉뉊뉋뉌뉍뉎뉏뉐뉑뉒뉓뉔뉕뉖뉗뉘뉙뉚뉛뉜뉝뉞뉟뉠뉡뉢뉣뉤뉥뉦뉧뉨뉩뉪뉫뉬뉭뉮뉯뉰뉱뉲뉳뉴뉵뉶뉷뉸뉹뉺뉻뉼뉽뉾뉿늀늁늂늃늄'; // @phpcs:ignore
        $result = Text::ascii($input);
        $this->assertSame($expected, $result);

        $input = [65136, 65137, 65138, 65139, 65140, 65141, 65142, 65143, 65144, 65145, 65146, 65147, 65148, 65149,
                  65150, 65151, 65152, 65153, 65154, 65155, 65156, 65157, 65158, 65159, 65160, 65161, 65162, 65163,
                  65164, 65165, 65166, 65167, 65168, 65169, 65170, 65171, 65172, 65173, 65174, 65175, 65176, 65177,
                  65178, 65179, 65180, 65181, 65182, 65183, 65184, 65185, 65186, 65187, 65188, 65189, 65190, 65191,
                  65192, 65193, 65194, 65195, 65196, 65197, 65198, 65199, 65200];
        $expected = 'ﹰﹱﹲﹳﹴ﹵ﹶﹷﹸﹹﹺﹻﹼﹽﹾﹿﺀﺁﺂﺃﺄﺅﺆﺇﺈﺉﺊﺋﺌﺍﺎﺏﺐﺑﺒﺓﺔﺕﺖﺗﺘﺙﺚﺛﺜﺝﺞﺟﺠﺡﺢﺣﺤﺥﺦﺧﺨﺩﺪﺫﺬﺭﺮﺯﺰ';
        $result = Text::ascii($input);
        $this->assertSame($expected, $result);

        $input = [65201, 65202, 65203, 65204, 65205, 65206, 65207, 65208, 65209, 65210, 65211, 65212, 65213, 65214,
                  65215, 65216,65217, 65218, 65219, 65220, 65221, 65222, 65223, 65224, 65225, 65226, 65227, 65228,
                  65229, 65230, 65231, 65232, 65233, 65234, 65235, 65236, 65237, 65238, 65239, 65240, 65241, 65242,
                  65243, 65244, 65245, 65246, 65247, 65248, 65249, 65250, 65251, 65252, 65253, 65254, 65255, 65256,
                  65257, 65258, 65259, 65260, 65261, 65262, 65263, 65264, 65265, 65266, 65267, 65268, 65269, 65270,
                  65271, 65272, 65273, 65274, 65275, 65276];
        $expected = 'ﺱﺲﺳﺴﺵﺶﺷﺸﺹﺺﺻﺼﺽﺾﺿﻀﻁﻂﻃﻄﻅﻆﻇﻈﻉﻊﻋﻌﻍﻎﻏﻐﻑﻒﻓﻔﻕﻖﻗﻘﻙﻚﻛﻜﻝﻞﻟﻠﻡﻢﻣﻤﻥﻦﻧﻨﻩﻪﻫﻬﻭﻮﻯﻰﻱﻲﻳﻴﻵﻶﻷﻸﻹﻺﻻﻼ';
        $result = Text::ascii($input);
        $this->assertSame($expected, $result);

        $input = [65345, 65346, 65347, 65348, 65349, 65350, 65351, 65352, 65353, 65354, 65355, 65356, 65357, 65358,
                  65359, 65360, 65361, 65362, 65363, 65364, 65365, 65366, 65367, 65368, 65369, 65370];
        $expected = 'ａｂｃｄｅｆｇｈｉｊｋｌｍｎｏｐｑｒｓｔｕｖｗｘｙｚ';
        $result = Text::ascii($input);
        $this->assertSame($expected, $result);

        $input = [65377, 65378, 65379, 65380, 65381, 65382, 65383, 65384, 65385, 65386, 65387, 65388, 65389, 65390,
                  65391, 65392, 65393, 65394, 65395, 65396, 65397, 65398, 65399, 65400];
        $expected = '｡｢｣､･ｦｧｨｩｪｫｬｭｮｯｰｱｲｳｴｵｶｷｸ';
        $result = Text::ascii($input);
        $this->assertSame($expected, $result);

        $input = [65401, 65402, 65403, 65404, 65405, 65406, 65407, 65408, 65409, 65410, 65411, 65412, 65413, 65414,
                  65415, 65416, 65417, 65418, 65419, 65420, 65421, 65422, 65423, 65424, 65425, 65426, 65427, 65428,
                  65429, 65430, 65431, 65432, 65433, 65434, 65435, 65436, 65437, 65438];
        $expected = 'ｹｺｻｼｽｾｿﾀﾁﾂﾃﾄﾅﾆﾇﾈﾉﾊﾋﾌﾍﾎﾏﾐﾑﾒﾓﾔﾕﾖﾗﾘﾙﾚﾛﾜﾝﾞ';
        $result = Text::ascii($input);
        $this->assertSame($expected, $result);

        $input = [292, 275, 314, 316, 335, 44, 32, 372, 337, 345, 316, 271, 33];
        $expected = 'Ĥēĺļŏ, Ŵőřļď!';
        $result = Text::ascii($input);
        $this->assertSame($expected, $result);

        $input = [72, 101, 108, 108, 111, 44, 32, 87, 111, 114, 108, 100, 33];
        $expected = 'Hello, World!';
        $result = Text::ascii($input);
        $this->assertSame($expected, $result);

        $input = [168];
        $expected = '¨';
        $result = Text::ascii($input);
        $this->assertSame($expected, $result);

        $input = [191];
        $expected = '¿';
        $result = Text::ascii($input);
        $this->assertSame($expected, $result);

        $input = [269, 105, 110, 105];
        $expected = 'čini';
        $result = Text::ascii($input);
        $this->assertSame($expected, $result);

        $input = [109, 111, 263, 105];
        $expected = 'moći';
        $result = Text::ascii($input);
        $this->assertSame($expected, $result);

        $input = [100, 114, 382, 97, 118, 110, 105];
        $expected = 'državni';
        $result = Text::ascii($input);
        $this->assertSame($expected, $result);

        $input = [25226, 30334, 24230, 35774, 20026, 39318, 39029];
        $expected = '把百度设为首页';
        $result = Text::ascii($input);
        $this->assertSame($expected, $result);

        $input = [19968, 20108, 19977, 21608, 27704, 40845];
        $expected = '一二三周永龍';
        $result = Text::ascii($input);
        $this->assertSame($expected, $result);

        $input = [1280, 1282, 1284, 1286, 1288, 1290, 1292, 1294, 1296, 1298];
        $expected = 'ԀԂԄԆԈԊԌԎԐԒ';
        $result = Text::ascii($input);
        $this->assertSame($expected, $result);

        $input = [1281, 1283, 1285, 1287, 1289, 1291, 1293, 1295, 1296, 1298];
        $expected = 'ԁԃԅԇԉԋԍԏԐԒ';
        $result = Text::ascii($input);
        $this->assertSame($expected, $result);

        $input = [1329, 1330, 1331, 1332, 1333, 1334, 1335, 1336, 1337, 1338, 1339, 1340, 1341, 1342, 1343, 1344, 1345,
                  1346, 1347, 1348, 1349, 1350, 1351, 1352, 1353, 1354, 1355, 1356, 1357, 1358, 1359, 1360, 1361, 1362,
                  1363, 1364, 1365, 1366, 1415];
        $result = Text::ascii($input);
        $expected = 'ԱԲԳԴԵԶԷԸԹԺԻԼԽԾԿՀՁՂՃՄՅՆՇՈՉՊՋՌՍՎՏՐՑՒՓՔՕՖև';
        $this->assertSame($expected, $result);

        $input = [1377, 1378, 1379, 1380, 1381, 1382, 1383, 1384, 1385, 1386, 1387, 1388, 1389, 1390, 1391, 1392, 1393,
                  1394, 1395, 1396, 1397, 1398, 1399, 1400, 1401, 1402, 1403, 1404, 1405, 1406, 1407, 1408, 1409, 1410,
                  1411, 1412, 1413, 1414, 1415];
        $result = Text::ascii($input);
        $expected = 'աբգդեզէըթժիլխծկհձղճմյնշոչպջռսվտրցւփքօֆև';
        $this->assertSame($expected, $result);

        $input = [4256, 4257, 4258, 4259, 4260, 4261, 4262, 4263, 4264, 4265, 4266, 4267, 4268, 4269, 4270, 4271, 4272,
                  4273, 4274, 4275, 4276, 4277, 4278, 4279, 4280, 4281, 4282, 4283, 4284, 4285, 4286, 4287, 4288, 4289,
                  4290, 4291, 4292, 4293];
        $result = Text::ascii($input);
        $expected = 'ႠႡႢႣႤႥႦႧႨႩႪႫႬႭႮႯႰႱႲႳႴႵႶႷႸႹႺႻႼႽႾႿჀჁჂჃჄჅ';
        $this->assertSame($expected, $result);

        $input = [7680, 7682, 7684, 7686, 7688, 7690, 7692, 7694, 7696, 7698, 7700, 7702, 7704, 7706, 7708, 7710, 7712,
                  7714, 7716, 7718, 7720, 7722, 7724, 7726, 7728, 7730, 7732, 7734, 7736, 7738, 7740, 7742, 7744, 7746,
                  7748, 7750, 7752, 7754, 7756, 7758, 7760, 7762, 7764, 7766, 7768, 7770, 7772, 7774, 7776, 7778, 7780,
                  7782, 7784, 7786, 7788, 7790, 7792, 7794, 7796, 7798, 7800, 7802, 7804, 7806, 7808, 7810, 7812, 7814,
                  7816, 7818, 7820, 7822, 7824, 7826, 7828, 7830, 7831, 7832, 7833, 7834, 7840, 7842, 7844, 7846, 7848,
                  7850, 7852, 7854, 7856, 7858, 7860, 7862, 7864, 7866, 7868, 7870, 7872, 7874, 7876, 7878, 7880, 7882,
                  7884, 7886, 7888, 7890, 7892, 7894, 7896, 7898, 7900, 7902, 7904, 7906, 7908, 7910, 7912, 7914, 7916,
                  7918, 7920, 7922, 7924, 7926, 7928];
        $result = Text::ascii($input);
        $expected = 'ḀḂḄḆḈḊḌḎḐḒḔḖḘḚḜḞḠḢḤḦḨḪḬḮḰḲḴḶḸḺḼḾṀṂṄṆṈṊṌṎṐṒṔṖṘṚṜṞṠṢṤṦṨṪṬṮṰṲṴṶṸṺṼṾẀẂẄẆẈẊẌẎẐẒẔẖẗẘẙẚẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼẾỀỂỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪỬỮỰỲỴỶỸ'; // @phpcs:ignore
        $this->assertSame($expected, $result);

        $input = [7681, 7683, 7685, 7687, 7689, 7691, 7693, 7695, 7697, 7699, 7701, 7703, 7705, 7707, 7709, 7711, 7713,
                  7715, 7717, 7719, 7721, 7723, 7725, 7727, 7729, 7731, 7733, 7735, 7737, 7739, 7741, 7743, 7745, 7747,
                  7749, 7751, 7753, 7755, 7757, 7759, 7761, 7763, 7765, 7767, 7769, 7771, 7773, 7775, 7777, 7779, 7781,
                  7783, 7785, 7787, 7789, 7791, 7793, 7795, 7797, 7799, 7801, 7803, 7805, 7807, 7809, 7811, 7813, 7815,
                  7817, 7819, 7821, 7823, 7825, 7827, 7829, 7830, 7831, 7832, 7833, 7834, 7841, 7843, 7845, 7847, 7849,
                  7851, 7853, 7855, 7857, 7859, 7861, 7863, 7865, 7867, 7869, 7871, 7873, 7875, 7877, 7879, 7881, 7883,
                  7885, 7887, 7889, 7891, 7893, 7895, 7897, 7899, 7901, 7903, 7905, 7907, 7909, 7911, 7913, 7915, 7917,
                  7919, 7921, 7923, 7925, 7927, 7929];
        $result = Text::ascii($input);
        $expected = 'ḁḃḅḇḉḋḍḏḑḓḕḗḙḛḝḟḡḣḥḧḩḫḭḯḱḳḵḷḹḻḽḿṁṃṅṇṉṋṍṏṑṓṕṗṙṛṝṟṡṣṥṧṩṫṭṯṱṳṵṷṹṻṽṿẁẃẅẇẉẋẍẏẑẓẕẖẗẘẙẚạảấầẩẫậắằẳẵặẹẻẽếềểễệỉịọỏốồổỗộớờởỡợụủứừửữựỳỵỷỹ'; // @phpcs:ignore
        $this->assertSame($expected, $result);

        $input = [8486, 8490, 8491, 8498];
        $result = Text::ascii($input);
        $expected = 'ΩKÅℲ';
        $this->assertSame($expected, $result);

        $input = [969, 107, 229, 8526];
        $result = Text::ascii($input);
        $expected = 'ωkåⅎ';
        $this->assertSame($expected, $result);

        $input = [8544, 8545, 8546, 8547, 8548, 8549, 8550, 8551, 8552, 8553, 8554, 8555, 8556, 8557, 8558, 8559, 8579];
        $result = Text::ascii($input);
        $expected = 'ⅠⅡⅢⅣⅤⅥⅦⅧⅨⅩⅪⅫⅬⅭⅮⅯↃ';
        $this->assertSame($expected, $result);

        $input = [8560, 8561, 8562, 8563, 8564, 8565, 8566, 8567, 8568, 8569, 8570, 8571, 8572, 8573, 8574, 8575, 8580];
        $result = Text::ascii($input);
        $expected = 'ⅰⅱⅲⅳⅴⅵⅶⅷⅸⅹⅺⅻⅼⅽⅾⅿↄ';
        $this->assertSame($expected, $result);

        $input = [9398, 9399, 9400, 9401, 9402, 9403, 9404, 9405, 9406, 9407, 9408, 9409, 9410, 9411, 9412, 9413, 9414,
                  9415, 9416, 9417, 9418, 9419, 9420, 9421, 9422, 9423];
        $result = Text::ascii($input);
        $expected = 'ⒶⒷⒸⒹⒺⒻⒼⒽⒾⒿⓀⓁⓂⓃⓄⓅⓆⓇⓈⓉⓊⓋⓌⓍⓎⓏ';
        $this->assertSame($expected, $result);

        $input = [9424, 9425, 9426, 9427, 9428, 9429, 9430, 9431, 9432, 9433, 9434, 9435, 9436, 9437, 9438, 9439, 9440,
                  9441, 9442, 9443, 9444, 9445, 9446, 9447, 9448, 9449];
        $result = Text::ascii($input);
        $expected = 'ⓐⓑⓒⓓⓔⓕⓖⓗⓘⓙⓚⓛⓜⓝⓞⓟⓠⓡⓢⓣⓤⓥⓦⓧⓨⓩ';
        $this->assertSame($expected, $result);

        $input = [11264, 11265, 11266, 11267, 11268, 11269, 11270, 11271, 11272, 11273, 11274, 11275, 11276, 11277,
                  11278, 11279, 11280, 11281, 11282, 11283, 11284, 11285, 11286, 11287, 11288, 11289, 11290, 11291,
                  11292, 11293, 11294, 11295, 11296, 11297, 11298, 11299, 11300, 11301, 11302, 11303, 11304, 11305,
                  11306, 11307, 11308, 11309, 11310];
        $result = Text::ascii($input);
        $expected = 'ⰀⰁⰂⰃⰄⰅⰆⰇⰈⰉⰊⰋⰌⰍⰎⰏⰐⰑⰒⰓⰔⰕⰖⰗⰘⰙⰚⰛⰜⰝⰞⰟⰠⰡⰢⰣⰤⰥⰦⰧⰨⰩⰪⰫⰬⰭⰮ';
        $this->assertSame($expected, $result);

        $input = [11312, 11313, 11314, 11315, 11316, 11317, 11318, 11319, 11320, 11321, 11322, 11323, 11324, 11325,
                  11326, 11327, 11328, 11329, 11330, 11331, 11332, 11333, 11334, 11335, 11336, 11337, 11338, 11339,
                  11340, 11341, 11342, 11343, 11344, 11345, 11346, 11347, 11348, 11349, 11350, 11351, 11352, 11353,
                  11354, 11355, 11356, 11357, 11358];
        $result = Text::ascii($input);
        $expected = 'ⰰⰱⰲⰳⰴⰵⰶⰷⰸⰹⰺⰻⰼⰽⰾⰿⱀⱁⱂⱃⱄⱅⱆⱇⱈⱉⱊⱋⱌⱍⱎⱏⱐⱑⱒⱓⱔⱕⱖⱗⱘⱙⱚⱛⱜⱝⱞ';
        $this->assertSame($expected, $result);

        $input = [11392, 11394, 11396, 11398, 11400, 11402, 11404, 11406, 11408, 11410, 11412, 11414, 11416, 11418,
                  11420, 11422, 11424, 11426, 11428, 11430, 11432, 11434, 11436, 11438, 11440, 11442, 11444, 11446,
                  11448, 11450, 11452, 11454, 11456, 11458, 11460, 11462, 11464, 11466, 11468, 11470, 11472, 11474,
                  11476, 11478, 11480, 11482, 11484, 11486, 11488, 11490];
        $result = Text::ascii($input);
        $expected = 'ⲀⲂⲄⲆⲈⲊⲌⲎⲐⲒⲔⲖⲘⲚⲜⲞⲠⲢⲤⲦⲨⲪⲬⲮⲰⲲⲴⲶⲸⲺⲼⲾⳀⳂⳄⳆⳈⳊⳌⳎⳐⳒⳔⳖⳘⳚⳜⳞⳠⳢ';
        $this->assertSame($expected, $result);

        $input = [11393, 11395, 11397, 11399, 11401, 11403, 11405, 11407, 11409, 11411, 11413, 11415, 11417, 11419,
                  11421, 11423, 11425, 11427, 11429, 11431, 11433, 11435, 11437, 11439, 11441, 11443, 11445, 11447,
                  11449, 11451, 11453, 11455, 11457, 11459, 11461, 11463, 11465, 11467, 11469, 11471, 11473, 11475,
                  11477, 11479, 11481, 11483, 11485, 11487, 11489, 11491];
        $result = Text::ascii($input);
        $expected = 'ⲁⲃⲅⲇⲉⲋⲍⲏⲑⲓⲕⲗⲙⲛⲝⲟⲡⲣⲥⲧⲩⲫⲭⲯⲱⲳⲵⲷⲹⲻⲽⲿⳁⳃⳅⳇⳉⳋⳍⳏⳑⳓⳕⳗⳙⳛⳝⳟⳡⳣ';
        $this->assertSame($expected, $result);

        $input = [64256, 64257, 64258, 64259, 64260, 64261, 64262, 64275, 64276, 64277, 64278, 64279];
        $result = Text::ascii($input);
        $expected = 'ﬀﬁﬂﬃﬄﬅﬆﬓﬔﬕﬖﬗ';
        $this->assertSame($expected, $result);
    }

    public function testTransliterator(): void
    {
        // reproduce CakPHP tests
        $this->assertNull(Text::getTransliterator());

        $transliterator = Transliterator::createFromRules('
            $nonletter = [:^Letter:];
            $nonletter → \'*\';
            ::Latin-ASCII;
        ');
        $this->assertInstanceOf(Transliterator::class, $transliterator);
        Text::setTransliterator($transliterator);
        $this->assertSame($transliterator, Text::getTransliterator());
    }

    public function testTransliteratorId(): void
    {
        // reproduce CakPHP tests
        $defaultTransliteratorId = 'Any-Latin; Latin-ASCII; [\u0080-\u7fff] remove';
        $this->assertSame($defaultTransliteratorId, Text::getTransliteratorId());

        $expected = 'Latin-ASCII;[\u0080-\u7fff] remove';
        Text::setTransliteratorId($expected);
        $this->assertSame($expected, Text::getTransliteratorId());

        $this->assertInstanceOf(Transliterator::class, Text::getTransliterator());
        $this->assertSame($expected, Text::getTransliterator()->id);

        Text::setTransliteratorId($defaultTransliteratorId);
    }

    public function testTokenize(): void
    {
        // reproduce CakPHP tests
        $result = Text::tokenize('A,(short,boring test)');
        $expected = ['A', '(short,boring test)'];
        $this->assertSame($expected, $result);

        $result = Text::tokenize('A,(short,more interesting( test)');
        $expected = ['A', '(short,more interesting( test)'];
        $this->assertSame($expected, $result);

        $result = Text::tokenize('A,(short,very interesting( test))');
        $expected = ['A', '(short,very interesting( test))'];
        $this->assertSame($expected, $result);

        $result = Text::tokenize('"single tag"', ' ', '"', '"');
        $expected = ['"single tag"'];
        $this->assertSame($expected, $result);

        $result = Text::tokenize('tagA "single tag" tagB', ' ', '"', '"');
        $expected = ['tagA', '"single tag"', 'tagB'];
        $this->assertSame($expected, $result);

        $result = Text::tokenize('tagA "first tag" tagB "second tag" tagC', ' ', '"', '"');
        $expected = ['tagA', '"first tag"', 'tagB', '"second tag"', 'tagC'];
        $this->assertSame($expected, $result);

        // Ideographic width space.
        $result = Text::tokenize("tagA\xe3\x80\x80\"single\xe3\x80\x80tag\"\xe3\x80\x80tagB", "\xe3\x80\x80", '"', '"');
        $expected = ['tagA', '"single　tag"', 'tagB'];
        $this->assertSame($expected, $result);
    }

    public function testUuid(): void
    {
        $result = Text::uuid();
        $pattern = '/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/';
        $match = (bool)preg_match($pattern, $result);
        $this->assertTrue($match);
    }

    public function testMbSubstrReplace(): void
    {
        $result = Text::mbSubstrReplace('1234567890', 'abcdef', 2, 3);
        $this->assertSame('12abcdef67890', $result);

        $result2 = substr_replace('1234567890', 'abcdef', 2, 3);
        $this->assertSame($result, $result2);

        $result = Text::mbSubstrReplace('1234567890', 'abcdef', 2, 3, 'utf-8');
        $this->assertSame('12abcdef67890', $result);

        $result = Text::mbSubstrReplace('💥1234567890💥', 'abc🏓d💐ef', 2, 6, 'utf-8');
        $this->assertSame('💥1abc🏓d💐ef890💥', $result);

        $result = Text::mbSubstrReplace('💥1234567890💥', 'abc🏓d💐ef', 2, 2, 'utf-8');
        $this->assertSame('💥1abc🏓d💐ef4567890💥', $result);

        $result = Text::mbSubstrReplace('1234567890', 'abcdef', -1, -1, 'utf-8');
        $result2 = substr_replace('1234567890', 'abcdef', -1, -1);
        $this->assertSame('123456789abcdef0', $result);
        $this->assertSame($result2, $result);

        $result = Text::mbSubstrReplace('1234567890', 'abcdef', -1, 1000, 'utf-8');
        $result2 = substr_replace('1234567890', 'abcdef', -1, 1000);
        $this->assertSame('123456789abcdef', $result);
        $this->assertSame($result2, $result);

        $result = Text::mbSubstrReplace('1234567890', 'abcdef', 1000, 1000, 'utf-8');
        $result2 = substr_replace('1234567890', 'abcdef', 1000, 1000);
        $this->assertSame('1234567890abcdef', $result);
        $this->assertSame($result2, $result);

        $result = Text::mbSubstrReplace('', 'abcdef', 1000, 1000, 'utf-8');
        $result2 = substr_replace('', 'abcdef', 1000, 1000);
        $this->assertSame('abcdef', $result);
        $this->assertSame($result2, $result);

        $result = Text::mbSubstrReplace('', 'abcdef', 1000, null, 'utf-8');
        $result2 = substr_replace('', 'abcdef', 1000, 1000);
        $this->assertSame('abcdef', $result);
        $this->assertSame($result2, $result);
    }
}
