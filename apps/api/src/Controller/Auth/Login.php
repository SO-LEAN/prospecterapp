<?php

namespace App\Controller\Auth;

use App\Form\LoginType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Sign-in page of the authorization server: the SPA sends the user here through /authorize.
 */
final class Login extends AbstractController
{
    #[Route('/login', name: 'login', methods: ['GET', 'POST'])]
    public function __invoke(AuthenticationUtils $authenticationUtils): Response
    {
        return $this->render('auth/login.html.twig', [
            'form' => $this->createForm(LoginType::class, ['userName' => $authenticationUtils->getLastUsername()]),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }
}
