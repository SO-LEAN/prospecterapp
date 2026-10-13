<?php

namespace App\Gateway;

use Prospecter\Core\Gateway\UserNotifier;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;

class UserNotifierSymfonyAdapter implements UserNotifier
{
    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    public function addSuccess(string $message): void
    {
        $this->add('success', $message);
    }

    public function addWarning(string $message): void
    {
        $this->add('warning', $message);
    }

    public function addError(string $message): void
    {
        $this->add('error', $message);
    }

    public function addInfo(string $message): void
    {
        $this->add('info', $message);
    }

    private function add(string $type, string $message): void
    {
        $session = $this->requestStack->getSession();

        if ($session instanceof FlashBagAwareSessionInterface) {
            $session->getFlashBag()->add($type, $message);
        }
    }
}
