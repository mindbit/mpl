<?php
/*
 * Mindbit PHP Library
 * Copyright (C) 2009 Mindbit SRL
 *
 * This library is free software; you can redistribute it and/or modify
 * it under the terms of version 2.1 of the GNU Lesser General Public
 * License as published by the Free Software Foundation.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 */

namespace Mindbit\Mpl\Mvc\Controller;

use Propel\Runtime\Map\TableMap;

abstract class SimpleFormRequest extends OmRequest
{
    const ACTION_NEW        = 'new';

    const STATUS_ADD        = self::ACTION_ADD;
    const STATUS_UPDATE     = self::ACTION_UPDATE;

    protected function actionFetch()
    {
        $this->om->setPrimaryKey($_REQUEST[$this->getPrimaryKeyFieldName()]);
        $om = $this->om->buildPkeyCriteria()->findOne();
        if (!$om) {
            throw new ObjectNotFoundException($this->om);
        }
        $this->om = $om;
    }

    protected function actionNew()
    {
    }

    protected function deriveAction()
    {
        if (isset($_REQUEST[static::ACTION_KEY])) {
            return $_REQUEST[static::ACTION_KEY];
        }

        return isset($_REQUEST[$this->getPrimaryKeyFieldName()]) ? self::ACTION_FETCH : self::ACTION_NEW;
    }

    public function handle()
    {
        parent::handle();

        switch ($this->action) {
            case self::ACTION_NEW:
            case self::ACTION_REMOVE:
                $this->setStatus(self::STATUS_ADD);
                break;
            case self::ACTION_ADD:
                $this->setStatus(empty($this->errors) ? self::STATUS_UPDATE: self::STATUS_ADD);
                break;
            case self::ACTION_FETCH:
            case self::ACTION_UPDATE:
                $this->setStatus(self::STATUS_UPDATE);
                break;
        }
    }

    /**
     * Extract field data from the OM into a format that is suitable for
     * inclusion into template variables and/or form.
     *
     * The base implementation in this class is just a wrapper for the OM
     * omToArray() method. Subclasses can reimplement this method to do
     * additional processing on the data. Examples:
     *  - Some OM fields need to be broken down into multiple form fields;
     *  - Additonal data needs to be fetched from different objects.
     *
     * @return array
     */
    public function getFormData()
    {
        return $this->omToArray(TableMap::TYPE_FIELDNAME);
    }
}
