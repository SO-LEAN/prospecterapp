<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Model;

/**
 * Why an account keeps a person's personal data: the person works in the account, or the account prospects them.
 */
enum Purpose: string
{
    case Membership = 'membership';
    case Prospecting = 'prospecting';
}
