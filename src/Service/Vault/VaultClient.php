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

namespace Signer\Service\Vault;

use Vault\Client;

class VaultClient extends Client
{
    /**
     * @var string
     */
    private $namespace;

    /**
     * @param string $path
     *
     * @return string
     */
    public function buildPath($path)
    {
        if (!$this->namespace) {
            $this->logger->warning('namespace is not set!');

            return parent::buildPath('/' . $path);
        }

        return parent::buildPath(sprintf('/%s%s', $this->namespace, $path));
    }

    /**
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * @param string $namespace
     */
    public function setNamespace(string $namespace)
    {
        $namespace = trim($namespace, '/') . '/';
        $this->namespace = $namespace;
    }
}
