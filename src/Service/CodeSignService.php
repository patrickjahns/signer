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

use phpseclib\Crypt\RSA;
use Signer\Model\OCAppKeySet;
use Signer\Service\KeyService\KeyServiceInterface;
use Symfony\Component\Finder\Finder;

class CodeSignService
{
    public const SIGNATURE_FILE = '/appinfo/signature.json';

    /**
     * @var KeyServiceInterface
     */
    private $keyService;

    /**
     * CodeSignService constructor.
     *
     * @param KeyServiceInterface $keyService
     */
    public function __construct(KeyServiceInterface $keyService)
    {
        $this->keyService = $keyService;
    }

    /**
     * @param string $path
     * @param string $appId
     *
     * @throws \Exception
     */
    public function signApp(string $path, string $appId)
    {
        $keySet = $this->keyService->getKeyPairForAppId($appId);
        $hashes = $this->getHashesForPath($path);
        $signature = $this->createSignature($hashes, $keySet);
        $this->writeSignature($signature, $path . self::SIGNATURE_FILE);
    }

    /**
     * @param string $appPath
     *
     * @return array
     */
    public function getHashesForPath(string $appPath): array
    {
        $finder = new Finder();
        $finder->in($appPath)->files()->notName('signature.json');
        $hashes = [];
        foreach ($finder as $file) {
            $hashes[$file->getRelativePathname()] = hash_file('sha512', $file->getPathname());
        }

        return $hashes;
    }

    /**
     * @param array       $hashes
     * @param OCAppKeySet $keySet
     *
     * @return array
     */
    public function createSignature(array $hashes, OCAppKeySet $keySet)
    {
        \ksort($hashes);
        $certificate = $keySet->getX509();
        $privateKey = $keySet->getRSA();
        $privateKey->setSignatureMode(RSA::SIGNATURE_PSS);
        $privateKey->setMGFHash('sha512');
        $privateKey->setSaltLength(0);
        $signature = $privateKey->sign(json_encode($hashes));

        return [
            'hashes' => $hashes,
            'signature' => base64_encode($signature),
            'certificate' => $certificate->saveX509($certificate->currentCert),
        ];
    }

    /**
     * @param array  $signature
     * @param string $file
     *
     * @throws \Exception
     */
    public function writeSignature(array $signature, string $file)
    {
        if (!\file_put_contents($file, json_encode($signature, JSON_PRETTY_PRINT))) {
            throw new \Exception("couldn't write signature file");
        }
    }
}
