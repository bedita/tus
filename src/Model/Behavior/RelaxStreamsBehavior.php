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
namespace BEdita\Tus\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\Validation\Validator;

/**
 * Relax some stream rules.
 */
class RelaxStreamsBehavior extends Behavior
{
    /**
     * Default configuration.
     *
     * @var array
     */
    protected $_defaultConfig = [];

    /**
     * Remove BEdita/Core.Uploadable behavior
     *
     * @param array $config The configuration
     * @return void
     */
    public function initialize(array $config): void
    {
        if ($this->_table->behaviors()->has('Uploadable')) {
            $this->_table->removeBehavior('Uploadable');
        }
    }

    /**
     * Relax validatation.
     *
     * @param \Cake\Validation\Validator $validator The validator.
     * @return \Cake\Validation\Validator
     */
    public function validationRelax(Validator $validator): Validator
    {
        return $this->_table->validationDefault($validator)
            ->remove('file_name')
            ->remove('mime_type')
            ->remove('contents');
    }
}
