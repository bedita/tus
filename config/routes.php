<?php
use Cake\Core\Configure;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;

Router::plugin(
    'BEdita/Tus',
    [
        'path' => sprintf('/%s', Configure::read('Tus.endpoint')),
        '_namePrefix' => 'api:',
    ],
    function (RouteBuilder $routes) {
        // Tus server
        $routes->connect(
            '/{type}/*',
            ['controller' => 'Tus', 'action' => 'server'],
            ['_name' => 'tus']
        )
        ->setPass(['type']);
    }
);
