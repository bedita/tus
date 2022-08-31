<?php

use Cake\Core\Configure;
use Cake\Routing\RouteBuilder;

return static function (RouteBuilder $routes) {

    $routes->plugin(
        'BEdita/Tus',
        ['path' => sprintf('/%s', Configure::read('Tus.endpoint')), '_namePrefix' => 'api:'],
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
};
