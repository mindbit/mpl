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

namespace Mindbit\Mpl\SmartClient;

use Mindbit\Mpl\SmartClient\RestRequest;

abstract class ConcurrentRestRequest extends RestRequest
{
    public function doSave()
    {
        if ($this->operationType == self::OPERATION_ADD) {
            return parent::doSave();
        }

        // Determine the primary key field name
        $pk = $this->om->buildPkeyCriteria()->keys();
        $pk = $this->omPeer->translateFieldName($pk[0], BasePeer::TYPE_COLNAME, BasePeer::TYPE_FIELDNAME);

        // Replace $this->data with only the fields that have changed
        $__d = $this->data;
        $this->data = array();
        foreach ($__d as $k => $v) {
            if ($v !== $this->oldValues[$k]) {
                $this->data[$k] = $v;
            }
        }

        // Replace "stub" OM with the real OM.
        //
        // FIXME for now we have no row locking support: see
        // http://www.propelorm.org/wiki/Documentation/1.5/Transactions#Limitations
        // for further details. As soon as we do, this should be nested in a
        // transaction.

        // FIXME BEGIN TRANSACTION
        $this->om = $this->omPeer->retrieveByPK($__d[$pk]); // FIXME "... FOR UPDATE"
        parent::doSave();
        // FIXME COMMIT TRANSACTION
    }
}
