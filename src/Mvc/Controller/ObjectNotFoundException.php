<?php
/*
 * Mindbit PHP Library
 * Copyright (C) 2017 Mindbit SRL
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

class ObjectNotFoundException extends \Exception
{
    public function __construct($om, $previous = null)
    {
        $tableMapReflection = new \ReflectionClass(OmRequest::getTableMapInstance($om));
        parent::__construct(sprintf(
            'Cannot find record with key \'%s\' in table %s.%s (class %s)',
            $om->getPrimaryKey(),
            $tableMapReflection->getConstant('DATABASE_NAME'),
            $tableMapReflection->getConstant('TABLE_NAME'),
            get_class($om)
        ), 0, $previous);
    }
}