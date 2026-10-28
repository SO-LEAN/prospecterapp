<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Domain\Model;

enum Role: string
{
    case User = 'ROLE_USER';
    case Prospector = 'ROLE_PROSPECTOR';
    case PersonalData = 'ROLE_PERSONAL_DATA';
    case Admin = 'ROLE_ADMIN';
}
