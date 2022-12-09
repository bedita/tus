<?php
declare(strict_types=1);

/**
 * BEdita, API-first content management framework
 * Copyright 2022 ChannelWeb Srl, Chialab Srl
 *
 * This file is part of BEdita: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * See LICENSE.LGPL or <http://gnu.org/licenses/lgpl-3.0.html> for more details.
 */
namespace BEdita\Tus\Cache;

use TusPhp\Cache\RedisStore as TusRedisStore;

class RedisStore extends TusRedisStore
{
    /**
     * @inheritDoc
     */
    public function set(string $key, $value)
    {
        $contents = $this->get($key) ?? [];

        if (\is_array($value)) {
            $contents = $value + $contents;
        } else {
            $contents[] = $value;
        }

        $status = $this->redis->set(
            $this->getPrefix() . $key,
            json_encode($contents),
            'EX',
            $this->ttl
        );

        return $status->getPayload() === 'OK';
    }
}
