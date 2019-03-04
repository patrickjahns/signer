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

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use Signer\Model\OCAppKeySet;
use Signer\Service\KeyService\FileKeyService;
use Signer\Tests\Helper\KeyHelper;

class FileKeyServiceTest extends TestCase
{
    /**
     * @var vfsStreamDirectory
     */
    private $filesystem;

    public function setUp()
    {
        // define my virtual file system
        $directory = [
            'keys' => [
                'app.key' => KeyHelper::RSA_KEY,
                'app.crt' => KeyHelper::RSA_CERT,
            ],
            'no-keys' => [],
            'rsa' => [
                'app.key' => KeyHelper::RSA_KEY,
            ],
            'cert' => [
                'app.crt' => KeyHelper::RSA_CERT,
            ],
            'invalid-rsa' => [
                'app.key' => '',
                'app.crt' => KeyHelper::RSA_CERT,
            ],
            'invalid-cert' => [
                'app.key' => KeyHelper::RSA_KEY,
                'app.crt' => '',
            ],
        ];
        // setup and cache the virtual file system
        $this->filesystem = vfsStream::setup('root', 444, $directory);
    }

    /**
     * @expectedException \Signer\Exception\InvalidKeyException
     */
    public function test_it_will_thrown_an_error_when_no_key_found()
    {
        $keyService = new FileKeyService($this->filesystem->url() . '/no-keys');
        $keyService->getKeyPairForAppId('test');
    }

    public function test_it_will_return_a_key_pair()
    {
        $keyService = new FileKeyService($this->filesystem->url() . '/keys');
        $keyPair = $keyService->getKeyPairForAppId('app');
        $this->assertInstanceOf(OCAppKeySet::class, $keyPair);
    }

    /**
     * @expectedException \Signer\Exception\InvalidKeyException
     */
    public function test_it_will_throw_an_error_when_cert_is_missing()
    {
        $keyService = new FileKeyService($this->filesystem->url() . '/rsa');
        $keyPair = $keyService->getKeyPairForAppId('app');
    }

    /**
     * @expectedException \Signer\Exception\InvalidKeyException
     */
    public function test_it_will_throw_an_error_when_key_is_missing()
    {
        $keyService = new FileKeyService($this->filesystem->url() . '/cert');
        $keyPair = $keyService->getKeyPairForAppId('app');
    }

    /**
     * @expectedException \RuntimeException
     */
    public function test_it_will_throw_an_error_when_key_is_invalid()
    {
        $keyService = new FileKeyService($this->filesystem->url() . '/invalid-rsa');
        $keyPair = $keyService->getKeyPairForAppId('app');
    }

    /**
     * @expectedException \RuntimeException
     */
    public function test_it_will_throw_an_error_when_cert_is_invalid()
    {
        $keyService = new FileKeyService($this->filesystem->url() . '/invalid-cert');
        $keyPair = $keyService->getKeyPairForAppId('app');
    }
}
