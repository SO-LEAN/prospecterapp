<?php

namespace Prospecter\Core\Traits\UseCase;

use Prospecter\Core\Gateway\UserNotifier;

trait UserNotifierTrait
{
    /**
     * @var UserNotifier
     */
    private $userNotifier;


    private function notifySuccess(string $msg)
    {
        $this->userNotifier->addSuccess($msg);
    }
}
