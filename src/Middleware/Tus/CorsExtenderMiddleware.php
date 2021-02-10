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

use BEdita\Tus\Event\UploadListener;
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
        $headers['Access-Control-Expose-Headers'] .= sprintf(
            ', %s, %s',
            UploadListener::BEDITA_OBJECT_ID_HEADER,
            UploadListener::BEDITA_OBJECT_TYPE_HEADER,
        );
        $response->replaceHeaders($headers);
    }
}
