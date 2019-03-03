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
use Signer\Service\AppInfoReader;

class AppInfoReaderTest extends TestCase
{
    /**
     * @var vfsStreamDirectory
     */
    private $filesystem;

    public function setUp()
    {
        // define my virtual file system
        $directory = [
            'appinfo' => [
                'info.xml' => '<?xml version="1.0"?><info><id>theme-example</id><version>1.0.0</version></info>',
                'missing.xml' => '',
                'invalid.xml' => '<?xml version="1.0"?><info><id>theme-example/id></info>',
            ],
        ];
        // setup and cache the virtual file system
        $this->filesystem = vfsStream::setup('root', 444, $directory);
    }

    /**
     * @expectedException \Exception
     */
    public function test_it_throws_an_exception_when_file_does_not_exist()
    {
        new AppInfoReader(
            $this->filesystem->url() . 'not.xml'
        );
    }

    /**
     * @expectedException \Signer\Exception\InvalidAppArchive
     */
    public function test_it_throws_an_exception_when_xml_file_is_empty()
    {
        $appInfoReader = new AppInfoReader(
            $this->filesystem->url() . '/appinfo/missing.xml'
        );
        $appInfoReader->getXML();
    }

    /**
     * @expectedException \Signer\Exception\InvalidAppArchive
     */
    public function test_it_throws_an_exception_when_no_valid_xml_found()
    {
        $appInfoReader = new AppInfoReader(
            $this->filesystem->url() . '/appinfo/invalid.xml'
        );
        $appInfoReader->getXML();
    }

    public function test_it_will_return_xml()
    {
        $appInfoReader = new AppInfoReader(
            $this->filesystem->url() . '/appinfo/info.xml'
        );
        $xml = $appInfoReader->getXML();
        $this->assertInstanceOf(\SimpleXMLElement::class, $xml);
    }
}
