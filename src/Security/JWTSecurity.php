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
use Jose\Component\Core\AlgorithmManagerFactory;
use Jose\Component\Core\Converter\StandardConverter;
use Jose\Component\Core\JWK;
use Jose\Component\Signature\Algorithm\ES256;
use Jose\Component\Signature\JWSTokenSupport;
use Jose\Component\Signature\JWSVerifierFactory;
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
     * JWTSecurity constructor.
     */
    public function __construct(JWKProvider $JWKProvider)
    {
        $this->jwk = $JWKProvider->getJWK();
    }

    //TODO: refactor into smaller functions
    public function isAuthenticated(Request $request)
    {
        $token = $this->extractToken($request);
        if (null === $token) {
            return false;
        }

        try {
            $algorithmManagerFactory = new AlgorithmManagerFactory();
            $algorithmManagerFactory->add('ES256', new ES256());
            $jwsVerifierFactory = new JWSVerifierFactory($algorithmManagerFactory);
            $jwsVerifier = $jwsVerifierFactory->create(['ES256']);

            // The JSON Converter.
            $jsonConverter = new StandardConverter();

            // The serializer manager. We only use the JWS Compact Serialization Mode.
            $serializerManager = JWSSerializerManager::create([
                new CompactSerializer($jsonConverter),
            ]);

            $jws = $serializerManager->unserialize($token);
            $jwsVerifier->verifyWithKey($jws, $this->jwk, 0);

            $headerCheckerManager = HeaderCheckerManager::create(
                [
                    new AlgorithmChecker(['ES256']), // We check the header "alg" (algorithm)
                ],
                [
                    new JWSTokenSupport(), // Adds JWS token type support
                ]
            );
            $headerCheckerManager->check($jws, 0, ['alg']);
            $claimCheckerManager = ClaimCheckerManager::create(
                [
                    new IssuedAtChecker(),
                    new NotBeforeChecker(),
                    new ExpirationTimeChecker(),
                    new AudienceChecker('signer'),
                ]
            );

            $claims = $jsonConverter->decode($jws->getPayload());
            $claimCheckerManager->check($claims, ['nbf', 'iat', 'iss', 'aud', 'scope', 'jti']);
        } catch (\Exception $e) {
            return false;
        }

        return true;
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
        // The JSON Converter.
        $jsonConverter = new StandardConverter();

        // The serializer manager. We only use the JWS Compact Serialization Mode.
        $serializerManager = JWSSerializerManager::create([
            new CompactSerializer($jsonConverter),
        ]);
        $jws = $serializerManager->unserialize($token);
        $claims = $jsonConverter->decode($jws->getPayload());
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
}
