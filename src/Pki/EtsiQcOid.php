<?php
/*    Mindbit PHP Library
 *    Copyright (C) 2009 Mindbit SRL
 *
 *    This library is free software; you can redistribute it and/or
 *    modify it under the terms of the GNU Lesser General Public
 *    License as published by the Free Software Foundation; either
 *    version 2.1 of the License, or (at your option) any later version.
 *
 *    This library is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 *    Lesser General Public License for more details.
 *
 *    You should have received a copy of the GNU Lesser General Public
 *    License along with this library; if not, write to the Free Software
 *    Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 */

namespace Mindbit\Mpl\Pki;

class EtsiQcOid
{
    public function __construct()
    {
        $this->etsiQcs = new AsnObjectId("0.4.0.1862.1");
        $this->etsiQcsQcCompliance = $this->etsiQcs->branch("1");
        $this->etsiQcsLimitValue = $this->etsiQcs->branch("2");
        $this->etsiQcsRetentionPeriod = $this->etsiQcs->branch("3");
        $this->etsiQcsQcSSCD = $this->etsiQcs->branch("4");
    }

    public function getOidMap()
    {
        return array(
                $this->etsiQcsQcCompliance->getData() => "ETSI QC Compliance",
                $this->etsiQcsLimitValue->getData() => "ETSI Transaction Value Limit",
                $this->etsiQcsRetentionPeriod->getData() => "ETSI Retention Period",
                $this->etsiQcsQcSSCD->getData() => "ETSI Secure Signature Creation Device"
                );
    }
}
