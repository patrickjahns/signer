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

namespace Signer\Controller;

use Signer\Security\JWTSecurity;
use Signer\Service\ArchiveService;
use Signer\Service\CodeSignService;
use Signer\Service\OCAppService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class SignController
{
    /**
     * @var CodeSignService
     */
    private $codeSignService;

    /**
     * @var JWTSecurity
     */
    private $security;

    /**
     * SignController constructor.
     *
     * @param CodeSignService $codeSignService
     */
    public function __construct(CodeSignService $codeSignService, JWTSecurity $security)
    {
        $this->codeSignService = $codeSignService;
        $this->security = $security;
    }

    /**
     * @param Request $request
     *
     * @return BinaryFileResponse|Response
     *
     * @throws \Exception
     */
    public function sign(Request $request)
    {
        if (!$this->security->isAuthenticated($request)) {
            return new Response(null, Response::HTTP_UNAUTHORIZED);
        }

        /** @var FileBag $files */
        $files = $request->files->all();
        if (count($files) > 1 || 0 === count($files)) {
            return new Response(null, Response::HTTP_BAD_REQUEST);
        }
        /** @var UploadedFile $file */
        $file = array_shift($files);

        if (!$file->isValid()) {
            return new Response(null, Response::HTTP_BAD_REQUEST);
        }
        // Extract
        $archiveService = new ArchiveService();
        $path = $archiveService->getTempFolder();
        $archiveService->extract($file, $path);

        // Load information on the app
        $infoFile = OCAppService::findAppInfoXML($path);
        $xmlString = OCAppService::getAppXMLAsString($infoFile);
        $appInfo = OCAppService::createFromXMLString($xmlString);
        $appPath = $path . '/' . $appInfo->getId();

        // check if the given token is authorized to perform the action
        if (!$this->security->isAuthorizedToPerform($request, 'sign:' . $appInfo->getId())) {
            return new Response(null, Response::HTTP_FORBIDDEN);
        }

        // sign app
        $this->codeSignService->signApp($appPath, $appInfo->getId());

        // compress signed app
        $archiveName = $appInfo->getId() . '-' . $appInfo->getVersion() . '.tar.gz';
        $newArchive = $archiveService->compress($appPath, $archiveName);

        $response = new BinaryFileResponse($newArchive);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $archiveName, $archiveName);

        return $response;
    }
}
