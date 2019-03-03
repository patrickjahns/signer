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

    public function httpMethodProvider()
    {
        return [
            ['GET'],
            ['HEAD'],
            ['PUT'],
        ];
    }
}
