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

namespace Signer\Tests\Unit\Security;

use PHPUnit\Framework\TestCase;
use Signer\Security\JWKProvider;
use Signer\Security\JWTSecurity;

class JWTSecurityTest extends TestCase
{
    /**
     * @var JWTSecurity
     */
    private $security;

    public function setUp(): void
    {
        $jwkProvider = $this->createMock(JWKProvider::class);
        $this->security = new JWTSecurity($jwkProvider);
        parent::setUp();
    }

    /**
     * @dataProvider claimProvider
     */
    public function testCheckClaim($claim, $action, $expected)
    {
        self::assertSame($expected, $this->security->checkClaim($claim, $action));
    }

    public function claimProvider()
    {
        return [
            'action is the same' => ['sign: *', 'sign: test', true],
            'action is not the same' => ['sign: *', 'count: test', false],
            'action is not the same - reversed' => ['count: *', 'test: test', false],
            'scope is empty' => ['', 'sign: test', false],
            'claim misses :' => ['test', 'test: test', false],
            'claim doesnt have a namespace' => ['test:', 'test: test', false],
            'claim and action without namespace' => ['test:', 'test:', false],
            'claim and action without namespace' => ['test:    ', 'test:     ', false],
            'claim and action without func' => [': test', ':test', false],
            'specific claim namespace' => ['sign: namespace', 'sign: test', false],
            'same namespace' => ['sign: test', 'sign: test', true],
        ];
    }
}
