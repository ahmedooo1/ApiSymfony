<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use App\Entity\User; // Adjust the namespace as needed
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Psr\Log\LoggerInterface;

class JwtAuthenticator extends AbstractAuthenticator
{
    private $secret;
    private $logger;
    private $userProvider;

    public function __construct(string $secret, LoggerInterface $logger, UserProviderInterface $userProvider)
    {
        $this->secret = $secret;
        $this->logger = $logger;
        $this->userProvider = $userProvider;
    }

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('Authorization');
    }

    public function getCredentials(Request $request)
    {
        $authorizationHeader = $request->headers->get('Authorization');

        if ($authorizationHeader === null || !preg_match('/Bearer\s(\S+)/', $authorizationHeader, $matches)) {
            return null;
        }

        return $matches[1];
    }

    public function getUser($credentials, UserProviderInterface $userProvider): ?User
    {
        try {
            $decoded = JWT::decode($credentials, new Key($this->secret, 'HS256'));
            $this->logger->info('Token decoded successfully', ['username' => $decoded->username]);

            $user = $userProvider->loadUserByIdentifier($decoded->username);
            if ($user instanceof User && isset($decoded->roles)) {
                $user->setRoles($decoded->roles);
            }

            $this->logger->info('User roles', ['roles' => $user->getRoles()]);

            return $user;
        } catch (\Exception $e) {
            $this->logger->error('Token decoding failed', ['exception' => $e->getMessage()]);
            throw new CustomUserMessageAuthenticationException('Invalid Token');
        }
    }

    public function checkCredentials($credentials, UserInterface $user): bool
    {
        return true; // As we are using JWT, we assume the credentials are valid if token is valid
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?JsonResponse
    {
        return new JsonResponse(['message' => 'Authentication Failed'], JsonResponse::HTTP_UNAUTHORIZED);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $providerKey): ?JsonResponse
    {
        return null; // On success, just return null to let the request proceed
    }

    public function start(Request $request, AuthenticationException $authException = null): JsonResponse
    {
        return new JsonResponse(['message' => 'Authentication Required'], JsonResponse::HTTP_UNAUTHORIZED);
    }

    public function supportsRememberMe(): bool
    {
        return false;
    }

    public function authenticate(Request $request): Passport
    {
        $credentials = $this->getCredentials($request);
        if (null === $credentials) {
            $this->logger->error('No token provided');
            throw new CustomUserMessageAuthenticationException('No token provided');
        }

        try {
            $decoded = JWT::decode($credentials, new Key($this->secret, 'HS256'));
            $this->logger->info('Token decoded successfully', ['username' => $decoded->username]);
        } catch (\Exception $e) {
            $this->logger->error('Token decoding failed', ['exception' => $e->getMessage()]);
            throw new CustomUserMessageAuthenticationException('Invalid Token');
        }

        return new SelfValidatingPassport(
            new UserBadge($decoded->username, function($username) use ($decoded) {
                $user = $this->userProvider->loadUserByIdentifier($username);
                if (isset($decoded->roles)) {
                    $user->setRoles($decoded->roles);
                }
                return $user;
            })
        );
    }
}
