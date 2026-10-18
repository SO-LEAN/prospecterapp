<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Domain;

enum AccountIntent
{
    case ReviseLegalIdentity;
    case ChangeLogo;
    case Relocate;
}
