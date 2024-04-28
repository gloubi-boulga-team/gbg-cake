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

return [

    'zip-plugin' => [

        'gbg-cake5' => [
            'name'  => 'gbg-cake5',                     // plugin name
            'dir'   => __DIR__,                         // plugin dir
            'ignore' => [
                '/\.git(.*)/i',                         // ignore recursively .git* folders / files
                '/composer\.(json|lock)$/i',            // ignore recursively composer.json and composer.locks
                '/^[dir]\/repo-cli\.md/i',              // ignore repo-cli.md
                '/changelog(.*)/i',                     // ignore recursively changelog* files (avoid exposing versions)
                '/VERSION(\.txt)?/i',                   // ignore recursively VERSION file
                '/phpstan.neon(.*)/i',                  // ignore recursively phpstan.neon* files
                '/(!readme).*\.txt$/i',                 // ignore recursively *.txt files except readme
                '/(.*)\.log$/i',                        // ignore *.log files
                '/^[dir]\/readme.md/i',                  // ignore git-reserved readme.md
                '/^[dir]\/tests/i',                     // ignore root folder /tests
                '/^[dir]\/env/i',                       // ignore root folder /env
                '/^[dir]\/examples/i',                  // ignore root folder /env
                '/^[dir]\/bin/i',                       // ignore root folder /bin
                '/^[dir]\/dist/i',                      // ignore root folder /dist
                '/^[dir]\/tools/i',                     // ignore root folder /tools
                '/^[dir]\/(.*).inc$/i',                 // ignore *.inc files
            ]
        ]
    ],

    'versionize-vendor' => [

        'paths' => [
            '[dir]/vendor',
            '[dir]/vendor/composer/composer.lock',
            '[dir]/vendor/composer/autoload_psr4.php',
            '[dir]/vendor/composer/installed.json',
            //'[dir]/vendor/phpunit/phpunit/phpunit',
        ],

        'replace' => [
            'phpunit' => [
                'PHPUnit[oldversion]\TextUI'           => 'PHPUnit[newversion]\TextUI',
            ],
            'php' => [
                'namespace Cake[oldversion];'       => 'namespace Cake[newversion];',
                'namespace Cake[oldversion]\\'      => 'namespace Cake[newversion]\\',
                'use Cake[oldversion]\\'            => 'use Cake[newversion]\\',
                'use function Cake[oldversion]\\'   => 'use function Cake[newversion]\\',
                '\\Cake[oldversion]\\'              => '\\Cake[newversion]\\',
                '\'Cake[oldversion]\\'              => '\'Cake[newversion]\\',

                'namespace Doctrine[oldversion];'       => 'namespace Doctrine[newversion];',
                'namespace Doctrine[oldversion]\\'      => 'namespace Doctrine[newversion]\\',
                'use Doctrine[oldversion]\\'            => 'use Doctrine[newversion]\\',
                'use function Doctrine[oldversion]\\'   => 'use function Doctrine[newversion]\\',
                '\\Doctrine[oldversion]\\'              => '\\Doctrine[newversion]\\',
                '\'Doctrine[oldversion]\\'              => '\'Doctrine[newversion]\\',

                'namespace PhpParser[oldversion];'                 => 'namespace PhpParser[newversion];',
                'namespace PhpParser[oldversion]\\'     => 'namespace PhpParser[newversion]\\',
                'use PhpParser[oldversion]\\'           => 'use PhpParser[newversion]\\',
                'use function PhpParser[oldversion]\\'  => 'use function PhpParser[newversion]\\',
                '\\PhpParser[oldversion]\\'             => '\\PhpParser[newversion]\\',
                '\'PhpParser[oldversion]\\'             => '\'PhpParser[newversion]\\',

                'namespace Psr[oldversion];'       => 'namespace Psr[newversion];',
                'namespace Psr[oldversion]\\'      => 'namespace Psr[newversion]\\',
                'use Psr[oldversion]\\'            => 'use Psr[newversion]\\',
                'use function Psr[oldversion]\\'   => 'use function Psr[newversion]\\',
                '\\Psr[oldversion]\\'              => '\\Psr[newversion]\\',
                '\'Psr[oldversion]\\'              => '\'Psr[newversion]\\',

                'namespace PharIo[oldversion];'       => 'namespace PharIo[newversion];',
                'namespace PharIo[oldversion]\\'      => 'namespace PharIo[newversion]\\',
                'use PharIo[oldversion]\\'            => 'use PharIo[newversion]\\',
                'use function PharIo[oldversion]\\'   => 'use function PharIo[newversion]\\',
                '\\PharIo[oldversion]\\'              => '\\PharIo[newversion]\\',
                '\'PharIo[oldversion]\\'              => '\'PharIo[newversion]\\',

                'namespace SebastianBergmann[oldversion];'       => 'namespace SebastianBergmann[newversion];',
                'namespace SebastianBergmann[oldversion]\\'      => 'namespace SebastianBergmann[newversion]\\',
                'use SebastianBergmann[oldversion]\\'            => 'use SebastianBergmann[newversion]\\',
                'use function SebastianBergmann[oldversion]\\'   => 'use function SebastianBergmann[newversion]\\',
                '\\SebastianBergmann[oldversion]\\'              => '\\SebastianBergmann[newversion]\\',
                '\'SebastianBergmann[oldversion]\\'              => '\'SebastianBergmann[newversion]\\',

//                'namespace PHPUnit[oldversion];'       => 'namespace PHPUnit[newversion];',
//                'namespace PHPUnit[oldversion]\\'      => 'namespace PHPUnit[newversion]\\',
//                'use PHPUnit[oldversion]\\'            => 'use PHPUnit[newversion]\\',
//                'use function PHPUnit[oldversion]\\'   => 'use function PHPUnit[newversion]\\',
//                '\\PHPUnit[oldversion]\\'              => '\\PHPUnit[newversion]\\',
//                '\'PHPUnit[oldversion]\\'              => '\'PHPUnit[newversion]\\',

                'namespace PHPStan[oldversion];'       => 'namespace PHPStan[newversion];',
                'namespace PHPStan[oldversion]\\'      => 'namespace PHPStan[newversion]\\',
                'use PHPStan[oldversion]\\'            => 'use PHPStan[newversion]\\',
                'use function PHPStan[oldversion]\\'   => 'use function PHPStan[newversion]\\',
                '\\PHPStan[oldversion]\\'              => '\\PHPStan[newversion]\\',
                '\'PHPStan[oldversion]\\'              => '\'PHPStan[newversion]\\',

                'namespace TheSeer[oldversion];'       => 'namespace TheSeer[newversion];',
                'namespace TheSeer[oldversion]\\'      => 'namespace TheSeer[newversion]\\',
                'use TheSeer[oldversion]\\'            => 'use TheSeer[newversion]\\',
                'use function TheSeer[oldversion]\\'   => 'use function TheSeer[newversion]\\',
                '\\TheSeer[oldversion]\\'              => '\\TheSeer[newversion]\\',
                '\'TheSeer[oldversion]\\'              => '\'TheSeer[newversion]\\',
            ],
            'json' => [
                '"Cake[oldversion]\\'               => '"Cake[newversion]\\',
                '"Doctrine[oldversion]\\'           => '"Doctrine[newversion]\\',
                '"Psr[oldversion]\\'                => '"Psr[newversion]\\',
                '"PhpParser[oldversion]\\'          => '"PhpParser[newversion]\\',
                '"PharIo[oldversion]\\'             => '"PharIo[newversion]\\',
                '"SebastianBergmann[oldversion]\\'  => '"SebastianBergmann[newversion]\\',
                '"TheSeer[oldversion]\\'            => '"TheSeer[newversion]\\',
            ],
            'lock' => [
                '"Cake[oldversion]\\'               => '"Cake[newversion]\\',
                '"Doctrine[oldversion]\\'           => '"Doctrine[newversion]\\',
                '"Psr[oldversion]\\'                => '"Psr[newversion]\\',
                '"PhpParser[oldversion]\\'          => '"PhpParser[newversion]\\',
                '"PharIo[oldversion]\\'             => '"PharIo[newversion]\\',
                '"SebastianBergmann[oldversion]\\'  => '"SebastianBergmann[newversion]\\',
                '"TheSeer[oldversion]\\'            => '"TheSeer[newversion]\\',
            ]
        ]
    ],
    'versionize-plugin' => [
        'paths' => [
            '[dir]/src',
            '[dir]/config',
            '[dir]/templates',
            '[dir]/tests',
            '[dir]/composer.lock',
            '[dir]/*.php',
        ],
        'rename' => [
            '[dir]/resources/locales/*.{pot,po,mo}' => [
                'gbg-cake[oldversion].pot' => 'gbg-cake[newversion].pot',
                'gbg-cake[oldversion].po' => 'gbg-cake[newversion].po',
                'gbg-cake[oldversion].mo' => 'gbg-cake[newversion].mo'
            ],
            '[dir]/*.php' => [
                'gbg-cake[oldversion]-' => 'gbg-cake[newversion]-',
                "gbg-cake[oldversion]\n" => "gbg-cake[newversion]\n",
                "gbg-cake[oldversion]'" => "gbg-cake[newversion]'",
            ],
        ],
        'replace' => [
            'php' => [
                'namespace Gbg\Cake[oldversion]\\'      => 'namespace Gbg\Cake[newversion]\\',
                'use Gbg\Cake[oldversion]\\'            => 'use Gbg\Cake[newversion]\\',
                'use function Gbg\Cake[oldversion]\\'   => 'use function Gbg\Cake[newversion]\\',
                '\\Gbg\\Cake[oldversion]\\'             => '\\Gbg\\Cake[newversion]\\',
                '\'Gbg\Cake[oldversion]\\'              => '\'Gbg\Cake[newversion]\\',
                'use Cake[oldversion]\\'                => 'use Cake[newversion]\\',
                'use function Cake[oldversion]\\'       => 'use function Cake[newversion]\\',
                '\\Cake[oldversion]\\'                  => '\\Cake[newversion]\\',
                '\'Cake[oldversion]\\'                  => '\'Cake[newversion]\\',
                'Gbg/Cake[oldversion].'                 => 'Gbg/Cake[newversion].',
                'Gbg/Cake[oldversion]:'                 => 'Gbg/Cake[newversion]:',
                '\'Gbg/Cake[oldversion]\''              => '\'Gbg/Cake[newversion]\'',
                'GBG_CAKE[oldversion]_'                 => 'GBG_CAKE[newversion]_',
                'gbgCake[oldversion]_'                  => 'gbgCake[newversion]_',
                'gbg-cake[oldversion]'                  => 'gbg-cake[newversion]',
                //'\PHPUnit[oldversion]\Framework\TestCase'   => '\PHPUnit[newversion]\Framework\TestCase',
                'Doctrine[oldversion]\Inflector'        => 'Doctrine[newversion]\Inflector',
                //'use PHPUnit[oldversion]\Framework'     => 'use PHPUnit[newversion]\Framework',
            ],
            'json' => [
                '"Cake[oldversion]\\'                   => '"Cake[newversion]\\'
            ],
            'lock' => [
                '"Cake[oldversion]\\'                   => '"Cake[newversion]\\'
            ],
        ]
    ]
];
