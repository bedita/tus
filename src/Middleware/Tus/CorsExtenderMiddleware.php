<?php

namespace BEdita\Tus\Middleware\Tus;

use TusPhp\Middleware\TusMiddleware;
use TusPhp\Request;
use TusPhp\Response;

/**
 * Extends Tus CORS adding needed headers.
 */
class CorsExtenderMiddleware implements TusMiddleware
{
    /**
     * {@inheritDoc}
     *
     * Add Authorization header to 'Access-Control-Allow-Headers'.
     */
    public function handle(Request $request, Response $response)
    {
        $headers = $response->getHeaders();
        $headers['Access-Control-Allow-Headers'] .= ', Authorization';
        $response->replaceHeaders($headers);
    }
}
