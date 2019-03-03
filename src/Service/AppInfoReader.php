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

namespace Signer\Service;

use Signer\Exception\InvalidAppArchive;

/**
 * Class AppInfoReader.
 */
class AppInfoReader
{
    /**
     * @var string
     */
    private $path;

    /**
     * AppInfoReader constructor.
     *
     * @param string $path
     *
     * @throws \Exception
     */
    public function __construct(string $path)
    {
        $this->path = $path;
        $this->fileExists();
    }

    /**
     * @return \SimpleXMLElement
     */
    public function getXML()
    {
        try {
            $xmlstring = file_get_contents($this->path);
            $xml = simplexml_load_string($xmlstring, 'SimpleXMLElement', LIBXML_NOCDATA);
        } catch (\Exception $e) {
            $xml = false;
        }
        if (false === $xml) {
            throw new InvalidAppArchive('invalid info.xml');
        }

        return $xml;
    }

    /**
     * @throws \Exception
     */
    private function fileExists()
    {
        if (!\file_exists($this->path)) {
            throw new InvalidAppArchive('info.xml not found');
        }
    }
}
