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

class CodeSignServiceTest extends TestCase
{
    const RSA_KEY = <<<EOD
-----BEGIN RSA PRIVATE KEY-----
MIIEowIBAAKCAQEAyr/+4RzUa+6swBXDxb4AehGeIXXfxRHUsB/EifOWqbhJQ2lQ
Q/KHgebBqWxM8Ll1+NqDsznGkqfomOVX8ylZZPbn2B6aJBBpJzKC8qsb0M3wdvvI
rS3FWrk7+JOe0o8/vj5F80sCubzGbMG5AtkK+aoTHlaB+7MxIKlTL3PVYkkN++un
tLGfW5duZX+b8jwkvCv8VpfWG3DPfvtyUMdLjLu1kSIrMNYX+E9PpT+iOVIPmJYo
3BakJPp0d8gvYhf/QnqVcKtOEtpeLhhfQ7VSCyQBFpjwwAytbhaK7Dsarivwb522
R6pdh+5YaW6EzwMdrq/KV2itbBcXX4cChmDK3QIDAQABAoIBACg28tAlzsBlw+AJ
sR4ctK2BpxLN9Yd6JOyWMH6IUT7yrZ1wWxpPFa+fXJRFRfGNZ6fnd63p7MgUA9+n
xc0WS4PqMUw9racvlhvPOgf2BnthDawb/s7SwE1hZlLEvQDYpvUOFwPNwgmjNtxF
hHPbJwEkScx/riKFhV4MF9LWr+Fhb7FQrjZsfrQQzF7TbKx61ndUO/sFE6fHx9Fy
2OItA0Ajs38jyPEgcgAauTEINeL3UGdilnhw9rL1BUPNATsZvzKwYeXB8Lzfdih+
Xm6u+x2zlhcap8W+Xfs+PkICrjZGnu5pH6uiMLYwr/mWP8DZCz5XNB7dhZMr5eDM
0+ZI9AECgYEA7Of9hS8T5MpX3G/GNAZN+ZKXgnZx3veGqCZgUxnDoC5+wqfmh+hb
SXNLAw6lazScGCmfcorpI75JkPTUJTR8qQCtoAQE0U+LZXxnufeWNSK+YUzuP8EV
ve2Dj+n27McEULCjvyrlFdyJDDJ8QhMoWXXv8FYu2LUJjtEgAjLUYd0CgYEA2xdF
VCX4D9ShdaXFd+o0+A29I0HUGsTsOr1x5W7KDnvv9ARdkZSCizwTiU8lMiF/zR4K
vl8Np2V+w3kj9EbLrezjnV7eK/Z9Ihdl8s+rCp5VmPXJMxXirRAeNLn4tUyAotFP
b1QIw45M0KZyxp8V++iCCxSXi478f05dI/1Z/QECgYEA2ifvv3tQuHjUW7vaKwI7
P72MI6lqxsXtjF56iUvKZ3EpbZsroK+JkKPIybHtBkHWFDIQEGx2sGGEYSXzTad9
vCtRt267+PtlBDmBUzD5c0jhQ7ySEMd/e/yCED6FEhlaket3fozDlFQXJ9I9tqLp
nygJoanbTde4S3msHhoslUkCgYBxi5ztoIPwSGWpYFF39VSgXhZw6FPxz68SPk6B
9qoXWZohYAXSRiJl4KvLVM5VLdFbT7+HrCGaaNqKmgTNO8ehiwzn6VvBcwylF3VJ
ouDlLuvzyyYMKMKCqMDO2LcR1uUv/MRrUST9nIko9aq0T4yIMpb7ASANPvyTSdyx
o0L5AQKBgA4GtOOZS2Ljc84HCv3ys0ZmhIO7lfvGo9pf9LRUvJ7jvBXhCe8HpgDZ
J1BQLi7CePolXdivaaOX85S6Y3oU+1tiRap3cE7ik3InF4qnJ0wwkyjOy7Id5zCb
6dkSFUoPjZhjJqLJ/cwK0voaETm7q9cfvJoB5YCM5hLQkjA3YRGO
-----END RSA PRIVATE KEY-----
EOD;

    const RSA_CERT = <<<EOD
-----BEGIN CERTIFICATE-----
MIIEHjCCAgYCCQCLqjieoC6p3jANBgkqhkiG9w0BAQsFADBFMQswCQYDVQQGEwJB
VTETMBEGA1UECBMKU29tZS1TdGF0ZTEhMB8GA1UEChMYSW50ZXJuZXQgV2lkZ2l0
cyBQdHkgTHRkMB4XDTE4MDYzMDE0MTUxNFoXDTE5MTExMjE0MTUxNFowXTELMAkG
A1UEBhMCQVUxEzARBgNVBAgTClNvbWUtU3RhdGUxITAfBgNVBAoTGEludGVybmV0
IFdpZGdpdHMgUHR5IEx0ZDEWMBQGA1UEAxMNdGhlbWUtZXhhbXBsZTCCASIwDQYJ
KoZIhvcNAQEBBQADggEPADCCAQoCggEBAMq//uEc1GvurMAVw8W+AHoRniF138UR
1LAfxInzlqm4SUNpUEPyh4HmwalsTPC5dfjag7M5xpKn6JjlV/MpWWT259gemiQQ
aScygvKrG9DN8Hb7yK0txVq5O/iTntKPP74+RfNLArm8xmzBuQLZCvmqEx5Wgfuz
MSCpUy9z1WJJDfvrp7Sxn1uXbmV/m/I8JLwr/FaX1htwz377clDHS4y7tZEiKzDW
F/hPT6U/ojlSD5iWKNwWpCT6dHfIL2IX/0J6lXCrThLaXi4YX0O1UgskARaY8MAM
rW4Wiuw7Gq4r8G+dtkeqXYfuWGluhM8DHa6vyldorWwXF1+HAoZgyt0CAwEAATAN
BgkqhkiG9w0BAQsFAAOCAgEAURF6hQ5KM+jacPwpf/yGVPAobhQfW5XELRF2LzZT
bNcbu6eEvvBzNU9q/S7ZmhRXHs/T/g6ZU7Bz8dty0ooOjqDMvkWzdbxoqr35WkdI
o7AAZqP8H0KzqDgGqbj+xfRSc9tsMHuRvpVTqGuCf/1i5IUHgWiNXdzeTHOH7Mpl
ynF1TeyuZsUa2UkzjdnwQN+jzKM2XVgME45TCUChziLPkuQ1z9VYlK1meZLCpH30
HhetJnZRau284HXInJID/Mxy5iCj/LYPJfgOk6gcBuSbSnB5a0OG9R2pmcVC/2NK
mhtWGOSCmnTZB3jFUBQoN4n36OBPnI7Kni5L6vAUKW2z0XiK7vEM+Vx1jxwhaHZE
9OyB0j02XoNyRv64/61MpXWJrpsGchkeFnsjrSY4rP5ej0I3n21yleuooTw1PQ6d
AcskVYCKZGikS+QI46wCuwt0qtGs0ZjmSKrTK/o3YnEqVHo24nLQiYLWrT98afsx
y3hnKsRoz9P3uKjAFgiR6U4s/Rhobuw69rYZp20TkKNocWIgyL8AH/ITfmKs6bne
uf31vQ2aHQjT+AyWCyV1cTOuu9vE1IX1r+z8yCHKLwPq28Yq2zdMIYNM0vO1NqPO
qCXdYrz5kC3EEnA5W/vyjAY9LxtVFwbGfq7M0UvpGJ62CDJelz/+qfymsQqMGiWu
cg0=
-----END CERTIFICATE-----
EOD;

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
        $rsa->loadKey(self::RSA_KEY);
        $x509 = new X509();
        $x509->loadX509(self::RSA_CERT);
        $x509->setPrivateKey($rsa);

        return new OCAppKeySet('test', $rsa, $x509);
    }
}
