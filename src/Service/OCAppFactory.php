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
use Signer\Model\OCApp;

class OCAppFactory
{
    const APP_INFO_PATH = 'appinfo/info.xml';

    /**
     * @param $path
     *
     * @return OCApp
     *
     * @throws \Exception
     */
    public static function fromPath($path)
    {
        $xmlPath = self::getAppPath($path) . '/' . self::APP_INFO_PATH;
        $appInfoReader = new AppInfoReader($xmlPath);
        $xmlArray = json_decode(json_encode($appInfoReader->getXML()), true);

        return new OCApp($xmlArray, self::getAppPath($path));
    }

    /**
     * @param $path
     *
     * @return string
     *
     * @throws \Exception
     */
    public static function getAppPath($path)
    {
        $dirs = array_diff(scandir($path, true), ['..', '.']);
        if (1 !== count($dirs)) {
            throw new InvalidAppArchive('could not determine app directory');
        }

        return $path . '/' . $dirs[0];
    }
}
