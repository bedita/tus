<?php

namespace BEdita\Tus;

use BEdita\API\Middleware\CorsMiddleware;
use BEdita\Tus\Middleware\TusMiddleware;
use Cake\Core\BasePlugin;
use Cake\Core\Configure;
use Cake\Core\PluginApplicationInterface;

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
    public function bootstrap(PluginApplicationInterface $app)
    {
        parent::bootstrap($app);

        Configure::load('BEdita/Tus.config');
    }

    /**
     * {@inheritDoc}
     */
    public function middleware($middleware)
    {
        /** @var \Cake\Http\MiddlewareQueue $middleware */
        $middleware->insertAt(0, new TusMiddleware(Configure::read('Tus')));

        return $middleware;
    }
}
