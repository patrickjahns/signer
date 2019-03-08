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

class WorkspaceService
{
    /**
     * @var string
     */
    private $workspace;

    /**
     * WorkspaceService constructor.
     *
     * @param $base
     */
    public function __construct($base)
    {
        $this->workspace = $base . '/' . \uniqid('app', false);
    }

    /**
     * @return string
     */
    public function getWorkspace(): string
    {
        if (false === file_exists($this->workspace)) {
            if (!mkdir($concurrentDirectory = $this->workspace, 0700, true) && !is_dir($concurrentDirectory)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
            }
        }

        return $this->workspace;
    }

    public function cleanup(): void
    {
        rmdir($this->workspace);
    }
}
