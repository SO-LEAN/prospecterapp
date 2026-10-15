<?php

declare(strict_types=1);

namespace Prospecter\Core\Domain\User;

enum Role: string
{
    case User = 'ROLE_USER';
    case Prospector = 'ROLE_PROSPECTOR';
    case Admin = 'ROLE_ADMIN';
}
