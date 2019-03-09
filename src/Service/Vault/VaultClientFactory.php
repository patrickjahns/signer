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

namespace Signer\Service\Vault;

use Vault\AuthenticationStrategies\AppRoleAuthenticationStrategy;
use Vault\AuthenticationStrategies\AuthenticationStrategy;
use Vault\AuthenticationStrategies\TokenAuthenticationStrategy;
use Vault\AuthenticationStrategies\UserPassAuthenticationStrategy;
use Vault\Transports\Transport;
use VaultTransports\Guzzle6Transport;

class VaultClientFactory
{
    /**
     * @param array $definition
     *
     * @return VaultClient
     */
    public function get(array $definition): VaultClient
    {
        $client = new VaultClient($this->getTransport($definition));
        $client->setNamespace($this->getNamespace($definition));
        $client->setAuthenticationStrategy($this->getAuthenticationStrategy($definition));

        return $client;
    }

    /**
     * @param array $definition
     *
     * @return Transport
     */
    private function getTransport(array $definition): Transport
    {
        if (!array_key_exists('url', $definition)) {
            throw new \RuntimeException('missing url for vault client');
        }

        return new Guzzle6Transport(['base_uri' => $definition['url']]);
    }

    /**
     * @param array $definition
     *
     * @return string
     */
    private function getNamespace(array $definition): string
    {
        if (array_key_exists('namespace', $definition)) {
            return $definition['namespace'];
        }

        return '';
    }

    /**
     * @param array $definition
     *
     * @return AuthenticationStrategy
     */
    private function getAuthenticationStrategy(array $definition): AuthenticationStrategy
    {
        if (!array_key_exists('auth', $definition) || !is_array($definition['auth'])) {
            throw new \RuntimeException('missing auth for vault client');
        }
        $authType = (string) array_key_first($definition['auth']);
        switch ($authType) {
            case 'credentials':
                $credentialArray = $definition['auth']['credentials'];
                if (!array_key_exists('username', $credentialArray) || !array_key_exists('password', $credentialArray)) {
                    throw new \RuntimeException('missing username or password for credential authentication');
                }

                return new UserPassAuthenticationStrategy($credentialArray['username'], $credentialArray['password']);
            case 'role':
                $roleArray = $definition['auth']['role'];
                if (!array_key_exists('id', $roleArray) || !array_key_exists('secret', $roleArray)) {
                    throw new \ RuntimeException('missing id or secret for role authentication');
                }

                return new AppRoleAuthenticationStrategy($roleArray['id'], $roleArray['secret']);
            case 'token':
                return new TokenAuthenticationStrategy((string) $definition['auth']['token']);
            default:
                throw new \RuntimeException('unknown authentication strategy');
        }
    }
}
