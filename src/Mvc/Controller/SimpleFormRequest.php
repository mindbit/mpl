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

abstract class SimpleFormRequest extends OmRequest
{
    const ACTION_NEW        = 'new';

    const STATUS_ADD        = self::ACTION_ADD;
    const STATUS_UPDATE     = self::ACTION_UPDATE;

    /* FIXME
    protected $prefixDataMapping = array();
    */

    /**
     * Return an array that maps request field names to OM field names.
     *
     * Keys are the OM field names and values are the corresponding
     * request field names.
     */
    protected function getRequestDataMapping()
    {
        return array();
    }

    /* FIXME
    protected function prefixRequestDataMapping($prefix)
    {
        if (isset($this->prefixDataMapping[$prefix])) {
            return $this->prefixDataMapping[$prefix];
        }
        $map = array();
        foreach ($this->omFieldNames as $omField) {
            $map[$omField] = $prefix . $omField;
        }
        $this->prefixDataMapping[$prefix] = $map;
        return $map;
    }
    */

    protected function getRequestData()
    {
        $data = array();
        $map = $this->getRequestDataMapping();

        foreach ($this->omFieldNames as $omField) {
            $requestField = isset($map[$omField]) ?: $omField;
            if (isset($_REQUEST[$requestField])) {
                $data[$omField] = $_REQUEST[$requestField];
            }
        }

        return $data;
    }

    protected function actionFetch()
    {
        $this->om->setPrimaryKey($_REQUEST[$this->getPrimaryKeyFieldName()]);
        $this->om = $this->om->buildPkeyCriteria()->findOne();
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

    public function getPrimaryKeyFieldName()
    {
        $pk = parent::getPrimaryKeyFieldName();
        $map = $this->getRequestDataMapping();
        return @$map[$pk] ?: $pk;
    }

    public function handle()
    {
        $this->data = $this->getRequestData();
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

    public function getFormData()
    {
        $formData = array();
        $omData = $this->omToArray();
        $map = $this->getRequestDataMapping();

        foreach ($this->omFieldNames as $omField) {
            $formData[isset($map[$omField]) ?: $omField] = $omData[$omField];
        }

        return $formData;
    }
}
