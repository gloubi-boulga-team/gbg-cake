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

namespace Gbg\Cake5\Http;

/** @phpstan-consistent-constructor */
// Overriding constructor may require overriding `instance` function
class Request
{
    /**
     * Trust proxy
     * @var bool $trustProxy
     */
    protected bool $trustProxy = false;

    /**
     * List of trusted proxies
     * @var array<string> $trustedProxies
     */
    protected array $trustedProxies = [];

    /**
     * @var array<string, int|array<string>>
     */
    public array $ipConfig = [
        'mask' => FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6,
        'trustedHeaders' => [
            'HTTP_CF_CONNECTING_IP',    // CloudFlare
            'HTTP_FORWARDED_FOR',       // Some old proxies
            'HTTP_X_FORWARDED',         // Some reverse-proxies
            'HTTP_X_FORWARDED_FOR',     // Most reverse-proxies
        ],
        'defaultHeaders' => [
            'HTTP_X_CLUSTER_CLIENT_IP', // For clustered servers
            'HTTP_X_REAL_IP',           // For PHP FPM
            'HTTP_CLIENT_IP',           // Some servers
            'REMOTE_ADDR'               // Final IP address
        ]
    ];

    /**
     * @var Request|null
     */
    protected static ?Request $instance = null;

    /**
     * Request constructor.
     */
    public function __construct()
    {
    }

    /**
     * @internal
     *
     * @return void
     */
    public static function resetInstance(): void
    {
        static::$instance = null;
    }

    /**
     * @return Request
     */
    public static function instance(): Request
    {
        if (!static::$instance) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    /**
     * Get current request URI (without host part)
     *
     * @param array<string, mixed>|null $options
     *
     * @return string|null
     */
    public function getCurrentUri(?array $options = ['removeQuery' => false]): ?string
    {
        if (empty($_SERVER)) {
            return null;
        }
        $uri = $_SERVER["REQUEST_URI"] ?? '';
        return !empty($options['removeQuery']) ? wp_parse_url($uri, PHP_URL_PATH) : $uri;
    }

    /**
     * Get current request URL (host + URI)
     *
     * @param array<string, mixed>|null $options
     *
     * @return string
     */
    public function getCurrentUrl(?array $options = ['removeQuery' => false]): string
    {
        return ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http") .
            '://' . ($_SERVER['HTTP_HOST'] ?? '??') . $this->getCurrentUri($options);
    }

    /**
     * Get current request query (after the `?`)
     *
     * @param string|null $param
     *
     * @return mixed
     */
    public function getCurrentQuery(?string $param = null): mixed
    {
        if (!is_string($url = wp_parse_url($this->getCurrentUrl(), PHP_URL_QUERY))) {
            return null;
        }
        parse_str($url, $params);

        return $param ? ($params[$param] ?? null) : $params;
    }

    /**
     * Get client ip
     *
     * @return string|null
     */
    public function getClientIp(): ?string
    {
        /** @var int $mask */
        $mask = $this->ipConfig['mask'];

        if ($this->trustProxy) {
            $trusted = count($this->trustedProxies);

            /** @var string[] $trustedHeaders */
            $trustedHeaders = $this->ipConfig['trustedHeaders'];

            foreach ($trustedHeaders as $trustedHeader) {
                if (!$ip = ($_SERVER[$trustedHeader] ?? null)) {
                    continue;
                }

                $ips = array_map('trim', explode(',', $ip));
                $countIps = count($ips);

                if ($trusted) {
                    $remain = array_diff($ips, $this->trustedProxies);
                    $countRemain = count($remain);
                    if ($countRemain === 1) {
                        if ($this->isIpValid($remain[0], $mask)) {
                            return $remain[0];
                        }
                        continue;
                    }
                }

                if ($this->isIpValid($ips[$countIps - 1], $mask)) {
                    return $ips[$countIps - 1];
                }
            }
        }

        /** @var string[] $defaultHeaders */
        $defaultHeaders = $this->ipConfig['defaultHeaders'];

        foreach ($defaultHeaders as $defaultHeader) {
            if (
                ($ip = ($_SERVER[$defaultHeader] ?? null)) &&
                $this->isIpValid($ip, $mask)
            ) {
                return $ip;
            }
        }

        return null;
    }

    /**
     * Find if a string is a valid IP
     *
     * @param string $ip
     * @param int|null $flags
     * @return mixed
     */
    public function isIpValid(string $ip, ?int $flags = null): mixed
    {
        /** @var ?int $mask */
        $mask = $this->ipConfig['mask'];
        $ipValidityMask = $flags ?? ($mask ?? FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6);

        return filter_var($ip, FILTER_VALIDATE_IP, $ipValidityMask);
    }

    /**
     * @param array<string> $proxies
     * @return static
     */
    public function setTrustedProxies(array $proxies): static
    {
        $this->trustedProxies = $proxies;
        $this->trustProxy = true;
        return $this;
    }

    /**
     * @return array<string>
     */
    public function getTrustedProxies(): array
    {
        return $this->trustedProxies;
    }


    /**
     * Set trust proxy
     *
     * @param bool $value
     * @return $this
     */
    public function setTrustProxy(bool $value): static
    {
        $this->trustProxy = $value;
        return $this;
    }

    /**
     * Get trust proxy
     *
     * @return bool
     */
    public function getTrustProxy(): bool
    {
        return $this->trustProxy;
    }
}
