<?php

namespace App\Security;

use App\Entity\ApiToken;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class ApiTokenAuthenticator extends AbstractAuthenticator
{
    /**
     * Header name for authorization with this authenticator
     * @var string
     */
    public const AUTHORIZATION_HEADER = 'Authorization';

    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;

    /**
     * @var PseudoUserProvider
     */
    private PseudoUserProvider $userProvider;

    /**
     * ApiTokenAuthenticator constructor.
     * @param EntityManagerInterface $entityManager
     * @param PseudoUserProvider $userProvider
     */
    public function __construct(EntityManagerInterface $entityManager, PseudoUserProvider $userProvider)
    {
        $this->entityManager = $entityManager;
        $this->userProvider = $userProvider;
    }

    public function supports(Request $request): ?bool
    {
        return $request->headers->has(self::AUTHORIZATION_HEADER)
            && 0 === strpos($request->headers->get(self::AUTHORIZATION_HEADER), 'Bearer ');
    }

    public function authenticate(Request $request): PassportInterface
    {
        $authorizationHeader = $request->headers->get(self::AUTHORIZATION_HEADER);
        $bearerToken = substr($authorizationHeader, 7);

        $apiToken = $this->entityManager->getRepository(ApiToken::class)->findOneBy(['token' => $bearerToken]);
        if ($apiToken === null) {
            throw new BadCredentialsException();
        }

        $user = $this->userProvider->loadUserByUsername($apiToken->getRole());
        return new SelfValidatingPassport($user);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $data = [
            'status' => 401,
            'error' => 'unauthorized',
            'message' => $exception->getMessage(),
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }
}
