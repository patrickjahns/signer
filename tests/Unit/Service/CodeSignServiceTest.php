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
use PHPUnit\Framework\TestCase;
use Signer\Service\CodeSignService;
use Signer\Service\KeyService\KeyServiceInterface;

class CodeSignServiceTest extends TestCase
{
    private $file_archive = [
        'appinfo' => [
            'info.xml' => '<xml/>',
            'signature.json' => 'signature',
        ],
        'signature.json' => 'extrafile',
        'README.md' => 'test',
    ];

    private $file_hashes = [
        'appinfo/info.xml' => 'f8da247a400e1f6f96ff9d847496f6ee303dd2a0c2e17eb8248297afa99bb9570c477840145fb3b0fa7adf3af94b6da10d64a2b391e1d9ed12d4f7630ccea46c',
        'signature.json' => '0e805cee6d033684942277ad7be460ab9793341f784e6bc2af0e58dfc1c58d8f5faa6745a9d0b953261fb5524061df0b208457332f1de49406452c259e44f01b',
        'README.md' => 'ee26b0dd4af7e749aa1a8ee3c10ae9923f618980772e473f8819a5d4940e0db27ac185f8a0e1d5f84f88bc887fd67b143732c304cc5fa9ad8e6f57f50028a8ff',
    ];

    private $codeSignService;

    /**
     * @var vfsStreamDirectory
     */
    private $filesystem;

    public function setUp()
    {
        // define my virtual file system
        $directory = $this->file_archive;
        // setup and cache the virtual file system
        $this->filesystem = vfsStream::setup('root', 444, $directory);
        $keyService = $this->createMock(KeyServiceInterface::class);
        $this->codeSignService = new CodeSignService($keyService);
    }

    public function test_it_can_generate_hashes_for_path()
    {
        $hashes = $this->codeSignService->getHashesForPath($this->filesystem->url());
        $this->assertSame($this->file_hashes, $hashes);
    }
}
