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

use Signer\Service\Vault\VaultClientFactory;

class KeyServiceFactory
{
    /**
     * @var FileKeyService
     */
    private $fileKeyService;

    /**
     * @var VaultClientFactory
     */
    private $vaultClientFactory;

    /**
     * KeyServiceFactory constructor.
     *
     * @param VaultClientFactory $vaultClientFactory
     */
    public function __construct(
        VaultClientFactory $vaultClientFactory
    ) {
        $this->vaultClientFactory = $vaultClientFactory;
    }

    /**
     * @param array $definition
     *
     * @return KeyServiceInterface
     */
    public function create(array $definition): KeyServiceInterface
    {
        $type = (string) array_key_first($definition);
        switch ($type) {
            case 'file':
                return $this->getFileKeyService($definition['file']);
            case 'vault_secret':
                return $this->createVaultSecretKeyService($definition['vault_secret']);
            default:
                throw new \RuntimeException(sprintf('Keyservice %s unknown', $type));
        }
    }

    /**
     * @param array $definition
     *
     * @return FileKeyService
     */
    private function getFileKeyService(array $definition): FileKeyService
    {
        return new FileKeyService($definition['lookup_directory']);
    }

    /**
     * @param array $definition
     *
     * @return VaultSecretKeyService
     */
    private function createVaultSecretKeyService(array $definition): VaultSecretKeyService
    {
        return new VaultSecretKeyService($this->vaultClientFactory->get($definition));
    }
}
