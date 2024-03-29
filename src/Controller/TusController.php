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
namespace BEdita\Tus\Controller;

use BEdita\API\Controller\AppController;
use BEdita\API\Policy\EndpointPolicy;
use BEdita\Tus\Event\UploadListener;
use BEdita\Tus\Http\ResponseTrait;
use BEdita\Tus\Http\ServerFactory;
use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\Http\Exception\BadRequestException;
use TusPhp\Events\UploadComplete;

/**
 * Tus controller. Expose Tus server.
 *
 * @property \BEdita\Core\Model\Table\ObjectTypesTable $ObjectTypes
 * @property \BEdita\Tus\Controller\Component\UploadComponent $Upload
 */
class TusController extends AppController
{
    use ResponseTrait;

    /**
     * The media object types supported.
     *
     * @var \Cake\Datasource\ResultSetInterface
     */
    protected $allowedTypes = null;

    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        parent::initialize();
        if ($this->components()->has('JsonApi')) {
            $this->components()->unload('JsonApi');
        }
        $this->request = $this->request->withAttribute(EndpointPolicy::DEFAULT_AUTHORIZED, true);

        $this->ObjectTypes = $this->fetchTable('ObjectTypes');
    }

    /**
     * @inheritDoc
     */
    protected function checkAcceptable(): void
    {
    }

    /**
     * Before filter operations.
     *
     * @param \Cake\Event\EventInterface $event The event
     * @return void
     */
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);

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

        $listener = new UploadListener(['objectType' => $objectType] + $tusConf);
        $server->event()->addListener(UploadComplete::NAME, [$listener, 'onUploadComplete']);

        $tusResponse = $server->serve();

        return $this->toCakeResponse($tusResponse);
    }
}
