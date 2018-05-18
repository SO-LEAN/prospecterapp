<?php

namespace App\Controller\Web;

use App\Form\UseCaseType\LoginType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * Class SecurityController.
 */
class SecurityController extends Controller
{
    /**
     * @Route("/login", name="login")
     */
    public function login()
    {
        $authenticationUtils = $this->get('security.authentication_utils');

        return $this->render('page/login.html.twig', [
            'form' => $this->createForm(LoginType::class, ['userName' => $authenticationUtils->getLastUsername() ?: ''])->createView(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    /**
     * @Route("/logout", name="logout")
     * @codeCoverageIgnore
     */
    public function logout()
    {
    }
}
