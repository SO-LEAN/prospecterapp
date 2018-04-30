<?php

namespace App\Gateway;

use Solean\CleanProspecter\Gateway\UserNotifier;
use Symfony\Component\HttpFoundation\Session\Session;

class UserNotifierSymfonyAdapter implements UserNotifier
{
    /**
     * @var Session
     */
    private $session;

    /**
     * @param Session $session
     */
    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * @param string $message
     */
    public function addSuccess(string $message): void
    {
        $this->session->getFlashBag()->add('success', $message);
    }

    /**
     * @param string $message
     */
    public function addWarning(string $message): void
    {
        $this->session->getFlashBag()->add('warning', $message);
    }

    /**
     * @param string $message
     */
    public function addError(string $message): void
    {
        $this->session->getFlashBag()->add('error', $message);
    }

    /**
     * @param string $message
     */
    public function addInfo(string $message): void
    {
        $this->session->getFlashBag()->add('info', $message);
    }
}
