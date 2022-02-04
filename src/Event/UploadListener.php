<?php
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
namespace BEdita\Tus\Event;

use BEdita\Core\Filesystem\FilesystemRegistry;
use BEdita\Core\Model\Action\GetObjectAction;
use BEdita\Core\Model\Action\SaveEntityAction;
use BEdita\Core\Model\Entity\ObjectEntity;
use BEdita\Core\Model\Entity\ObjectType;
use BEdita\Core\Model\Table\MediaTable;
use BEdita\Tus\Http\Server;
use Cake\Core\InstanceConfigTrait;
use Cake\Event\EventDispatcherTrait;
use Cake\Log\LogTrait;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Utility\Hash;
use TusPhp\Events\TusEvent;
use TusPhp\File;

/**
 * Upload listener
 */
class UploadListener
{
    use EventDispatcherTrait;
    use InstanceConfigTrait;
    use LocatorAwareTrait;
    use LogTrait;

    /**
     * StreamsTable instance.
     *
     * @var \BEdita\Core\Model\Table\StreamsTable
     */
    protected $Streams = null;

    /**
     * Table instance for media type
     *
     * @var \BEdita\Core\Model\Table\ObjectsBaseTable
     */
    protected $Table = null;

    /**
     * Default configuration.
     *
     * - `filesystem` the filesystem to use
     * - `uploadDir` the upload dir
     * - `objectType` the object type entity related to file you want to upload
     *
     * @var array
     */
    protected $_defaultConfig = [
        'filesystem' => 'tus',
        'uploadDir' => 'uploads',
        'objectType' => null, // required
    ];

    /**
     * Set configuration and initialize models
     *
     * @param array $config The configuration.
     */
    public function __construct(array $config = [])
    {
        $this->setConfig($config);
        $objectType = $this->getConfig('objectType');
        if (!$objectType instanceof ObjectType) {
            throw new \InvalidArgumentException('Missing "objectType" entity or not valid');
        }

        $this->setTable($objectType->alias);

        $this->Streams = $this->getTableLocator()->get('Streams');
    }

    /**
     * Setup Table.
     *
     * @param string $table The table name
     * @return void
     * @throws \InvalidArgumentException If table doesn't represent a media
     */
    protected function setTable(string $table)
    {
        $this->Table = $this->getTableLocator()->get($table);

        if ($this->Table instanceof MediaTable || $this->Table->isTableInherited('Media', true)) {
            return;
        }

        throw new \InvalidArgumentException(sprintf('table %s must represent a media', $table));
    }

    /**
     * On upload complete action.
     *
     * @param \TusPhp\Events\TusEvent $event The Tus event.
     * @return \TusPhp\Events\TusEvent
     */
    public function onUploadComplete(TusEvent $event)
    {
        $response = $event->getResponse();
        try {
            $entity = $this->finalize($event->getFile());
            $response->setHeaders([
                Server::BEDITA_OBJECT_ID_HEADER => $entity->id,
                Server::BEDITA_OBJECT_TYPE_HEADER => $entity->type,
            ]);
        } catch (\Exception $e) {
            throw $e;
        }

        return $event;
    }

    /**
     * Finalize the upload doing:
     *
     * - move file to the final destination
     * - create media object type
     * - create stream associated to file and media object type
     *
     * @param \TusPhp\File $file The file to upload.
     * @return \BEdita\Core\Model\Entity\ObjectEntity
     */
    protected function finalize(File $file): ObjectEntity
    {
        return $this->Table->getConnection()->transactional(function () use ($file) {
            $fileMeta = $file->details();

            // create media type
            $entity = $this->Table->newEntity();
            $entity->set('type', $this->Table->objectType()->name);
            $data = [
                'title' => Hash::get($fileMeta, 'metadata.title', $fileMeta['name']),
            ];
            $action = new SaveEntityAction(['table' => $this->Table]);
            $entity = $action(compact('entity', 'data'));

            /** @var \BEdita\Core\Model\Entity\ObjectType $objectType */
            $objectType = $this->getConfig('objectType');

            $stream = $this->Streams->newEntity();

            $resource = fopen($file->getFilePath(), 'r');
            $streamData = [
                'file_name' => Hash::get($fileMeta, 'name'),
                'mime_type' => Hash::get($fileMeta, 'metadata.type'),
                'contents' => $resource,
            ];

            // save stream and create related object
            $stream->object_id = $entity->id;
            $action = new SaveEntityAction(['table' => $this->Streams]);
            $stream = $action([
                'entity' => $stream,
                'data' => $streamData,
            ]);
            fclose($resource);

            // remove uploaded file
            $srcPath = sprintf('%s://%s/%s', $this->getConfig('filesystem'), $this->getConfig('uploadDir'), $fileMeta['name']);
            $mountManager = FilesystemRegistry::getMountManager();
            if (!$mountManager->delete($srcPath)) {
                $this->log(sprintf('Error removing temporary file uplaoded in %s destination', $srcPath));
            }

            $action = new GetObjectAction(['table' => $this->Table, 'objectType' => $objectType]);

            return $action(['primaryKey' => $entity->id]);
        });
    }
}
