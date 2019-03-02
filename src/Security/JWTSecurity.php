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

namespace Signer\Security;

use Jose\Component\Checker\AlgorithmChecker;
use Jose\Component\Checker\AudienceChecker;
use Jose\Component\Checker\ClaimCheckerManager;
use Jose\Component\Checker\ExpirationTimeChecker;
use Jose\Component\Checker\HeaderCheckerManager;
use Jose\Component\Checker\IssuedAtChecker;
use Jose\Component\Checker\NotBeforeChecker;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Core\Converter\StandardConverter;
use Jose\Component\Core\JWK;
use Jose\Component\Signature\Algorithm\ES256;
use Jose\Component\Signature\JWS;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\JWSTokenSupport;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Jose\Component\Signature\Serializer\JWSSerializerManager;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class JWTSecurity.
 */
class JWTSecurity
{
    /**
     * @var JWK
     */
    private $jwk;

    /**
     * @var AlgorithmManager
     */
    private $algorithmManager;

    /**
     * @var StandardConverter
     */
    private $jsonConverter;

    /**
     * @var JWSBuilder
     */
    private $jwsBuilder;

    /**
     * @var JWSSerializerManager
     */
    private $serializerManager;

    /**
     * JWTSecurity constructor.
     *
     * @param JWKProvider $JWKProvider
     */
    public function __construct(JWKProvider $JWKProvider)
    {
        $this->jwk = $JWKProvider->getJWK();
        $this->algorithmManager = AlgorithmManager::create([
            new ES256(),
        ]);
        $this->jsonConverter = new StandardConverter();

        $this->jwsBuilder = new JWSBuilder(
            $this->jsonConverter,
            $this->algorithmManager
        );

        $this->serializerManager = JWSSerializerManager::create([
            new CompactSerializer($this->jsonConverter),
        ]);
    }

    /**
     * @param Request $request
     *
     * @return bool
     */
    public function isAuthenticated(Request $request)
    {
        $token = $this->extractToken($request);
        if (null === $token) {
            return false;
        }

        try {
            // get the jws
            $jws = $this->getJWSFromToken($token);

            // verify JWS
            if (!$this->verifyJWS($jws)) {
                return false;
            }

            // verify headers
            $this->verifyHeaders($jws);

            // verify claims
            $this->verifyClaims($jws);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * @param string $token
     *
     * @return JWS
     *
     * @throws \Exception
     */
    private function getJWSFromToken(string $token): JWS
    {
        return $this->serializerManager->unserialize($token);
    }

    /**
     * @param JWS $jws
     *
     * @return bool
     */
    private function verifyJWS(JWS $jws)
    {
        $jwsVerifier = new JWSVerifier($this->algorithmManager);

        return $jwsVerifier->verifyWithKey($jws, $this->jwk, 0);
    }

    /**
     * @param JWS $jws
     *
     * @throws \Jose\Component\Checker\InvalidHeaderException
     * @throws \Jose\Component\Checker\MissingMandatoryHeaderParameterException
     */
    private function verifyHeaders(JWS $jws)
    {
        $headerCheckerManager = HeaderCheckerManager::create(
            [
                new AlgorithmChecker(['ES256']),
            ],
            [
                new JWSTokenSupport(),
            ]
        );
        $headerCheckerManager->check($jws, 0, ['alg']);
    }

    /**
     * @param JWS $jws
     *
     * @throws \Jose\Component\Checker\InvalidClaimException
     * @throws \Jose\Component\Checker\MissingMandatoryClaimException
     */
    private function verifyClaims(JWS $jws)
    {
        $claimCheckerManager = ClaimCheckerManager::create(
            [
                new IssuedAtChecker(),
                new NotBeforeChecker(),
                new ExpirationTimeChecker(),
                new AudienceChecker('signer'),
            ]
        );

        $claims = $this->jsonConverter->decode($jws->getPayload());
        $claimCheckerManager->check($claims, ['nbf', 'iat', 'iss', 'aud', 'scope', 'jti']);
    }

    /**
     * @param Request $request
     * @param string  $action
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function isAuthorizedToPerform(Request $request, string $action)
    {
        $token = $this->extractToken($request);
        if (null === $token) {
            return false;
        }

        $jws = $this->getJWSFromToken($token);
        $claims = $this->jsonConverter->decode($jws->getPayload());

        if (!array_key_exists('scope', $claims)) {
            return false;
        }

        if (!is_array($claims['scope'])) {
            return $this->checkClaim($action, $claims['scope']);
        }

        foreach ($claims['scope'] as $scope) {
            if ($this->checkClaim($scope, $action)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $claim
     * @param string $action
     *
     * @return bool
     */
    public function checkClaim(string $claim, string $action)
    {
        $claim_parts = explode(':', $claim);
        $action_parts = explode(':', $action);
        if (2 !== count($claim_parts) || 2 !== count($action_parts)) {
            return false;
        }
        $claim_action = trim($claim_parts[0]);
        $claim_namespace = trim($claim_parts[1]);
        $action_action = trim($action_parts[0]);
        $action_namespace = trim($action_parts[1]);

        if ($claim_action !== $action_action) {
            return false;
        }

        if (empty($claim_action) || empty($claim_namespace) || empty($action_action) || empty($action_namespace)) {
            return false;
        }

        if ('*' !== $claim_namespace && $claim_namespace !== $action_namespace) {
            return false;
        }

        return true;
    }

    /**
     * @param Request $request
     *
     * @return string|null
     */
    public function extractToken(Request $request)
    {
        if (!$request->headers->has('Authorization')) {
            return null;
        }
        $authorizationHeader = $request->headers->get('Authorization');
        $headerParts = explode(' ', $authorizationHeader);
        if (!(2 === count($headerParts) && 0 === strcasecmp($headerParts[0], 'Bearer'))) {
            return null;
        }

        return $headerParts[1];
    }

    /**
     * @param array  $claims
     * @param int    $valid
     * @param string $issuer
     *
     * @return string
     *
     * @throws \Exception
     */
    public function issueToken(array $claims, int $valid, string $issuer): string
    {
        $payload = $this->jsonConverter->encode([
            'jti' => \uniqid('', true),
            'iat' => time(),
            'nbf' => time(),
            'exp' => time() + $valid,
            'iss' => $issuer,
            'aud' => 'signer',
            'scope' => $claims,
        ]);

        $jws = $this->jwsBuilder
            ->create()
            ->withPayload($payload)
            ->addSignature($this->jwk, ['alg' => 'ES256'])
            ->build();

        return $this->serializerManager->serialize(CompactSerializer::NAME, $jws, 0);
    }
}
