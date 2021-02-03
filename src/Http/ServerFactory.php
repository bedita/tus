<?php

namespace BEdita\Tus\Http;

use BEdita\AWS\Filesystem\Adapter\S3Adapter;
use BEdita\Core\Filesystem\Adapter\LocalAdapter;
use BEdita\Core\Filesystem\FilesystemRegistry;
use Cake\Core\InstanceConfigTrait;
use Cake\Http\Exception\InternalErrorException;
use TusPhp\Config as TusConfig;
use TusPhp\Tus\Server;

/**
 * Factory for Tus Server
 */
class ServerFactory
{
    use InstanceConfigTrait;

    /**
     * Default configuration.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'filesystem' => 'default',
        'uploadDir' => 'uploads',
        'cache' => 'file',
        'server' => null,
        'endpoint' => '/tus',
    ];

    /**
     * The upload path
     *
     * @var string
     */
    protected $uploadPath = null;

    /**
     * Instance of Tus server
     *
     * @var \TusPhp\Tus\Server
     */
    protected $tusServer = null;

    /**
     * Create ServerFactory instance.
     *
     * @param array $config The configuration
     */
    public function __construct(array $config = [])
    {
        if (!empty($config['endpoint']) && strpos($config['endpoint'], '/') !== 0) {
            $config['endpoint'] = '/' . $config['endpoint'];
        }
        $this->setConfig($config);
    }

    /**
     * Create Tus server.
     *
     * @param array $config Configuration
     * @return \TusPhp\Tus\Server
     */
    public static function create(array $config = []): Server
    {
        return (new self($config))->getServer();
    }

    /**
     * Get configured Tus Server.
     *
     * @return \TusPhp\Tus\Server
     */
    public function getServer(): Server
    {
        $this->setupFilesystem()->ensureUploadDir();

        TusConfig::set($this->getConfig('server'));
        $this->tusServer = new Server($this->getConfig('cache'));

        return $this->tusServer
            ->setUploadDir($this->uploadPath)
            ->setApiPath($this->getConfig('endpoint'));
    }

    /**
     * Ensure upload directory.
     *
     * @return bool
     */
    protected function ensureUploadDir(): bool
    {
        $uploadDir = $this->getConfig('uploadDir');
        $manager = FilesystemRegistry::getMountManager();
        $fs = $manager->getFilesystem($this->getConfig('filesystem'));
        if ($fs->has($uploadDir)) {
            return true;
        }

        return $fs->createDir($uploadDir);
    }

    /**
     * Setup filesystem and upload path.
     *
     * @return $this
     */
    protected function setupFilesystem(): ServerFactory
    {
        $adapter = FilesystemRegistry::getInstance()->get($this->getConfig('filesystem'));

        // local adapter ready to use
        if ($adapter instanceof LocalAdapter) {
            $this->uploadPath = $adapter->getConfig('path') . DS . $this->getConfig('uploadDir');

            return $this;
        }

        // for S3 register stream wrapper https://www.php.net/manual/en/class.streamwrapper.php
        if ($adapter instanceof S3Adapter) {
            /** @var \League\Flysystem\AwsS3v3\AwsS3Adapter $innerAdapter */
            $innerAdapter = $adapter->getInnerAdapter();
            $innerAdapter->getClient()->registerStreamWrapper();

            $this->uploadPath = sprintf(
                's3://%s/%s',
                $adapter->getConfig('host'), // bucket.
                $this->getConfig('uploadDir'),
            );

            return $this;
        }

        throw new InternalErrorException('Filesystem not supported.');
    }
}
