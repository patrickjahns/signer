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

namespace Signer\Tests\Unit\Service;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use phpseclib\Crypt\RSA;
use phpseclib\File\X509;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Signer\Model\OCAppKeySet;
use Signer\Service\CodeSignService;
use Signer\Service\KeyService\KeyServiceInterface;
use Signer\Tests\Helper\KeyHelper;

class CodeSignServiceTest extends TestCase
{
    const EXPECTED_SIGNATUE = 'TIOgm2xUGqmom2DZWD1ziO8dmcKZ0Ci6VXCT6eLJP0jwgqwUYi2n3kuK/A3hg09QS3bwPl2iBpc0CKJM/okz4VQYIfHwT8rNlx16SaB2yXMMcu42uIPZVJn46V3E4R3F/n0NPKpEevfcETEsraACsMxTFBnSAkpGuiup8o7GphZALpSs0qmNu8/KjB3ppNJgsvtO84NOkcCM3uCQh8wwYXSaFJSDzDcctWbmn5w74rit+5PuXjhIq2KafPyPToNmzQBllM38W2jYXbFez7sfLp5Hv2qt986aYSbG6hHeztjG2FHGGfBoTmRPtvWrOEcdrf/NSE10VH0Qt+5Sz+gqHA==';

    const FILE_ARCHIVE = [
        'appinfo' => [
            'info.xml' => '<xml/>',
            'signature.json' => 'signature',
        ],
        'signature.json' => 'extrafile',
        'README.md' => 'test',
    ];

    const FILE_HASHES = [
        'appinfo/info.xml' => 'f8da247a400e1f6f96ff9d847496f6ee303dd2a0c2e17eb8248297afa99bb9570c477840145fb3b0fa7adf3af94b6da10d64a2b391e1d9ed12d4f7630ccea46c',
        'signature.json' => '0e805cee6d033684942277ad7be460ab9793341f784e6bc2af0e58dfc1c58d8f5faa6745a9d0b953261fb5524061df0b208457332f1de49406452c259e44f01b',
        'README.md' => 'ee26b0dd4af7e749aa1a8ee3c10ae9923f618980772e473f8819a5d4940e0db27ac185f8a0e1d5f84f88bc887fd67b143732c304cc5fa9ad8e6f57f50028a8ff',
    ];

    /**
     * @var KeyServiceInterface | MockObject
     */
    private $keyService;

    /**
     * @var vfsStreamDirectory
     */
    private $filesystem;

    public function setUp()
    {
        // define my virtual file system
        $directory = self::FILE_ARCHIVE;
        // setup and cache the virtual file system
        $this->filesystem = vfsStream::setup('root', 444, $directory);
        $this->keyService = $this->createMock(KeyServiceInterface::class);
    }

    public function test_it_can_generate_hashes_for_path()
    {
        $codeSignService = new CodeSignService($this->keyService);
        $hashes = $codeSignService->getHashesForPath($this->filesystem->url());
        $this->assertSame(self::FILE_HASHES, $hashes);
    }

    public function test_it_can_sign_hashes()
    {
        $codeSignService = new CodeSignService($this->keyService);
        $signature = $codeSignService->createSignature(self::FILE_HASHES, $this->getKeySet());
        $this->assertIsArray($signature);
        $this->assertArrayHasKey('hashes', $signature);
        $this->assertArrayHasKey('signature', $signature);
        $this->assertSame($signature['signature'], self::EXPECTED_SIGNATUE);
    }

    public function test_it_can_sign_an_app()
    {
        $directory = [
            'appinfo' => [
                'info.xml' => '<xml/>',
            ],
            'signature.json' => 'extrafile',
            'README.md' => 'test',
        ];
        $filesystem = vfsStream::setup('root', 444, $directory);
        $this
            ->keyService
            ->method('getKeyPairForAppId')
            ->with('test')
            ->willReturn($this->getKeySet());
        $codeSignService = new CodeSignService($this->keyService);
        $codeSignService->signApp($filesystem->url(), 'test');
        $this->assertTrue($filesystem->hasChild('appinfo/signature.json'));
    }

    private function getKeySet()
    {
        $rsa = new RSA();
        $rsa->loadKey(KeyHelper::RSA_KEY);
        $x509 = new X509();
        $x509->loadX509(KeyHelper::RSA_CERT);
        $x509->setPrivateKey($rsa);

        return new OCAppKeySet('test', $rsa, $x509);
    }
}
