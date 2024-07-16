<?php

namespace App\Security;

use App\Services\Internal\Jwt\UserJwtTokenService;
use Exception;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\InvalidTokenException;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Authentication\Token\JWTUserToken;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Authentication\Token\PreAuthenticationJWTUserToken;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Guard\JWTTokenAuthenticator;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\TokenExtractorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use TypeError;

/**
 * Extends the Lexit Bundle authentication logic
 */
class LexitBundleJwtTokenAuthenticator extends JWTTokenAuthenticator
{
    public function __construct(
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly EventDispatcherInterface $dispatcher,
        private readonly TokenExtractorInterface  $tokenExtractor,
        private readonly TokenStorageInterface    $preAuthenticationTokenStorage,
        private readonly LoggerInterface          $logger,
        private readonly UserJwtTokenService      $userJwtTokenService,
    ) {
        parent::__construct($jwtManager, $dispatcher, $tokenExtractor, $preAuthenticationTokenStorage);
    }

    /**
     * @param Request $request
     *
     * @return PreAuthenticationJWTUserToken
     */
    public function getCredentials(Request $request): PreAuthenticationJWTUserToken
    {
        $jwtToken = $request->query->get('token');
        if (empty($jwtToken)) {
            throw new InvalidTokenException("Jwt token is empty");
        }

        $preAuthToken = new PreAuthenticationJWTUserToken($jwtToken);

        try {
            $userName = $this->userJwtTokenService->getUserName($jwtToken);
        } catch (JWTDecodeFailureException $e) {
            // this particular exception has to be caught and the other needs to be thrown else got 500 which is invalid
            throw new InvalidTokenException($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        $preAuthToken->setPayload([
            UserJwtTokenService::USER_IDENTIFIER => $userName,
        ]);
        return $preAuthToken;
    }

    /**
     * @param Request $request
     *
     * @return bool
     */
    public function supports(Request $request): bool
    {
        return str_starts_with($request->getRequestUri(), "/api/external");
    }

    /**
     * @param PreAuthenticationJWTUserToken $credentials
     * @param UserInterface $user
     * @return bool
     */
    public function checkCredentials($credentials, UserInterface $user): bool
    {
        $isValid = $this->isTokenValid($credentials->getCredentials());
        return $isValid;
    }

    /**
     * Will check if token is valid
     *
     * @param string $rawToken
     * @return bool
     */
    public function isTokenValid(string $rawToken): bool
    {
        $token = new JWTUserToken();
        $token->setRawToken($rawToken);
        try {
            $this->jwtManager->decode($token);
            return true;
        } catch (Exception|TypeError $e) {
            $this->logger->error("Could not parse the jwt token", [
                "exceptionMessage" => $e->getMessage(),
            ]);
            return false;
        }
    }
}
