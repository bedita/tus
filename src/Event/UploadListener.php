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
namespace BEdita\Tus\Event;

use BEdita\Core\Filesystem\FilesystemRegistry;
use BEdita\Core\Model\Action\GetObjectAction;
use BEdita\Core\Model\Action\SaveEntityAction;
use BEdita\Core\Model\Entity\ObjectEntity;
use BEdita\Core\Model\Entity\ObjectType;
use BEdita\Core\Model\Table\MediaTable;
use BEdita\Core\Model\Table\StreamsTable;
use Cake\Core\InstanceConfigTrait;
use Cake\Http\Exception\InternalErrorException;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Utility\Hash;
use TusPhp\Events\TusEvent;
use TusPhp\File;

/**
 * Upload listener
 */
class UploadListener
{
    use InstanceConfigTrait;
    use LocatorAwareTrait;

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

        // force a new instance of StreamsTable to ensure to not modifing existing one
        $this->Streams = $this->getTableLocator()->get('TusStreams', [
            'className' => StreamsTable::class,
        ]);

        $this->Streams->addBehavior('BEdita/Tus.RelaxStreams');
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
                self::BEDITA_OBJECT_ID_HEADER => $entity->id,
                self::BEDITA_OBJECT_TYPE_HEADER => $entity->type,
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
            /** @var \BEdita\Core\Model\Entity\ObjectType $objectType */
            $objectType = $this->getConfig('objectType');

            // move file to default place and save stream
            $srcPath = sprintf('%s://%s/%s', $this->getConfig('filesystem'), $this->getConfig('uploadDir'), $fileMeta['name']);

            $stream = $this->Streams->newEntity();
            $stream->file_name = $fileMeta['name'];
            $stream->mime_type = Hash::get($fileMeta, 'metadata.type');
            $stream->uri = $stream->filesystemPath();
            $stream->file_size = Hash::get($fileMeta, 'size');
            $stream->hash_md5 = '';
            $stream->hash_sha1 = '';

            $mountManager = FilesystemRegistry::getMountManager();
            if (!$mountManager->move($srcPath, $stream->uri)) {
                throw new InternalErrorException(sprintf('Error moving file in %s destination', $stream->uri));
            }

            // create media type
            $entity = $this->Table->newEntity();
            $entity->set('type', $this->Table->objectType()->name);
            $data = ['title' => $fileMeta['name']];
            $action = new SaveEntityAction(['table' => $this->Table]);
            $entity = $action(compact('entity', 'data'));

            // save stream and create related object
            $stream->object_id = $entity->id;
            $action = new SaveEntityAction(['table' => $this->Streams]);
            $stream = $action([
                'entity' => $stream,
                'data' => [],
                'entityOptions' => ['validate' => 'relax'],
            ]);

            $action = new GetObjectAction(['table' => $this->Table, 'objectType' => $objectType]);

            return $action(['primaryKey' => $entity->id]);
        });
    }
}
