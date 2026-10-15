<?php

namespace App\EventListener;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Event\LogoutEvent;

/**
 * Signing out of the authorization server sends the user back to the SPA
 * (Symfony only redirects to the same host by itself).
 */
#[AsEventListener]
final readonly class LogoutListener
{
    public function __construct(#[Autowire(env: 'FRONT_URL')] private string $frontUrl)
    {
    }

    public function __invoke(LogoutEvent $event): void
    {
        $event->setResponse(new RedirectResponse($this->frontUrl));
    }
}
