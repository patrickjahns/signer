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

namespace Signer\Service\KeyService;

use phpseclib\Crypt\RSA;
use phpseclib\File\X509;
use Signer\Exception\InvalidKeyException;
use Signer\Model\OCAppKeySet;

class FileKeyService implements KeyServiceInterface
{
    /**
     * @var string
     */
    private $searchPath;

    /**
     * FileKeyService constructor.
     *
     * @param string $searchPath
     */
    public function __construct(string $searchPath)
    {
        $this->searchPath = $searchPath;
    }

    /**
     * @param string $appId
     *
     * @return OCAppKeySet
     */
    public function getKeyPairForAppId(string $appId): OCAppKeySet
    {
        $rsa = $this->getRSA($appId);
        $x509 = $this->getX509($appId, $rsa);

        return new OCAppKeySet($appId, $rsa, $x509);
    }

    /**
     * @param string $appId
     *
     * @return RSA
     *
     * @throws InvalidKeyException
     */
    private function getRSA(string $appId): RSA
    {
        $filepath = $this->searchPath . '/' . $appId . '.key';
        if (!file_exists($filepath)) {
            throw new InvalidKeyException('no key found');
        }
        $rsa = new RSA();
        $rsa->loadKey(file_get_contents($filepath));

        return $rsa;
    }

    /**
     * @param string $appId
     *
     * @return X509
     *
     * @throws InvalidKeyException
     */
    private function getX509(string $appId, RSA $rsa)
    {
        $filepath = $this->searchPath . '/' . $appId . '.crt';
        if (!file_exists($filepath)) {
            throw new InvalidKeyException('no key found');
        }
        $x509 = new X509();
        $x509->loadX509(file_get_contents($filepath));
        $x509->setPrivateKey($rsa);

        return $x509;
    }
}
