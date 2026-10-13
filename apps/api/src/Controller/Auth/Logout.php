<?php

namespace App\Controller\Auth;

use Symfony\Component\Routing\Attribute\Route;

/**
 * Handled by the firewall (see LogoutListener), the route only has to exist.
 */
final class Logout
{
    /**
     * @codeCoverageIgnore
     */
    #[Route('/logout', name: 'logout', methods: ['GET'])]
    public function __invoke(): never
    {
        throw new \LogicException('This method is intercepted by the logout key of the firewall.');
    }
}
