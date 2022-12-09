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
namespace BEdita\Tus\Http;

use BEdita\Tus\Cache\RedisStore;
use Cake\Utility\Hash;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use TusPhp\Tus\Server as TusServer;

/**
 * Tus Server
 */
class Server extends TusServer
{
    /**
     * BEdita object id header
     *
     * @var string
     */
    public const BEDITA_OBJECT_ID_HEADER = 'BEdita-Object-Id';

    /**
     * BEdita object type header
     *
     * @var string
     */
    public const BEDITA_OBJECT_TYPE_HEADER = 'BEdita-Object-Type';

    /**
     * Update cache with info about bedita object created.
     *
     * @return void
     */
    public function updateCache(): void
    {
        $key = $this->request->key();
        $cacheData = $this->cache->get($key);
        if (!$cacheData) {
            return;
        }

        $headers = $this->response->getHeaders();
        $objectId = Hash::get($headers, static::BEDITA_OBJECT_ID_HEADER);
        $objectType = Hash::get($headers, static::BEDITA_OBJECT_TYPE_HEADER);
        if ($objectId === null) {
            return;
        }

        $cacheData['bedita'] = ['object_id' => $objectId, 'object_type' => $objectType];

        $this->cache->set($key, $cacheData);
    }

    /**
     * @inheritDoc
     */
    public function serve()
    {
        $response = parent::serve();
        $this->updateCache();

        return $response;
    }

    /**
     * {@inheritDoc}
     *
     * Add bedita headers if exists.
     */
    protected function handleHead(): HttpResponse
    {
        $key = $this->request->key();
        $cacheData = $this->cache->get($key);
        if (!$cacheData) {
            return parent::handleHead();
        }

        $objectId = Hash::get((array)$cacheData, 'bedita.object_id');
        $objectType = Hash::get((array)$cacheData, 'bedita.object_type');
        if (!empty($objectId)) {
            $this->response->setHeaders([
                static::BEDITA_OBJECT_ID_HEADER => $objectId,
                static::BEDITA_OBJECT_TYPE_HEADER => $objectType,
            ]);
        }

        return parent::handleHead();
    }

    /**
     * Set cache.
     *
     * @param mixed $cache Cache configuration
     * @return self
     */
    public function setCache($cache): self
    {
        if ($cache !== 'redis') {
            return parent::setCache($cache);
        }
        $this->cache = new RedisStore();
        $this->cache->setPrefix('tus:server:');

        return $this;
    }
}
