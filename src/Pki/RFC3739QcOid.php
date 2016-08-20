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

namespace Mindbit\Mpl\Pki;

class RFC3739QcOid
{
    public function __construct()
    {
        $this->qcs = new AsnObjectId("1.3.6.1.5.5.7.11");
        $this->qcsPkixQcSyntaxV1 = $this->qcs->branch("1");
        $this->qcsPkixQcSyntaxV2 = $this->qcs->branch("2");
    }

    public function getOidMap()
    {
        return array(
                $this->qcsPkixQcSyntaxV1->getData() => "PKIX QCSyntax-v1",
                $this->qcsPkixQcSyntaxV2->getData() => "PKIX QCSyntax-v2"
                );
    }
}
