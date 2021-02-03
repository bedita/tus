<?php
namespace BEdita\Tus\Middleware;

use BEdita\AWS\Filesystem\Adapter\S3Adapter;
use BEdita\Core\Filesystem\Adapter\LocalAdapter;
use BEdita\Core\Filesystem\FilesystemRegistry;
use BEdita\Tus\Event\UploadListener;
use Cake\Core\InstanceConfigTrait;
use Cake\Http\Exception\InternalErrorException;
use Laminas\Diactoros\Stream;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TusPhp\Config as TusConfig;
use TusPhp\Events\TusEvent;
use TusPhp\Tus\Server;

/**
 * TusMiddleware middleware
 */
class TusMiddleware
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
        'server' => null,
        'endpoint' => '/tus',
    ];

    protected $uploadPath = null;

    /**
     * Constructor.
     * Setup the middleware.
     *
     * @param array $config The Tus server configuration.
     */
    public function __construct(array $config)
    {
        if (!empty($config['endpoint']) && strpos($config['endpoint'], '/') !== 0) {
            $config['endpoint'] = '/' . $config['endpoint'];
        }
        $this->setConfig($config);

        TusConfig::set($this->getConfig('server'));
    }

    /**
     * Invoke method.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Message\ResponseInterface $response The response.
     * @param callable $next Callback to invoke the next middleware.
     * @return \Psr\Http\Message\ResponseInterface A response
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next)
    {
        $path = $request->getUri()->getPath();
        if ($request->getMethod() === 'OPTIONS' && strpos($path, $this->getConfig('endpoint')) === 0) {
            $next($request, $response); // skip CORS middleware
        }

        return $next($request, $response);
    }
}
