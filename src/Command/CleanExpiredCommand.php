<?php
declare(strict_types=1);

/**
 * BEdita, API-first content management framework
 * Copyright 2022 Atlas Srl, Chialab Srl
 *
 * This file is part of BEdita: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * See LICENSE.LGPL or <http://gnu.org/licenses/lgpl-3.0.html> for more details.
 */

namespace BEdita\Tus\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Core\Configure;
use Cake\Utility\Hash;
use Carbon\Carbon;
use TusPhp\Config as TusConfig;
use TusPhp\Tus\Server as TusServer;

/**
 * Remove expired items (finished or unfinished) from TUS cache.
 */
class CleanExpiredCommand extends Command
{
    /**
     * Update gettext po files.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return null|void|int The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io)
    {
        $io->out([
            '<info>Cleaning server resources</info>',
            '<info>=========================</info>',
            '',
        ]);

        $server = $this->tusServer();
        $deleted = $server->handleExpiration();

        if (empty($deleted)) {
            $io->out('<comment>Nothing to delete.</comment>');
        } else {
            foreach ($deleted as $key => $item) {
                $io->out('<comment>' . ($key + 1) . ". Deleted {$item['name']} </comment>");
            }
        }

        $io->out('Done');

        return null;
    }

    protected function tusServer(): TusServer
    {
        TusConfig::set(Configure::read('Tus.server'));

        return new class (Configure::read('Tus.cache')) extends TusServer
        {
            protected function isExpired($contents): bool
            {
                $expiresAt = Hash::get((array)$contents, 'expires_at');

                return empty($expiresAt) || Carbon::parse($expiresAt)->lt(Carbon::now());
            }
        };
    }
}