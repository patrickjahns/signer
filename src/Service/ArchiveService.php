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
use Symfony\Component\Finder\Finder;

class ArchiveService
{
    public function extract(\SplFileInfo $file, string $targetFolder)
    {
        $archive = new \Archive_Tar($file->getPathname());
        $archive->extract($targetFolder);
        if (!file_exists($targetFolder)) {
            throw new InvalidAppArchive('archive could not be extracted');
        }
    }

    public function compress($path, $filename)
    {
        $finder = new Finder();
        $finder->in($path);
        $targetFile = dirname($path) . '/' . $filename;
        $archive = new \Archive_Tar($targetFile, 'gz');
        $archive->createModify([$path], '', dirname($path));

        return $targetFile;
    }

    public function getTempFolder()
    {
        $uniqueFileName = tempnam(sys_get_temp_dir(), 'Signer');

        return $uniqueFileName . '-folder';
    }
}
