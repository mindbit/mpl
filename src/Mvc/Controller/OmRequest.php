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

use Mindbit\Mpl\Util\Propel;
use Propel\Runtime\Map\TableMap;

abstract class OmRequest extends BaseRequest
{
    const ACTION_FETCH  = 'fetch';
    const ACTION_ADD    = 'add';
    const ACTION_UPDATE = 'update';
    const ACTION_REMOVE = 'remove';

    protected $data = array();

    protected $om;

    /**
     * @var \Propel\Runtime\Map\TableMap
     */
    protected $tableMap;

    protected $omFieldNames;

    abstract protected function createOm();
    abstract protected function actionFetch();

    public static function getTableMapInstance($om)
    {
        $omReflection = new \ReflectionClass($om);
        $tableMapReflection = new \ReflectionClass($omReflection->getConstant('TABLE_MAP'));
        return $tableMapReflection->getMethod('getTableMap')->invoke(null);
    }

    public function __construct()
    {
        $this->om = $this->createOm();
        $this->tableMap = $this->getTableMapInstance($this->om);
        $this->omFieldNames = $this->tableMap->getFieldNames(TableMap::TYPE_FIELDNAME);
        $this->errors = array();
    }

    public function getOm()
    {
        return $this->om;
    }

    public function getPrimaryKeyFieldName()
    {
        return key($this->tableMap->getPrimaryKeys());
    }

    protected function setOmFields($data)
    {
        foreach ($data as $field => $value) {
            $pos = $this->tableMap->translateFieldName(
                $field,
                TableMap::TYPE_FIELDNAME,
                TableMap::TYPE_NUM
            );
            $this->om->setByPosition($pos, $value);
        }

        if ($this->action != self::ACTION_UPDATE) {
            return;
        }

        // On update, we want to do just a SQL UPDATE query and avoid an extra
        // SELECT query to hydrate the object. This works if we set the primary
        // key value on the OM object and call save(), but we need to do some
        // extra work to ensure all columns are updated. By default, Propel
        // excludes unmodified columns from the UPDATE query, but, because the
        // object is not hydrated, it compares the column values that we set
        // with the default column values (the constructor initializes all OM
        // fields to the default value). Without the extra handling, the side
        // effect is that columns can never be updated to the default value.

        $class = new \ReflectionClass($this->om);
        $property = $class->getProperty('modifiedColumns');
        $property->setAccessible(true);
        $modifiedColumns = $property->getValue($this->om);
        foreach ($data as $field => $value) {
            $col = $this->tableMap->translateFieldName(
                $field,
                TableMap::TYPE_FIELDNAME,
                TableMap::TYPE_COLNAME
            );
            $modifiedColumns[$col] = true;
        }
        $property->setValue($this->om, $modifiedColumns);
    }

    protected function omToArray($om = null)
    {
        $ret = array();
        $om = (array)($om ?: $this->om);
        foreach ($this->omFieldNames as $field) {
            $val = $om[Propel::PROTECTED_MAGIC . $field];
            $column = $this->tableMap->getColumn($field);
            /* Blob columns are read by propel into a memory buffer and
               are returned to the user as a resource of type stream.
               Since those cannot be json encoded, and we need that in
               all our SmartClient applications, we need to read the
               buffer contents into a string.
               FIXME: does this still apply to Propel 2 ?
             */
            if (is_resource($val) && $column->isLob()) {
                $val = stream_get_contents($val);
            }
            $ret[$field] = $val === null ? '' : $val;
        }
        return $ret;
    }

    protected function arrayToOm($data = null)
    {
        if ($data === null) {
            $data = $this->data;
        }
        $ret = array();
        foreach ($this->arrayToOmFieldNames() as $field) {
            if (!isset($data[$field])) {
                continue;
            }
            $column = $this->tableMap->getColumn($field);
            $value = $data[$field];
            // For text and numeric columns that can be null we translate '' to null.
            if ($data[$field] === '' && ($column->isText() || $column->isNumeric()) &&
                    !$column->isNotNull()) {
                $value = null;
            }
            $ret[$field] = $value;
        }
        return $ret;
    }

    protected function arrayToOmFieldNames()
    {
        return $this->omFieldNames;
    }

    protected function validate()
    {
        return true;
    }

    /**
     * Add or update a database record.
     *
     * Both actionAdd() and actionUpdate() are wrappers for this method that just set a value
     * for the $new parameter. The parameter is passed directly to the Propel BaseObject class.
     *
     * The Propel BaseObject::doSave() method decides whether it does an INSERT or UPDATE based
     * on the $new flag. The default value is true (set as part of the BaseObject class).
     *
     * @param bool $new
     */
    protected function doSave()
    {
        $this->setOmFields($this->arrayToOm());
        if (!$this->validate()) {
            return;
        }
        /* FIXME is there anything equivalent to validate() in Propel 2 ?
        if (!$this->om->validate()) {
            $this->errors = array_merge($this->errors, $this->om->getValidationFailures());
            return;
        }
        */
        $this->om->setNew($this->action == self::ACTION_ADD);
        $this->omSave();
    }

    protected function omSave()
    {
        if (empty($this->errors)) {
            try {
                $this->om->save();
            } catch (\Exception $e) {
                $this->errors[] = $e->getMessage();
            }
        }
    }

    protected function actionAdd()
    {
        $this->doSave();
    }

    protected function actionUpdate()
    {
        $this->doSave();
    }

    protected function actionRemove()
    {
        $this->setOmFields($this->arrayToOm());
        $this->om->delete();
    }
}
