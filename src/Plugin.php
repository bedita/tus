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
namespace BEdita\Tus;

use BEdita\Tus\Middleware\TusMiddleware;
use Cake\Core\BasePlugin;
use Cake\Core\Configure;
use Cake\Core\PluginApplicationInterface;
use Cake\Http\MiddlewareQueue;

/**
 * Plugin for BEdita\Tus
 */
class Plugin extends BasePlugin
{
    /**
     * {@inheritDoc}
     *
     * Load Tus configuration.
     */
    public function bootstrap(PluginApplicationInterface $app): void
    {
        parent::bootstrap($app);

        if (!Configure::check('Tus')) {
            Configure::load('BEdita/Tus.config');
        }
    }

    /**
     * @inheritDoc
     */
    public function middleware(MiddlewareQueue $middleware): MiddlewareQueue
    {
        $middleware->insertAt(0, new TusMiddleware(Configure::read('Tus')));

        return $middleware;
    }
}
