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
use Signer\Service\WorkspaceService;

class WorkspaceServiceTest extends TestCase
{
    /**
     * @var
     */
    private $workspaceService;

    private $vfs;

    public function setUp()
    {
        $this->vfs = vfsStream::setup('tmp');
        $this->workspaceService = new WorkspaceService(vfsStream::url('tmp'));
    }

    public function test_it_will_return_a_path()
    {
        $workspace = $this->workspaceService->getWorkspace();
        $this->assertIsString($workspace);
        $this->assertTrue($this->vfs->hasChild(basename($workspace)));
    }

    public function test_it_will_cleanup_the_workspace()
    {
        $workspace = $this->workspaceService->getWorkspace();
        $this->workspaceService->cleanup();
        $this->assertFalse($this->vfs->hasChild(basename($workspace)));
    }
}
