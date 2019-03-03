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

namespace Signer\Tests\Functional;

use Signer\Security\JWTSecurity;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

class SignControllerTest extends ApiTestCase
{
    public function test_route_requires_authentication()
    {
        $this->client->request('POST', '/sign');
        $response = $this->client->getResponse();
        $this->assertResponseCode($response, Response::HTTP_UNAUTHORIZED);
        $this->assertJsonHeader($response);
    }

    /**
     * @dataProvider httpMethodProvider
     */
    public function test_route_only_allows_post_method($method)
    {
        $this->client->request($method, '/sign');
        $response = $this->client->getResponse();
        $this->assertResponseCode($response, Response::HTTP_METHOD_NOT_ALLOWED);
        $this->assertJsonHeader($response);
    }

    public function test_missing_upload_results_in_error()
    {
        $client = $this->getAuthenticatedClient($this->getToken());
        $client->request('POST', '/sign');
        $response = $client->getResponse();
        $this->assertResponseCode($response, Response::HTTP_BAD_REQUEST);
        $this->assertJsonHeader($response);
    }

    public function test_upload_with_valid_archive_not_enough_permissions()
    {
        $testFile = $this->getTestDataDir() . '/theme-example.tar.gz';
        $uploadedFile = new UploadedFile($testFile, 'theme-example.tar.gz');
        $client = $this->getAuthenticatedClient($this->getToken());
        $client->request('POST', '/sign', [], [$uploadedFile]);
        $response = $client->getResponse();
        $this->assertResponseCode($response, Response::HTTP_FORBIDDEN);
        $this->assertJsonHeader($response);
    }

    public function test_upload_with_valid_archive_and_correct_claims()
    {
        $testFile = $this->getTestDataDir() . '/theme-example.tar.gz';
        $uploadedFile = new UploadedFile($testFile, 'theme-example.tar.gz');
        $client = $this->getAuthenticatedClient($this->getToken(['sign:*']));
        $client->request('POST', '/sign', [], [$uploadedFile]);
        $response = $client->getResponse();
        $this->assertResponseCode($response, Response::HTTP_OK);
    }

    public function test_upload_with_invalid_archive()
    {
        $testFile = $this->getTestDataDir() . '/bad_archive.tar.gz';
        $uploadedFile = new UploadedFile($testFile, 'bad_archive.tar.gz');
        $client = $this->getAuthenticatedClient($this->getToken(['sign:*']));
        $client->request('POST', '/sign', [], [$uploadedFile]);
        $response = $client->getResponse();
        $this->assertResponseCode($response, Response::HTTP_BAD_REQUEST);
    }

    public function test_upload_app_with_missing_appinfo()
    {
        $testFile = $this->getTestDataDir() . '/bad_app.tar.gz';
        $uploadedFile = new UploadedFile($testFile, 'bad_app.tar.gz');
        $client = $this->getAuthenticatedClient($this->getToken(['sign:*']));
        $client->request('POST', '/sign', [], [$uploadedFile]);
        $response = $client->getResponse();
        $this->assertResponseCode($response, Response::HTTP_BAD_REQUEST);
    }

    public function test_upload_app_with_bad_appinfo()
    {
        $testFile = $this->getTestDataDir() . '/bad_appinfo.tar.gz';
        $uploadedFile = new UploadedFile($testFile, 'bad_appinfo.tar.gz');
        $client = $this->getAuthenticatedClient($this->getToken(['sign:*']));
        $client->request('POST', '/sign', [], [$uploadedFile]);
        $response = $client->getResponse();
        $this->assertResponseCode($response, Response::HTTP_BAD_REQUEST);
    }

    public function test_upload_for_app_with_missing_key()
    {
        $testFile = $this->getTestDataDir() . '/app_missing_key.tar.gz';
        $uploadedFile = new UploadedFile($testFile, 'app_missing_key.tar.gz');
        $client = $this->getAuthenticatedClient($this->getToken(['sign:*']));
        $client->request('POST', '/sign', [], [$uploadedFile]);
        $response = $client->getResponse();
        $this->assertResponseCode($response, Response::HTTP_NOT_FOUND);
    }

    public function test_upload_for_app_with_bad_infoxml()
    {
        $testFile = $this->getTestDataDir() . '/bad_infoxml.tar.gz';
        $uploadedFile = new UploadedFile($testFile, 'bad_infoxml.tar.gz');
        $client = $this->getAuthenticatedClient($this->getToken(['sign:*']));
        $client->request('POST', '/sign', [], [$uploadedFile]);
        $response = $client->getResponse();
        $this->assertResponseCode($response, Response::HTTP_BAD_REQUEST);
    }

    protected function getAuthenticatedClient($token)
    {
        return self::createClient([], [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_Authorization' => 'Bearer ' . $token,
        ]);
    }

    protected function getToken($claims = [])
    {
        $container = $this->getTestContainer();
        $tokenSecurity = $container->get(JWTSecurity::class);

        return $tokenSecurity->issueToken($claims, 3600, 'tester');
    }

    protected function getTestDataDir()
    {
        return self::$kernel->getProjectDir() . '/tests/data';
    }

    protected function getTestContainer(): ContainerInterface
    {
        return self::$kernel->getContainer()->get('test.service_container');
    }

    public function httpMethodProvider()
    {
        return [
            ['GET'],
            ['HEAD'],
            ['PUT'],
        ];
    }
}
