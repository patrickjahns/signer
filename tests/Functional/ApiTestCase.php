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

use Signer\Tests\Helper\ApiClient;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Response;

class ApiTestCase extends KernelTestCase
{
    protected static function createClient(array $options = [], array $server = [])
    {
        $kernel = static::bootKernel($options);
        $client = new ApiClient($kernel);
        $client->setServerParameters($server);

        return $client;
    }

    /**
     * @before
     */
    public function setUpClient()
    {
        $this->client = self::createClient([], [
            'HTTP_ACCEPT' => 'application/json',
        ]);
    }

    /**
     * @param Response $response
     * @param int      $statusCode
     */
    protected function assertResponseCode(Response $response, $statusCode)
    {
        self::assertEquals($statusCode, $response->getStatusCode(), $response->getContent());
    }

    /**
     * @param Response $response
     * @param string   $contentType
     */
    protected function assertHeader(Response $response, $contentType)
    {
        if (method_exists(self::class, 'assertStringContainsString')) {
            self::assertStringContainsString(
                $contentType,
                $response->headers->get('Content-Type'),
                $response->headers
            );
        } else {
            self::assertContains(
                $contentType,
                $response->headers->get('Content-Type'),
                $response->headers
            );
        }
    }

    /**
     * @param Response $response
     */
    protected function assertJsonHeader(Response $response)
    {
        self::assertHeader($response, 'application');
        self::assertHeader($response, 'json');
    }
}
