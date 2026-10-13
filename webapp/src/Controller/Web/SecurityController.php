<?php

namespace App\Controller\Web;

use App\Form\UseCaseType\LoginType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        return $this->render('page/login.html.twig', [
            'form' => $this->createForm(LoginType::class, ['userName' => $authenticationUtils->getLastUsername()]),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    /**
     * @codeCoverageIgnore
     */
    #[Route('/logout', name: 'logout')]
    public function logout(): never
    {
        throw new \LogicException('This method is intercepted by the logout key of the firewall.');
    }
}
