<?php
/**
 * BEdita, API-first content management framework
 * Copyright 2021 ChannelWeb Srl, Chialab Srl
 *
 * This file is part of BEdita: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * See LICENSE.LGPL or <http://gnu.org/licenses/lgpl-3.0.html> for more details.
 */
namespace BEdita\Tus\Middleware\Tus;

use Cake\Core\InstanceConfigTrait;
use Cake\Utility\Hash;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use TusPhp\Middleware\TusMiddleware;
use TusPhp\Request;
use TusPhp\Response;

/**
 * Setup the request for trusted proxies.
 */
class TrustProxiesMiddleware implements TusMiddleware
{
    use InstanceConfigTrait;

    /**
     * Default configuration.
     *
     * - `proxies` an array of IPs or * as wild card
     * - `headers` trusted headers from proxies
     *
     * @var array
     */
    protected $_defaultConfig = [
        'proxies' => [],
        'headers' => [
            HttpRequest::HEADER_X_FORWARDED_PROTO,
            HttpRequest::HEADER_X_FORWARDED_FOR,
            HttpRequest::HEADER_X_FORWARDED_PORT,
            HttpRequest::HEADER_X_FORWARDED_HOST,
            HttpRequest::HEADER_X_FORWARDED_AWS_ELB,
        ],
    ];

    /**
     * Init middleware.
     *
     * @param array $config The configuration
     */
    public function __construct(array $config = [])
    {
        $headers = Hash::get($config, 'headers');
        if ($headers === null) {
            unset($config['headers']);
        }

        if ($headers && is_string($config['headers'])) {
            $config['headers'] = explode(',', trim($headers));
        }

        $proxies = Hash::get($config, 'proxies');
        if ($proxies && is_string($config['proxies']) && $proxies !== '*') {
            $config['proxies'] = explode(',', trim($proxies));
        }

        $this->setConfig($config);
    }

    /**
     * {@inheritDoc}
     *
     * Add Authorization header to 'Access-Control-Allow-Headers'.
     */
    public function handle(Request $request, Response $response): void
    {
        $proxies = $this->getProxies($request);
        if (!empty($proxies)) {
            HttpRequest::setTrustedProxies($proxies, $this->getTrustedHeaders());
        }
    }

    /**
     * Get trusted proxies
     *
     * @param \TusPhp\Request $request The http request
     * @return array
     */
    protected function getProxies(Request $request): array
    {
        $httpRequest = $request->getRequest();
        if ($this->getConfig('proxies') === '*') {
            return [$httpRequest->server->get('REMOTE_ADDR')];
        }

        return (array)$this->getConfig('proxies');
    }

    /**
     * Return the `|` bitwise of headers.
     *
     * @return int
     */
    protected function getTrustedHeaders(): int
    {
        return array_reduce(
            (array)$this->getConfig('headers'),
            function ($carry, $item) {
                return $carry | $item;
            },
            0
        );
    }
}
