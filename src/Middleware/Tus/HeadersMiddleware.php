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
namespace BEdita\Tus\Middleware\Tus;

use BEdita\Tus\Http\Server;
use Cake\Core\InstanceConfigTrait;
use Cake\Utility\Hash;
use TusPhp\Middleware\TusMiddleware;
use TusPhp\Request;
use TusPhp\Response;

/**
 * Handle headers:
 *
 * - extends Tus CORS adding needed headers.
 * - remove headers to not set
 */
class HeadersMiddleware implements TusMiddleware
{
    use InstanceConfigTrait;

    /**
     * Default configuration:
     *
     *  - `excludeHeaders` the headers to not set.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'exclude' => [],
    ];

    /**
     * Init middleware.
     *
     * @param array $config The configuration
     */
    public function __construct(array $config = [])
    {
        $exclude = Hash::get($config, 'exclude');
        if ($exclude && is_string($exclude)) {
            $config['exclude'] = explode(',', trim($exclude));
        }
        $this->setConfig($config);
    }

    /**
     * @inheritDoc
     */
    public function handle(Request $request, Response $response)
    {
        $headers = $this->extendsCors($response->getHeaders());
        foreach ((array)$this->getConfig('exclude') as $header) {
            $this->unsetHeader($headers, $header);
        }

        $response->replaceHeaders($headers);
    }

    /**
     * Remove header from array of headers.
     *
     * @param array $headers The headers array
     * @param string $header The header to remove
     * @return void
     */
    protected function unsetHeader(array &$headers, string $header): void
    {
        unset($headers[$header], $headers[strtolower($header)]);
        $parts = explode('-', $header);
        $header = implode('-', array_map('ucfirst', $parts));
        unset($headers[$header]);
    }

    /**
     * Extends CORS headers adding `Authorization` to `Access-Control-Allow-Headers`
     * and expose BEdita custom headers.
     *
     * @param array $headers Array of headers
     * @return array
     */
    protected function extendsCors(array $headers): array
    {
        $headers['Access-Control-Allow-Headers'] .= ', Authorization';
        $headers['Access-Control-Expose-Headers'] .= sprintf(
            ', %s, %s',
            Server::BEDITA_OBJECT_ID_HEADER,
            Server::BEDITA_OBJECT_TYPE_HEADER
        );

        return $headers;
    }
}
