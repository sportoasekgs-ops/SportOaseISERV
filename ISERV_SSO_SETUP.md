# IServ SSO Integration Setup Guide

This document provides detailed instructions for integrating SportOase with IServ's Single Sign-On (SSO) system using OAuth 2.0 / OpenID Connect (OIDC).

## Prerequisites

- IServ 3.0 or higher installed and running
- Admin access to the IServ server
- Symfony 6.4+ project (SportOase)
- PHP 8.0 or higher

## Step 1: Register Application in IServ

### 1.1 Access IServ Admin Panel

1. Log into your IServ instance as administrator
2. Navigate to: **Verwaltung → System → Single-Sign-On**

### 1.2 Create OAuth2 Client

1. Click **Hinzufügen** (Add) to create a new OpenID Connect client
2. Configure the following settings:

   - **Name**: `SportOase`
   - **Client-ID**: (Auto-generated - copy this value)
   - **Client-Geheimnis** (Client Secret): (Auto-generated - copy this value)
   - **Redirect URI**: `https://your-iserv-domain.de/sportoase/oidc/callback`
   - **Vertrauenswürdig** (Trusted): **Ja** (Yes)
   - **Scopes**: Select `openid`, `profile`, `email`, `roles`

3. Save the configuration
4. **Important**: Copy and securely store the `Client-ID` and `Client-Geheimnis`

## Step 2: Install Required Symfony Packages

Run the following commands in your Symfony project:

```bash
composer require knpuniversity/oauth2-client-bundle
composer require league/oauth2-client
```

## Step 3: Configure OAuth2 Client

### 3.1 Update Environment Variables

Add the following to your `.env` file:

```env
###> IServ OAuth2 Configuration ###
ISERV_BASE_URL=https://your-school.iserv.de
ISERV_CLIENT_ID=your-client-id-from-step-1
ISERV_CLIENT_SECRET=your-client-secret-from-step-1
###< IServ OAuth2 Configuration ###
```

### 3.2 Configure KnpU OAuth2 Client

Create or update `config/packages/knpu_oauth2_client.yaml`:

```yaml
knpu_oauth2_client:
    clients:
        iserv:
            type: generic
            provider_class: League\OAuth2\Client\Provider\GenericProvider
            client_id: '%env(ISERV_CLIENT_ID)%'
            client_secret: '%env(ISERV_CLIENT_SECRET)%'
            redirect_route: oidc_callback
            redirect_params: {}
            provider_options:
                urlAuthorize: '%env(ISERV_BASE_URL)%/iserv/oauth/v2/authorize'
                urlAccessToken: '%env(ISERV_BASE_URL)%/iserv/oauth/v2/token'
                urlResourceOwnerDetails: '%env(ISERV_BASE_URL)%/iserv/oauth/v2/userinfo'
                scopes: 'openid profile email'
```

## Step 4: Create IServ Authenticator

Replace the current `src/IServAuthenticator.php` with the real implementation:

```php
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
        return new RedirectResponse($this->router->generate('app_login'));
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
```

## Step 5: Configure Security

Update `config/packages/security.yaml`:

```yaml
security:
    firewalls:
        main:
            custom_authenticators:
                - SportOase\Security\IServAuthenticator
            entry_point: SportOase\Security\IServAuthenticator
            logout:
                path: app_logout
                target: /
```

## Step 6: Add Routes

Add these routes to `config/routes.yaml`:

```yaml
iserv_login:
    path: /login/iserv
    controller: SportOase\Controller\SecurityController::connectIServ

oidc_callback:
    path: /oidc/callback
    controller: SportOase\Controller\SecurityController::callback

app_logout:
    path: /logout
```

## Step 7: Create Security Controller

Create `src/Controller/SecurityController.php`:

```php
<?php

namespace SportOase\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

class SecurityController extends AbstractController
{
    #[Route('/login/iserv', name: 'iserv_login')]
    public function connectIServ(ClientRegistry $clientRegistry): RedirectResponse
    {
        return $clientRegistry
            ->getClient('iserv')
            ->redirect(['openid', 'profile', 'email'], []);
    }

    #[Route('/oidc/callback', name: 'oidc_callback')]
    public function callback(): never
    {
        throw new \Exception('This should be handled by IServAuthenticator');
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
```

## Step 8: Update User Entity

Ensure your `User` entity has these fields:

```php
#[ORM\Column(type: 'string', length: 255, unique: true)]
private string $username;

#[ORM\Column(type: 'string', length: 255, nullable: true)]
private ?string $iservId = null;

#[ORM\Column(type: 'string', length: 255, nullable: true)]
private ?string $email = null;

#[ORM\Column(type: 'string', length: 255, nullable: true)]
private ?string $fullName = null;

#[ORM\Column(type: 'json')]
private array $roles = [];

#[ORM\Column(type: 'boolean')]
private bool $isActive = true;

#[ORM\Column(type: 'datetime')]
private \DateTime $updatedAt;
```

## Step 9: Create Database Migration

Generate and run the migration for the new User fields:

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

## Step 10: Test the Integration

1. Clear Symfony cache:
   ```bash
   php bin/console cache:clear
   ```

2. Visit `/login/iserv` in your browser

3. You should be redirected to your IServ login page

4. After successful authentication, you'll be redirected back to your application's dashboard

## Troubleshooting

### Error: "Invalid redirect URI"
- Ensure the redirect URI in IServ matches exactly: `https://your-domain/sportoase/oidc/callback`
- Check for trailing slashes

### Error: "Invalid client credentials"
- Verify `ISERV_CLIENT_ID` and `ISERV_CLIENT_SECRET` in `.env`
- Ensure environment variables are loaded

### User not created after login
- Check logs: `var/log/dev.log` or `var/log/prod.log`
- Verify User entity has all required fields
- Ensure database is accessible

### Cannot access OpenID endpoints
- Verify IServ base URL is correct
- Check network connectivity between servers
- Ensure IServ server allows connections from your application server

## Production Checklist

- [ ] Use HTTPS for all connections
- [ ] Set `ISERV_CLIENT_SECRET` as environment variable (not in `.env.local`)
- [ ] Configure proper CORS headers if needed
- [ ] Test logout functionality (clears Symfony session + redirects to IServ logout)
- [ ] Test with different user roles (admin, teacher, student)
- [ ] Implement CSRF protection for OAuth state parameter
- [ ] Set up monitoring for failed authentication attempts
- [ ] Configure session timeout appropriately

## IServ User Data Format

IServ returns user data in this format:

```json
{
  "sub": "user.name",
  "email": "user@school.de",
  "name": "John Doe",
  "preferred_username": "user.name",
  "roles": ["role_teacher", "role_class_10a"]
}
```

## Support

For IServ-specific SSO questions:
- Documentation: https://doku.iserv.de/manage/system/sso/
- IServ Support: Contact your IServ administrator

For SportOase integration issues:
- Email: sportoase.kg@gmail.com

## Version History

- **1.0.0** (2025-11-22): Initial IServ SSO integration guide
