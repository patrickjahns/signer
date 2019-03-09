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

namespace Signer\Tests\Unit\Vault\KeyService;

use PHPUnit\Framework\TestCase;
use Signer\Service\Vault\VaultClient;
use Vault\Transports\Transport;

class VaultClientTest extends TestCase
{
    /**
     * @var VaultClient
     */
    private $client;

    public function setUp()
    {
        $transport = $this->createMock(Transport::class);
        $this->client = new VaultClient($transport);
    }

    /**
     * @dataProvider namespaceDataProvider
     */
    public function testNamespaceIsNormalized($input, $expected)
    {
        $this->client->setNamespace($input);
        $this->assertSame($expected, $this->client->getNamespace());
    }

    public function namespaceDataProvider()
    {
        return [
            'normal' => ['test/', 'test/'],
            'remove leading' => ['/test/', 'test/'],
            'remove several leading' => ['/////test/', 'test/'],
            'remove several trailing' => ['test////', 'test/'],
            'lave subpath' => ['/t/e/s/t////', 't/e/s/t/'],
        ];
    }

    public function test_build_path_will_contain_version_when_namespace_is_empty()
    {
        $path = $this->client->buildPath('test');
        $this->assertSame('/v1/test', $path);
    }

    public function test_build_path_will_contain_version_and_namspace()
    {
        $this->client->setNamespace('signer');
        $path = $this->client->buildPath('test');
        $this->assertSame('/v1/signer/test', $path);
    }
}
