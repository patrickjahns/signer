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
use Jose\Component\Checker\MissingMandatoryClaimException;
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
 *
 * @phan-file-suppress PhanDeprecatedClass
 * @phan-file-suppress PhanDeprecatedInterface
 */
class JWTSecurity
{
    /**
     * @var ES256
     */
    private $algorithm;

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
        $this->algorithm = new ES256();
        $this->algorithmManager = AlgorithmManager::create([
            $this->algorithm,
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
    public function isAuthenticated(Request $request): bool
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
    private function verifyJWS(JWS $jws): bool
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
    private function verifyHeaders(JWS $jws): void
    {
        $headerCheckerManager = HeaderCheckerManager::create(
            [
                new AlgorithmChecker([$this->algorithm->name()]),
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
    private function verifyClaims(JWS $jws): void
    {
        $claimCheckerManager = ClaimCheckerManager::create(
            [
                new IssuedAtChecker(),
                new NotBeforeChecker(),
                new ExpirationTimeChecker(),
                new AudienceChecker('signer'),
            ]
        );
        $payload = $jws->getPayload();
        if (null === $payload) {
            throw new MissingMandatoryClaimException('missing payload', []);
        }
        $claims = $this->jsonConverter->decode($payload);
        $claimCheckerManager->check($claims, ['nbf', 'iat', 'iss', 'aud', 'scope', 'jti', 'sub']);
    }

    /**
     * @param Request $request
     * @param string  $action
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function isAuthorizedToPerform(Request $request, string $action): bool
    {
        $token = $this->extractToken($request);
        if (null === $token) {
            return false;
        }

        $jws = $this->getJWSFromToken($token);
        $payload = $jws->getPayload();
        if (null === $payload) {
            return false;
        }
        $claims = $this->jsonConverter->decode($payload);

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
    public function checkClaim(string $claim, string $action): bool
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
    public function extractToken(Request $request): ?string
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
    public function issueToken(array $claims, int $valid, string $issuer, string $subject): string
    {
        $payload = $this->jsonConverter->encode([
            'sub' => $subject,
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
            ->addSignature($this->jwk, ['alg' => $this->algorithm->name()])
            ->build();

        return $this->serializerManager->serialize(CompactSerializer::NAME, $jws, 0);
    }
}
