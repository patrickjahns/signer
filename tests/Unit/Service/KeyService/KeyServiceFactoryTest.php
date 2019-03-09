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

namespace Signer\Tests\Unit\Service\KeyService;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Signer\Service\KeyService\FileKeyService;
use Signer\Service\KeyService\KeyServiceFactory;
use Signer\Service\Vault\VaultClientFactory;
use Signer\Service\KeyService\VaultSecretKeyService;

class KeyServiceFactoryTest extends TestCase
{
    /**
     * @var MockObject | FileKeyService
     */
    private $fileKeyService;

    /**
     * @var MockObject | VaultClientFactory
     */
    private $vaultClientFactory;

    /**
     * @var KeyServiceFactory
     */
    private $keyServiceFactory;

    public function setUp()
    {
        $this->fileKeyService = $this->createMock(FileKeyService::class);
        $this->vaultClientFactory = $this->createMock(VaultClientFactory::class);
        $this->keyServiceFactory = new KeyServiceFactory(
            $this->fileKeyService,
            $this->vaultClientFactory
        );
    }

    /**
     * @expectedException \RuntimeException
     */
    public function test_it_will_throw_an_error_on_unkown_service()
    {
        $this->keyServiceFactory->create('test');
    }

    public function test_it_will_return_a_filekey_service()
    {
        $fileKeyService = $this->keyServiceFactory->create('file');
        $this->assertInstanceOf(FileKeyService::class, $fileKeyService);
    }

    public function test_it_will_return_a_vaultsecret_service()
    {
        $fileKeyService = $this->keyServiceFactory->create('vault_secret');
        $this->assertInstanceOf(VaultSecretKeyService::class, $fileKeyService);
    }
}
