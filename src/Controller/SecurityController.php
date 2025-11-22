<?php

namespace SportOase\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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

    #[Route('/login', name: 'app_login')]
    public function login(Request $request, ClientRegistry $clientRegistry): Response
    {
        // Check if there's an authentication error
        $error = $request->query->get('error');
        
        if ($error) {
            // Render login page with error message
            return $this->render('@SportOase/security/login.html.twig', [
                'error' => $error,
            ]);
        }
        
        // Automatically redirect to IServ OAuth2 login
        return $clientRegistry
            ->getClient('iserv')
            ->redirect(['openid', 'profile', 'email'], []);
    }
}
