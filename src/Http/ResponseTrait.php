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
namespace BEdita\Tus\Http;

use Cake\Http\Response;
use Laminas\Diactoros\Stream;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Help to handle Tus response.
 */
trait ResponseTrait
{
    /**
     * Transform a Symfony response in a CakePHP response
     *
     * @param \Symfony\Component\HttpFoundation\Response $httpResponse The response
     * @return \Cake\Http\Response
     */
    public function toCakeResponse(HttpResponse $httpResponse): Response
    {
        $response = new Response();
        $response = $response->withStatus($httpResponse->getStatusCode());
        foreach ($httpResponse->headers->all() as $k => $v) {
            $response = $response->withHeader($k, $v);
        }
        if ($httpResponse->getContent() !== false) {
            $stream = new Stream('php://memory', 'rw');
            $stream->write($httpResponse->getContent());
            $response = $response->withBody($stream);
        }

        return $response;
    }
}
