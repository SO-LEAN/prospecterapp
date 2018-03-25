<?php

namespace App\Controller;

use App\Form\LoginForm;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class SecurityController
 * @package App\Controller
 */
class SecurityController extends Controller
{
    /**
     * @Route("/login", name="login")
     */
    public function displayDashboard()
    {
        $authenticationUtils = $this->get('security.authentication_utils');

        return $this->render('page/login.html.twig', [
            'form' => $this->createForm(LoginForm::class, ['userName' => $authenticationUtils->getLastUsername()?: '' ])->createView(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }
    /**
     * @Route("/logout", name="logout")
     */
    public function logoutDashboard()
    {
    }
}