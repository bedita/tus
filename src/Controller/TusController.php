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
namespace BEdita\Tus\Controller;

use BEdita\Tus\Event\UploadListener;
use BEdita\Tus\Http\ResponseTrait;
use BEdita\Tus\Http\ServerFactory;
use BEdita\Tus\Middleware\Tus\CorsExtenderMiddleware;
use BEdita\Tus\Middleware\Tus\TrustProxiesMiddleware;
use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\Http\Exception\BadRequestException;
use Cake\Utility\Hash;
use TusPhp\Events\UploadComplete;

/**
 * Tus controller. Expose Tus server.
 *
 * @property-read \BEdita\Core\Model\Table\ObjectTypesTable $ObjectTypes
 * @property-read \BEdita\Tus\Controller\Component\UploadComponent $Upload
 */
class TusController extends Controller
{
    use ResponseTrait;

    /**
     * The media object types supported.
     *
     * @var \Cake\Datasource\ResultSetInterface
     */
    protected $allowedTypes = null;

    /**
     * {@inheritDoc}
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('Auth', [
            'authenticate' => ['BEdita/API.Jwt'],
            'loginAction' => ['_name' => 'api:login'],
            'loginRedirect' => ['_name' => 'api:login'],
            'unauthorizedRedirect' => false,
            'storage' => 'Memory',
        ]);

        $this->loadModel('ObjectTypes');
    }

    /**
     * Before filter operations.
     *
     * @param \Cake\Event\Event $event The event
     * @return void
     */
    public function beforeFilter(Event $event): void
    {
        parent::beforeFilter($event);

        if ($this->request->getMethod() === 'OPTIONS') {
            $this->Auth->allow('server');
        }

        $mediaId = $this->ObjectTypes->get('media')->id;
        $this->allowedTypes = $this->ObjectTypes->find('children', ['for' => $mediaId])
            ->where([$this->ObjectTypes->aliasField('enabled') => true])
            ->all();
    }

    /**
     * Create tus server.
     *
     * @param string $type The object type
     * @return \Cake\Http\Response
     */
    public function server($type)
    {
        $objectType = $this->allowedTypes->firstMatch(['name' => $type]);
        if (!$objectType instanceof EntityInterface) {
            throw new BadRequestException(sprintf('Unsupported type %s', $type));
        }

        $tusConf = Configure::read('Tus');
        $tusConf['endpoint'] .= '/' . $type;
        $server = ServerFactory::create($tusConf);

        $trustedProxies = new TrustProxiesMiddleware((array)Hash::get($tusConf, 'trustedProxies'));
        $server->middleware()
            ->add(CorsExtenderMiddleware::class)
            ->add($trustedProxies);

        $listener = new UploadListener(['objectType' => $objectType] + $tusConf);
        $server->event()->addListener(UploadComplete::NAME, [$listener, 'onUploadComplete']);

        $tusResponse = $server->serve();

        return $this->toCakeResponse($tusResponse);
    }
}
