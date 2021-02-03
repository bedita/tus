<?php

namespace BEdita\Tus\Http;

use Cake\Http\Response;
use Laminas\Diactoros\Stream;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Help to handle response.
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
