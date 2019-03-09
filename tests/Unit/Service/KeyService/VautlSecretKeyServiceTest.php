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
use Signer\Service\Vault\VaultClient;
use Signer\Service\KeyService\VaultSecretKeyService;
use Signer\Tests\Helper\KeyHelper;
use Vault\Exceptions\AbstractResponseException;
use Vault\ResponseModels\Response;

class VautlSecretKeyServiceTest extends TestCase
{
    /**
     * @var MockObject | VaultClient
     */
    private $vaultClient;

    /**
     * @var MockObject | Response
     */
    private $vaultResponse;

    public function setUp()
    {
        $this->vaultClient = $this->createMock(VaultClient::class);
        $this->vaultResponse = $this->createMock(Response::class);
    }

    public function test_it_will_return_a_key_pair()
    {
        $this->vaultResponse
            ->method('getData')
            ->willReturn([
                'key' => KeyHelper::RSA_KEY,
                'cert' => KeyHelper::RSA_CERT,
            ]);
        $this->vaultClient
            ->method('read')
            ->willReturn($this->vaultResponse);

        $keyService = new VaultSecretKeyService($this->vaultClient);
        $keyPair = $keyService->getKeyPairForAppId('theme-example');
        $this->assertSame($keyPair->getAppId(), 'theme-example');
    }

    /**
     * @expectedException \Signer\Exception\InvalidKeyException
     */
    public function test_it_will_thrown_an_error_when_key_is_not_found_on_vault()
    {
        $this->vaultClient
            ->method('read')
            ->willThrowException(new AbstractResponseException('404 Not found', 404));

        $keyService = new VaultSecretKeyService($this->vaultClient);
        $keyService->getKeyPairForAppId('theme-example');
    }

    /**
     * @expectedException \RuntimeException
     */
    public function test_it_will_thrown_an_error_when_vault_answers_unexpected()
    {
        $this->vaultClient
            ->method('read')
            ->willThrowException(new AbstractResponseException('something weird happened'));

        $keyService = new VaultSecretKeyService($this->vaultClient);
        $keyService->getKeyPairForAppId('theme-example');
    }

    /**
     * @expectedException \Signer\Exception\InvalidKeyException
     */
    public function test_it_will_throw_an_error_when_vault_returns_no_key()
    {
        $this->vaultResponse
            ->method('getData')
            ->willReturn([]);
        $this->vaultClient
            ->method('read')
            ->willReturn($this->vaultResponse);

        $keyService = new VaultSecretKeyService($this->vaultClient);
        $keyService->getKeyPairForAppId('theme-example');
    }

    /**
     * @expectedException \Signer\Exception\InvalidKeyException
     */
    public function test_it_will_throw_an_error_when_vault_returns_no_cert()
    {
        $this->vaultResponse
            ->method('getData')
            ->willReturn([
                'key' => KeyHelper::RSA_KEY,
            ]);
        $this->vaultClient
            ->method('read')
            ->willReturn($this->vaultResponse);

        $keyService = new VaultSecretKeyService($this->vaultClient);
        $keyService->getKeyPairForAppId('theme-example');
    }

    /**
     * @expectedException \RuntimeException
     */
    public function test_it_will_throw_an_error_when_vault_returns_bad_key()
    {
        $this->vaultResponse
            ->method('getData')
            ->willReturn([
                'key' => 'key',
                'cert' => 'cert',
            ]);
        $this->vaultClient
            ->method('read')
            ->willReturn($this->vaultResponse);

        $keyService = new VaultSecretKeyService($this->vaultClient);
        $keyService->getKeyPairForAppId('theme-example');
    }

    /**
     * @expectedException \RuntimeException
     */
    public function test_it_will_throw_an_error_when_vault_returns_bad_cert()
    {
        $this->vaultResponse
            ->method('getData')
            ->willReturn([
                'key' => 'key',
                'cert' => 'cert',
            ]);
        $this->vaultClient
            ->method('read')
            ->willReturn($this->vaultResponse);

        $keyService = new VaultSecretKeyService($this->vaultClient);
        $keyService->getKeyPairForAppId('theme-example');
    }

    /**
     * @expectedException \RuntimeException
     */
    public function test_it_will_throw_an_error_when_vault_cert_doesnt_match_appid()
    {
        $this->vaultResponse
            ->method('getData')
            ->willReturn([
                'key' => KeyHelper::RSA_KEY,
                'cert' => KeyHelper::RSA_CERT,
            ]);
        $this->vaultClient
            ->method('read')
            ->willReturn($this->vaultResponse);

        $keyService = new VaultSecretKeyService($this->vaultClient);
        $keyService->getKeyPairForAppId('theme');
    }
}
