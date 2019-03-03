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

namespace Signer\Model;

class OCApp
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $version;

    /**
     * @var string
     */
    private $appPath;

    /**
     * OCApp constructor.
     *
     * @param array $values
     * @param $appPath
     *
     * @throws \Exception
     */
    public function __construct(array $values, $appPath)
    {
        $this->setValueFromArray($values, 'id', true);
        $this->setValueFromArray($values, 'version', true);
        $this->appPath = $appPath;
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getAppPath(): string
    {
        return $this->appPath;
    }

    /**
     * @param $array
     * @param $key
     * @param bool $mandatory
     *
     * @throws \Exception
     */
    private function setValueFromArray($array, $key, $mandatory = false)
    {
        if (!array_key_exists($key, $array)) {
            if (false === $mandatory) {
                return;
            }
            throw new \Exception('missing mandatory value');
        }
        $this->$key = $array[$key];
    }
}
