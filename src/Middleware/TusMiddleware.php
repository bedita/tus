<?php
declare(strict_types=1);

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

use BEdita\Tus\Http\ResponseTrait;
use BEdita\Tus\Http\ServerFactory;
use Cake\Core\InstanceConfigTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * TusMiddleware middleware
 */
class TusMiddleware implements MiddlewareInterface
{
    use InstanceConfigTrait;
    use ResponseTrait;

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
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var \Cake\Http\ServerRequest $request */
        $path = $request->getUri()->getPath();
        if ($request->getMethod() !== 'OPTIONS' || strpos($path, $this->getConfig('endpoint')) !== 0) {
            return $handler->handle($request);
        }

        $config = $this->getConfig();
        $config['endpoint'] = $path;

        return $this->toCakeResponse(ServerFactory::create($config)->serve());
    }
}
