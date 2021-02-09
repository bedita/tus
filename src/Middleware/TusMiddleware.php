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
namespace BEdita\Tus\Middleware;

use Cake\Core\InstanceConfigTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * TusMiddleware middleware
 */
class TusMiddleware
{
    use InstanceConfigTrait;

    /**
     * Default configuration.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'endpoint' => '/tus',
    ];

    protected $uploadPath = null;

    /**
     * Constructor.
     * Setup the middleware.
     *
     * @param array $config The Tus server configuration.
     */
    public function __construct(array $config)
    {
        if (!empty($config['endpoint']) && strpos($config['endpoint'], '/') !== 0) {
            $config['endpoint'] = '/' . $config['endpoint'];
        }
        $this->setConfig($config);
    }

    /**
     * Invoke method.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Message\ResponseInterface $response The response.
     * @param callable $next Callback to invoke the next middleware.
     * @return \Psr\Http\Message\ResponseInterface A response
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next)
    {
        $path = $request->getUri()->getPath();
        if ($request->getMethod() === 'OPTIONS' && strpos($path, $this->getConfig('endpoint')) === 0) {
            $next($request, $response); // skip BEdita/API CORS middleware
        }

        return $next($request, $response);
    }
}
