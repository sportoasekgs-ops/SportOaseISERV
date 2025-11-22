<?php

namespace SportOase\Security;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Doctrine\ORM\EntityManagerInterface;
use SportOase\Entity\User;

class IServAuthenticator extends OAuth2Authenticator
{
    public function __construct(
        private ClientRegistry $clientRegistry,
        private RouterInterface $router,
        private EntityManagerInterface $entityManager
    ) {}

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'oidc_callback';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('iserv');
        $accessToken = $this->fetchAccessToken($client);
        
        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function() use ($accessToken, $client) {
                $userData = $client->fetchUserFromToken($accessToken);
                return $this->loadOrCreateUser($userData->toArray());
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return new RedirectResponse($this->router->generate('sportoase_dashboard'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        // Redirect to login page with error message to prevent redirect loops
        $errorMessage = 'IServ-Anmeldung fehlgeschlagen. Bitte versuchen Sie es erneut.';
        
        return new RedirectResponse(
            $this->router->generate('app_login', [
                'error' => urlencode($errorMessage)
            ])
        );
    }

    private function loadOrCreateUser(array $userData): User
    {
        $username = $userData['preferred_username'] ?? $userData['sub'];
        $email = $userData['email'] ?? null;
        $fullName = $userData['name'] ?? $username;
        $iservRoles = $userData['roles'] ?? [];

        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => $username]);

        if (!$user) {
            $user = new User();
            $user->setUsername($username);
            $user->setIservId($userData['sub']);
            $user->setCreatedAt(new \DateTime());
        }

        $user->setEmail($email);
        $user->setFullName($fullName);
        $user->setRoles($this->mapIServRoles($iservRoles));
        $user->setIsActive(true);
        $user->setUpdatedAt(new \DateTime());

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function mapIServRoles(array $iservRoles): array
    {
        $roleMap = [
            'role_admin' => 'ROLE_ADMIN',
            'role_teacher' => 'ROLE_TEACHER',
            'role_student' => 'ROLE_USER',
        ];

        $symfonyRoles = ['ROLE_USER'];
        foreach ($iservRoles as $role) {
            $roleLower = strtolower($role);
            if (isset($roleMap[$roleLower])) {
                $symfonyRoles[] = $roleMap[$roleLower];
            }
        }

        return array_unique($symfonyRoles);
    }
}
