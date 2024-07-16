<?php

namespace App\Services\Internal\Jwt;

use App\Entity\User;
use App\Security\LexitBundleJwtTokenAuthenticator;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTEncodeFailureException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Provides logic for handling jwt tokens in context of {@see User}
 */
#[Route("/api/external", name: "api_external_")]
class UserJwtTokenService
{
    /**
     * This name is necessary for the {@see LexitBundleJwtTokenAuthenticator} to work properly
     */
    const USER_IDENTIFIER = "UserIdentifier";

    public function __construct(
        private readonly JwtTokenService $jwtTokenService,
    ){}

    /**
     * Will create the jwt token for {@see User}
     *
     * @param User $user
     * @param bool $endlessLifetime
     *
     * @return string
     *
     * @throws JWTEncodeFailureException
     */
    public function generate(User $user, bool $endlessLifetime = false): string
    {
        return $this->jwtTokenService->encode([
            self::USER_IDENTIFIER => $user->getUsername(),
        ], $endlessLifetime);
    }

    /**
     * Will extract the Username string from the jwt token payload and return it
     * If {@see UserJwtTokenService::USER_IDENTIFIER} is missing, then NULL will be returned
     *
     * @param string $jwtToken
     *
     * @return string|null
     * @throws JWTDecodeFailureException
     */
    public function getUserName(string $jwtToken): ?string
    {
        $payload = $this->jwtTokenService->decode($jwtToken);

        return $payload[self::USER_IDENTIFIER] ?? null;
    }

}
