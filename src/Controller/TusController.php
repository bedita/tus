<?php
/**
 * Rejoint BEdita plugin
 *
 * Copyright 2020 ChannelWeb Srl
 */
namespace BEdita\Tus\Controller;

use BEdita\API\Controller\AppController;
use BEdita\Tus\Event\UploadListener;
use BEdita\Tus\Http\ResponseTrait;
use BEdita\Tus\Http\ServerFactory;
use BEdita\Tus\Middleware\Tus\CorsExtenderMiddleware;
use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Http\Exception\BadRequestException;

/**
 * Tus controller. Expose Tus server.
 *
 * @property-read \BEdita\Core\Model\Table\ObjectTypesTable $ObjectTypes
 */
class TusController extends Controller
{
    use ResponseTrait;

    /**
     * The media object types supported.
     * Keys are the ids and values are the names.
     *
     * @var array
     */
    protected $allowedTypes = null;

    /**
     * {@inheritDoc}
     */
    public function initialize(): void
    {
        parent::initialize();

        if ($this->request->getMethod() !== 'OPTIONS') {
            $this->loadComponent('Auth', [
                'authenticate' => ['BEdita/API.Jwt'],
                'loginAction' => ['_name' => 'api:login'],
                'loginRedirect' => ['_name' => 'api:login'],
                'unauthorizedRedirect' => false,
                'storage' => 'Memory',
            ]);
        }

        $this->loadModel('ObjectTypes');
        $mediaId = $this->ObjectTypes->get('media')->id;
        $this->allowedTypes = $this->ObjectTypes->find('children', ['for' => $mediaId])
            ->find('list')
            ->where([$this->ObjectTypes->aliasField('enabled') => true])
            ->toArray();

        // avoid that RequestHandler tries to parse body
        // $this->RequestHandler->setConfig('inputTypeMap', [], false);
    }

    /**
     * Skip parent behavior that check content negotiation (check header Accept for json)
     *
     * @param \Cake\Event\Event $event The event
     * @return void
     */
    public function beforeFilter(Event $event): void
    {
    }

    /**
     * Create tus server.
     *
     * @param string $type The object type
     * @return \Cake\Http\Response
     */
    public function server($type)
    {
        if (!in_array($type, $this->allowedTypes)) {
            throw new BadRequestException(sprintf('Unsupported type %s', $type));
        }

        $tusConf = Configure::read('Tus');
        $tusConf['endpoint'] .= '/' . $type;
        $server = ServerFactory::create($tusConf);
        $server->middleware()->add(CorsExtenderMiddleware::class);

        $listener = new UploadListener();
        $server->event()->addListener('tus-server.upload.complete', [$listener, 'onUploadComplete']);

        $tusResponse = $server->serve();

        return $this->toCakeResponse($tusResponse);
    }
}
