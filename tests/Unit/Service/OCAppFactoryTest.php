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
use PHPUnit\Framework\TestCase;
use Signer\Model\OCApp;
use Signer\Service\OCAppFactory;

class OCAppFactoryTest extends TestCase
{
    public function test_get_app_path_will_return_a_path()
    {
        $directory = [
            '.' => [],
            '..' => [],
            'example' => [],
        ];
        $filesystem = $this->filesystem = vfsStream::setup('root', 444, $directory);
        $path = OCAppFactory::getAppPath($filesystem->url());
        $this->assertSame('vfs://root/example', $path);
    }

    /**
     * @expectedException  \Signer\Exception\InvalidAppArchive
     */
    public function test_raising_exception_if_not_a_proper_app_path()
    {
        $directory = [
            '.' => [],
            '..' => [],
            'example' => [],
            'example2' => [],
        ];
        $filesystem = $this->filesystem = vfsStream::setup('root', 444, $directory);
        $path = OCAppFactory::getAppPath($filesystem->url());
    }

    public function test_it_will_return_a_oc_app_object()
    {
        $directory = [
            'example' => [
                'appinfo' => [
                    'info.xml' => '<?xml version="1.0"?><info><id>theme-example</id><version>1.0.0</version></info>',
                ],
            ],
        ];
        $filesystem = $this->filesystem = vfsStream::setup('root', 444, $directory);
        $app = OCAppFactory::fromPath($filesystem->url());
        $this->assertInstanceOf(OCApp::class, $app);
        $this->assertSame('theme-example', $app->getId());
        $this->assertSame('1.0.0', $app->getVersion());
        $this->assertSame('vfs://root/example', $app->getAppPath());
    }

    /**
     * @expectedException  \Signer\Exception\InvalidAppArchive
     */
    public function test_raised_exception_for_invalid_app()
    {
        $directory = [
            'example' => [
                'appinfo' => [
                    'info.xml' => '<?xml version="1.0"?><info><id></id>theme-example</id><version>1.0.0</version></info>',
                ],
            ],
        ];
        $filesystem = $this->filesystem = vfsStream::setup('root', 444, $directory);
        $app = OCAppFactory::fromPath($filesystem->url());
    }
}
