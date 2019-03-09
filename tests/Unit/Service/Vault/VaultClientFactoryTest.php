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

namespace Signer\Tests\Unit\Service\Vault;

use PHPUnit\Framework\TestCase;
use Signer\Service\Vault\VaultClientFactory;
use Vault\AuthenticationStrategies\AppRoleAuthenticationStrategy;
use Vault\AuthenticationStrategies\TokenAuthenticationStrategy;
use Vault\AuthenticationStrategies\UserPassAuthenticationStrategy;

class VaultClientFactoryTest extends TestCase
{
    /**
     * @var VaultClientFactory
     */
    private $factory;

    public function setUp()
    {
        $this->factory = new VaultClientFactory();
    }

    /**
     * @expectedException \RuntimeException
     */
    public function test_it_will_throw_a_error_if_base_url_is_not_provided()
    {
        $client = $this->factory->get([]);
    }

    public function test_it_will_have_a_namespace_when_provided()
    {
        $client = $this->factory->get([
            'url' => 'test',
            'namespace' => 'test/',
            'auth' => [
                'token' => 'test',
            ],
        ]);
        $this->assertSame('test/', $client->getNamespace());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function test_it_will_throw_an_exception_when_no_authentication_strategie_is_provided()
    {
        $client = $this->factory->get([
            'url' => 'test',
        ]);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function test_it_will_throw_an_error_when_auth_is_unknown()
    {
        $client = $this->factory->get([
            'url' => 'test',
            'auth' => 'test',
        ]);
    }

    public function test_it_will_have_token_auth_strategie_configured()
    {
        $client = $this->factory->get([
            'url' => 'test',
            'auth' => [
                'token' => 'test',
            ],
        ]);
        $this->assertInstanceOf(TokenAuthenticationStrategy::class, $client->getAuthenticationStrategy());
    }

    public function test_it_will_have_password_auth_strategie_configured()
    {
        $client = $this->factory->get([
            'url' => 'test',
            'auth' => [
                'credentials' => [
                    'username' => 'test',
                    'password' => 'test',
                ],
            ],
        ]);
        $this->assertInstanceOf(UserPassAuthenticationStrategy::class, $client->getAuthenticationStrategy());
    }

    public function test_it_will_have_app_role_auth_strategie_configured()
    {
        $client = $this->factory->get([
            'url' => 'test',
            'auth' => [
                'role' => [
                    'id' => 'test',
                    'secret' => 'test',
                ],
            ],
        ]);
        $this->assertInstanceOf(AppRoleAuthenticationStrategy::class, $client->getAuthenticationStrategy());
    }
}
