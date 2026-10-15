<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Account;

final readonly class ChangeMyUserNameRequest
{
    public function __construct(public string $userName)
    {
    }
}
