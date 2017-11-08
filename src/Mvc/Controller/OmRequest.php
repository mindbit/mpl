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

    public function __construct()
    {
        $this->om = $this->createOm();
        $omReflection = new \ReflectionClass($this->om);
        $tableMapReflection = new \ReflectionClass($omReflection->getConstant('TABLE_MAP'));
        $this->tableMap = $tableMapReflection->getMethod('getTableMap')->invoke(null);
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
            $setter = 'set' . $this->tableMap->translateFieldName(
                $field,
                TableMap::TYPE_FIELDNAME,
                TableMap::TYPE_PHPNAME
            );
            // The following block worksaround the following issue: when text
            // fields are set to their default value during update, they are
            // not actually saved into the database. Look at the doSave()
            // method below for the full comment.
            //
            // All this mess is because BaseObject::$modifiedColumns is
            // protected and therefore we cannot explicitly set the column
            // as modified.
            /* FIXME still needed for Propel2 ?
            if ($this->operationType == self::OPERATION_UPDATE) {
                $column = $tableMap->getColumn($field);
                if ($column->isText()) {
                    call_user_func(array($this->om, "set".$phpName), '_' . $data[$field]);
                }
            }
            */
            $this->om->$setter($data[$field]);
        }
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
    protected function actionSave($new)
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
        // Intentionally call setNew() *AFTER* setOmFields() was called, because
        // otherwise updating a field to its default value would not work (the OM
        // class constructor sets all fields to their default values and all
        // setter methods check if we actually change the value)
        //
        // Actually, this only works for integer type fields, where the Propel
        // generated setter code looks something like this:
        //     if ($this->reinnoire !== $v || $this->isNew()) {
        //         $this->reinnoire = $v;
        //         $this->modifiedColumns[] = DgsCertificatPeer::REINNOIRE;
        //     }
        //
        // On the other hand, for text fields the "new" state is not checked:
        //     if ($this->cert_ai_org !== $v) {
        //         $this->cert_ai_org = $v;
        //         $this->modifiedColumns[] = DgsCertificatPeer::CERT_AI_ORG;
        //     }
        //
        // The case is almost the same for DateTime fields, but that's more
        // complex.
        $this->om->setNew($new);
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
        $this->actionSave(true);
    }

    protected function actionUpdate()
    {
        $this->actionSave(false);
    }

    protected function actionRemove()
    {
        $this->setOmFields($this->arrayToOm());
        $this->om->delete();
    }
}
