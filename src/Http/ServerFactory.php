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
namespace BEdita\Tus\Http;

use BEdita\AWS\Filesystem\Adapter\S3Adapter;
use BEdita\Core\Filesystem\Adapter\LocalAdapter;
use BEdita\Core\Filesystem\FilesystemRegistry;
use BEdita\Tus\Middleware\Tus\HeadersMiddleware;
use BEdita\Tus\Middleware\Tus\TrustProxiesMiddleware;
use Cake\Core\InstanceConfigTrait;
use Cake\Http\Exception\InternalErrorException;
use TusPhp\Config as TusConfig;

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
        'filesystem' => 'tus',
        'uploadDir' => 'uploads',
        'cache' => 'file',
        'server' => null,
        'endpoint' => '/tus',
        'headers' => null,
        'trustedProxies' => null,
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
     * @var \BEdita\Tus\Http\Server
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
     * @return \BEdita\Tus\Http\Server
     */
    public static function create(array $config = []): Server
    {
        return (new self($config))->getServer();
    }

    /**
     * Get configured Tus Server.
     *
     * @return \BEdita\Tus\Http\Server
     */
    public function getServer(): Server
    {
        $this->setupFilesystem()->ensureUploadDir();

        TusConfig::set($this->getConfig('server'));
        $this->tusServer = new Server($this->getConfig('cache'));

        $headersMiddleware = new HeadersMiddleware((array)$this->getConfig('headers'));
        $trustedProxies = new TrustProxiesMiddleware((array)$this->getConfig('trustedProxies'));
        $this->tusServer->middleware()
            ->add($headersMiddleware)
            ->add($trustedProxies);

        return $this->tusServer
            ->setUploadDir($this->uploadPath)
            ->setApiPath($this->getConfig('endpoint'));
    }

    /**
     * Ensure upload directory.
     *
     * @return void
     * @throws \League\Flysystem\UnableToCreateDirectory
     */
    protected function ensureUploadDir(): void
    {
        $uploadDir = $this->getConfig('uploadDir');
        $manager = FilesystemRegistry::getMountManager();
        $dir = sprintf('%s://%s', $this->getConfig('filesystem'), $uploadDir);
        if ($manager->fileExists($dir)) {
            return;
        }

        $manager->createDirectory($dir);
    }

    /**
     * Setup filesystem and upload path.
     *
     * @return $this
     */
    protected function setupFilesystem()
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
                $this->getConfig('uploadDir')
            );

            return $this;
        }

        throw new InternalErrorException('Filesystem not supported.');
    }
}
