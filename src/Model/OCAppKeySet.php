<?php

/**
 * @author Patrick Jahns <github@patrickjahns.de>
 * @copyright Copyright (c) 2019, Patrick Jahns.
 * @license GPL-2.0
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

namespace Signer\Model;

use phpseclib\Crypt\RSA;
use phpseclib\File\X509;

class OCAppKeySet
{
    /**
     * @var string
     */
    private $appId;

    /**
     * @var RSA
     */
    private $rsa;

    /**
     * @var X509
     */
    private $x509;

    /**
     * OCAppKeySet constructor.
     *
     * @param RSA  $rsa
     * @param X509 $x509
     */
    public function __construct(string $appId, RSA $rsa, X509 $x509)
    {
        $this->appId = $appId;
        $this->rsa = $rsa;
        $this->x509 = $x509;
    }

    /**
     * @return string
     */
    public function getAppId(): string
    {
        return $this->appId;
    }

    /**
     * @return RSA
     */
    public function getRSA(): RSA
    {
        return $this->rsa;
    }

    /**
     * @return X509
     */
    public function getX509(): X509
    {
        return $this->x509;
    }
}
