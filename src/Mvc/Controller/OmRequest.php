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

    protected $om;

    /**
     * @var \Propel\Runtime\Map\TableMap
     */
    protected $tableMap;

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

    /**
     * OM fromArray() wrapper
     *
     * On update, we want to do just a SQL UPDATE query and avoid an extra
     * SELECT query to hydrate the object. This works if we set the primary
     * key value on the OM object and call save(), but we need to do some
     * extra work to ensure all columns are updated. By default, Propel
     * excludes unmodified columns from the UPDATE query, but, because the
     * object is not hydrated, it compares the column values that we set
     * with the default column values (the constructor initializes all OM
     * fields to the default value). Without the extra handling, the side
     * effect is that columns can never be updated to the default value.
     *
     * @param array  $arr     An array to populate the object from
     * @param string $keyType The type of keys the array uses
     * @param string $prefix  Prefix to add to OM key before looking up in $arr
     * @return void
     */
    protected function omFromArray($arr, $keyType = TableMap::TYPE_PHPNAME)
    {
        $this->om->fromArray($arr, $keyType);

        if ($this->action != self::ACTION_UPDATE) {
            return;
        }

        $class = new \ReflectionClass($this->om);
        $property = $class->getProperty('modifiedColumns');
        $property->setAccessible(true);

        $modifiedColumns = $property->getValue($this->om);
        foreach ($this->tableMap->getFieldNames($keyType) as $field) {
            if (array_key_exists($field, $arr)) {
                $col = $this->tableMap->translateFieldName($field, $keyType, TableMap::TYPE_COLNAME);
                $modifiedColumns[$col] = true;
            }
        }
        $property->setValue($this->om, $modifiedColumns);
    }

    /**
     * OM toArray() wrapper
     *
     * Blob columns are read by propel into a memory buffer and are returned to
     * the user as a resource of type stream. This is not very useful and, since
     * we don't expect very large values, we convert them back to PHP strings.
     *
     * @param string $keyType The type of keys the returned array uses
     * @return array
     */
    protected function omToArray($keyType = TableMap::TYPE_PHPNAME)
    {
        $ret = $this->om->toArray($keyType);
        foreach ($ret as $key => $value) {
            if (is_resource($value)) {
                $name = $this->tableMap->translateFieldName($name, $keyType, TableMap::TYPE_COLNAME);
                $column = $this->tableMap->getColumn($name);
                if ($column->isLob()) {
                    $ret[$key] = stream_get_contents($value);
                }
            }
        }
        return $ret;
    }

    /**
     * Extract request data and prepare it for importing into OM
     *
     * The data that is returned by this function is used with omFromArray() to
     * import it into the OM. It is OK to return more fields than the OM expects
     * because omFromArray() only imports the fields that are relevant to the OM.
     *
     * The base implementation in this class just returns $_REQUEST. Subclasses
     * can reimplement this method if they need to get the data from a different
     * source and/or do additional processing before the data is imported into
     * the OM. Examples:
     *  - Request data is encapsulated in another format (JSON, XML, etc.);
     *  - Request field names are prefixed and the prefix needs to be removed
     *    before fields are passed to omFromArray();
     *  - Multiple request fields need to be aggregated into a single OM field;
     *  - Some request fields need to be processed before they are passed to
     *    omFromArray(), e.g. trimmed.
     *
     * @return array
     */
    protected function getRequestData()
    {
        return $_REQUEST;
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
        $this->omFromArray($this->getRequestData(), TableMap::TYPE_FIELDNAME);
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
