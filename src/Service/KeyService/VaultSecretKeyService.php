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
use Signer\Service\Vault\VaultClient;
use Vault\Exceptions\AbstractResponseException;

class VaultSecretKeyService implements KeyServiceInterface
{
    /**
     * @var VaultClient
     */
    private $vaultClient;

    /**
     * VaultSecretKeyService constructor.
     *
     * @param VaultClient $vaultClient
     */
    public function __construct(VaultClient $vaultClient)
    {
        $this->vaultClient = $vaultClient;
    }

    /**
     * @param string $appId
     *
     * @return OCAppKeySet
     */
    public function getKeyPairForAppId(string $appId): OCAppKeySet
    {
        $data = $this->getData($appId);
        $rsa = $this->getRSA($data);
        $x509 = $this->getX509($data, $appId, $rsa);

        return new OCAppKeySet($appId, $rsa, $x509);
    }

    /**
     * @param string $appId
     *
     * @return array
     */
    private function getData(string $appId): array
    {
        try {
            $this->vaultClient->authenticate();
            $response = $this->vaultClient->read($appId);

            return $response->getData();
        } catch (AbstractResponseException $e) {
            if (404 === $e->getCode()) {
                throw new InvalidKeyException('no key found');
            }
            throw new \RuntimeException($e->getMessage());
        }
    }

    /**
     * @param array $data
     *
     * @return RSA
     */
    private function getRSA(array $data): RSA
    {
        if (!array_key_exists('key', $data)) {
            throw new InvalidKeyException('no key found');
        }
        $rsa = new RSA();
        if (!$rsa->loadKey($data['key'])) {
            throw new \RuntimeException('invalid key');
        }

        return $rsa;
    }

    /**
     * @param array  $data
     * @param string $appId
     * @param RSA    $rsa
     *
     * @return X509
     */
    private function getX509(array $data, string $appId, RSA $rsa): X509
    {
        if (!array_key_exists('cert', $data)) {
            throw new InvalidKeyException('no key found');
        }
        try {
            $x509 = new X509();
            $x509->loadX509($data['cert']);
            $x509->setPrivateKey($rsa);
            /** @var array $values */
            $values = $x509->getDN(X509::DN_OPENSSL);
            if (!is_array($values) || !array_key_exists('CN', $values) || $values['CN'] !== $appId) {
                throw new \RuntimeException('invalid key');
            }
        } catch (\Exception $e) {
            throw new \RuntimeException('invalid key');
        }

        return $x509;
    }
}
