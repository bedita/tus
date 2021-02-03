<?php

namespace BEdita\Tus\Event;

use BEdita\Core\Filesystem\FilesystemRegistry;
use Cake\Core\InstanceConfigTrait;
use Cake\Datasource\ModelAwareTrait;
use Cake\Utility\Hash;
use TusPhp\Events\TusEvent;

/**
 * Upload listener
 *
 * @property-read \BEdita\Core\Model\Table\StreamsTable $Streams
 */
class UploadListener
{
    use InstanceConfigTrait;
    use ModelAwareTrait;

    /**
     * Default configuration.
     *
     * - `filesystem` the filesystem to use
     * - `uploadDir`
     *
     * @var array
     */
    protected $_defaultConfig = [
        'filesystem' => 'tus',
        'uploadDir' => 'uploads',
        'type' => 'files',
    ];

    public function __construct(array $config = [])
    {
        $this->setConfig($config);
        $this->loadModel('Streams');
    }

    public function onUploadComplete(TusEvent $event)
    {
        $fileMeta = $event->getFile()->details();
        $request = $event->getRequest();
        $response = $event->getResponse();

        // move file, save stream and create related object
        $filePath = $fileMeta['file_path'];
        $mountManager = FilesystemRegistry::getMountManager();
        $srcPath = sprintf('%s://%s/%s', $this->getConfig('filesystem'), $this->getConfig('uploadDir'), $fileMeta['name']);

        $stream = $this->Streams->newEntity([
            'file_name' => $fileMeta['name'],
            'mime_type' => Hash::get($fileMeta, 'metadata.type'),
        ]);
        $stream->uri = $stream->filesystemPath();
        $stream->file_size = Hash::get($fileMeta, 'size');

        $mountManager->move($srcPath, $stream->uri);

        // modify the response adding info about object created
    }

    protected function getTypeFromMime($mimeType): string
    {

    }
}
