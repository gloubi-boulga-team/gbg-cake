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

use Gbg\Cake5\Http\Request;
use Gbg\Cake5\TestCase;

class RequestTest extends TestCase
{
    /**
     * @inheritDoc
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
    }

    /**
     * @inheritDoc
     */
    public static function tearDownAfterClass(): void
    {
        parent::setUpBeforeClass();
    }

    public function testGetCurrentUri(): void
    {
        $request = Request::instance();

        $oldServer = $_SERVER;
        $_SERVER = null;

        $this->assertNull($request->getCurrentUri());

        $_SERVER = [
            'REQUEST_SCHEME' => 'http',
            'HTTP_HOST' => 'localhost',
            'REQUEST_URI' => '/test?param=value',
        ];
        $this->assertSame('/test?param=value', $request->getCurrentUri());
        $this->assertSame('/test', $request->getCurrentUri(['removeQuery' => true]));

        $_SERVER = [
            'REQUEST_SCHEME' => 'http',
            'HTTP_HOST' => 'localhost',
            'REQUEST_URI' => '/test-x/y/zz.z?param=value',
        ];
        $this->assertSame('/test-x/y/zz.z?param=value', $request->getCurrentUri());
        $this->assertSame('/test-x/y/zz.z', $request->getCurrentUri(['removeQuery' => true]));

        $_SERVER = [
            'REQUEST_SCHEME' => 'http',
            'HTTP_HOST' => 'localhost',
            'REQUEST_URI' => '',
        ];
        $this->assertSame('', $request->getCurrentUri());
        $this->assertSame('', $request->getCurrentUri(['removeQuery' => true]));

        $_SERVER = [
            'REQUEST_SCHEME' => 'http',
            'HTTP_HOST' => 'localhost',
            'REQUEST_URI' => '/',
        ];
        $this->assertSame('/', $request->getCurrentUri());
        $this->assertSame('/', $request->getCurrentUri(['removeQuery' => true]));

        $_SERVER = $oldServer;
    }

    public function testGetCurrentUrl(): void
    {
        $request = Request::instance();

        $oldServer = $_SERVER;
        $_SERVER = null;

        $this->assertNull($request->getCurrentUri());

        $_SERVER = [
            'HTTP_HOST' => 'local.host.com',
            'REQUEST_URI' => '/test-x/y/zz.z?p1=[v1a,v1b]&p2=v2',
        ];
        $this->assertSame('http://local.host.com/test-x/y/zz.z?p1=[v1a,v1b]&p2=v2', $request->getCurrentUrl());
        $this->assertSame('http://local.host.com/test-x/y/zz.z', $request->getCurrentUrl(['removeQuery' => true]));

        $_SERVER = [
            'HTTPS' => 'off',
            'HTTP_HOST' => 'local.host.com',
            'REQUEST_URI' => '/test-x/y/zz.z?p1=[v1a,v1b]&p2=v2',
        ];
        $this->assertSame('http://local.host.com/test-x/y/zz.z?p1=[v1a,v1b]&p2=v2', $request->getCurrentUrl());
        $this->assertSame('http://local.host.com/test-x/y/zz.z', $request->getCurrentUrl(['removeQuery' => true]));

        $_SERVER = [
            'HTTPS' => true,
            'HTTP_HOST' => 'local.host.com',
            'REQUEST_URI' => '/test-x/y/zz.z?p1=[v1a,v1b]&p2=v2',
        ];
        $this->assertSame('https://local.host.com/test-x/y/zz.z?p1=[v1a,v1b]&p2=v2', $request->getCurrentUrl());
        $this->assertSame('https://local.host.com/test-x/y/zz.z', $request->getCurrentUrl(['removeQuery' => true]));

        $_SERVER = [
            'HTTPS' => 'on',
            'HTTP_HOST' => 'local.host.com',
            'REQUEST_URI' => '/test-x/y/zz-d.z',
        ];
        $this->assertSame('https://local.host.com/test-x/y/zz-d.z', $request->getCurrentUrl());
        $this->assertSame('https://local.host.com/test-x/y/zz-d.z', $request->getCurrentUrl(['removeQuery' => true]));

        $_SERVER = $oldServer;
    }

    public function testGetCurrentQuery(): void
    {
        $request = Request::instance();

        $oldServer = $_SERVER;
        $_SERVER = null;

        $this->assertNull($request->getCurrentQuery());
        $this->assertNull($request->getCurrentQuery('p1'));

        $_SERVER = [
            'HTTP_HOST' => 'local.host.com',
            'REQUEST_URI' => '/test-x/y/zz.z?p1[]=v1a&p1[]=v1b&p2=v2',
        ];
        $this->assertSame(['p1' => ['v1a', 'v1b'], 'p2' => 'v2'], $request->getCurrentQuery());
        $this->assertSame(['v1a', 'v1b'], $request->getCurrentQuery('p1'));
        $this->assertSame('v2', $request->getCurrentQuery('p2'));

        $_SERVER = $oldServer;
    }

    public function testIsIValid(): void
    {
        $request = Request::instance();

        $oldServer = $_SERVER;
        $_SERVER = null;

        $_SERVER = [
            'HTTP_HOST' => 'local.host.com',
            'REQUEST_URI' => '/test-x/y/zz.z?p1[]=v1a&p1[]=v1b&p2=v2',
        ];

        $this->assertSame('127.0.0.1', $request->isIpValid('127.0.0.1', FILTER_FLAG_IPV4));
        $this->assertFalse($request->isIpValid('127.0.0.1', FILTER_FLAG_IPV6));
        $this->assertFalse($request->isIpValid('::ffff:7f00:0001', FILTER_FLAG_IPV4));
        $this->assertSame('::ffff:7f00:0001', $request->isIpValid('::ffff:7f00:0001', FILTER_FLAG_IPV6));
        $this->assertSame(
            '0000:0000:0000:0000:0000:ffff:7f00:0001',
            $request->isIpValid(
                '0000:0000:0000:0000:0000:ffff:7f00:0001',
                FILTER_FLAG_IPV6
            )
        );

        $_SERVER = $oldServer;
    }

    public function testClientIp(): void
    {
        Request::resetInstance();
        $request = Request::instance();

        $oldServer = $_SERVER;

        $_SERVER = ['HTTP_X_FORWARDED_FOR' => '192.168.1.1, 192.168.1.2, 192.168.1.3, 192.168.1.4'];

        $request->setTrustedProxies([]);
        $this->assertSame([], $request->getTrustedProxies());

        $this->assertSame('192.168.1.4', $request->getClientIp());

        $request->setTrustedProxies(['192.168.1.2', '192.168.1.3', '192.168.1.4']);
        $this->assertSame(['192.168.1.2', '192.168.1.3', '192.168.1.4'], $request->getTrustedProxies());

        $_SERVER = ['HTTP_X_FORWARDED_FOR' => '192.168.1.2'];
        $this->assertSame('192.168.1.2', $request->getClientIp());

        $_SERVER = ['HTTP_X_FORWARDED_FOR' => '192.168.1.1, 192.168.1.2'];
        $this->assertSame('192.168.1.1', $request->getClientIp());

        $_SERVER = ['HTTP_X_FORWARDED_FOR' => '192.168.1.3, 192.168.1.2'];
        $this->assertSame('192.168.1.2', $request->getClientIp());

        $_SERVER = ['HTTP_X_FORWARDED_FOR' => '192.168.1.1x, 192.168.1.2'];
        $this->assertSame(null, $request->getClientIp());

        $_SERVER = ['HTTP_X_FORWARDED_FOR' => '192.168.1.1x, 192.168.1.2'];
        $this->assertSame(null, $request->getClientIp());

        $_SERVER = ['HTTP_X_FORWARDED_FOR' => '::ffff:c001:0101, 192.168.1.2'];
        $this->assertSame('::ffff:c001:0101', $request->getClientIp());

        $_SERVER = ['HTTP_X_FORWARDED_FOR' => '0000:0000:0000:0000:0000:ffff:c001:0101, 192.168.1.2'];
        $this->assertSame('0000:0000:0000:0000:0000:ffff:c001:0101', $request->getClientIp());

        $request->setTrustedProxies([]);
        $_SERVER = ['REMOTE_ADDR' => '192.168.1.1'];
        $this->assertSame('192.168.1.1', $request->getClientIp());

        $_SERVER = ['HTTP_X_REAL_IP' => '192.168.1.1'];
        $this->assertSame('192.168.1.1', $request->getClientIp());

        $request->setTrustProxy(false);
        $this->assertSame(false, $request->getTrustProxy());

        $_SERVER = ['HTTP_X_REAL_IP' => '192.168.1.1'];
        $this->assertSame('192.168.1.1', $request->getClientIp());

        $_SERVER = ['HTTP_X_REAL_IP' => '192.168.1.1', 'REMOTE_ADDR' => '192.168.1.2'];
        $this->assertSame('192.168.1.1', $request->getClientIp());

        $_SERVER = ['REMOTE_ADDR' => '192.168.1.2'];
        $this->assertSame('192.168.1.2', $request->getClientIp());

        $_SERVER = $oldServer;
    }
}
