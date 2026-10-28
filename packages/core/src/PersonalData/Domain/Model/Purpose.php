<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Model;

/**
 * Why an account keeps the personal data of a person: the person works in it, or it prospects them.
 */
enum Purpose: string
{
    case Membership = 'membership';
    case Prospecting = 'prospecting';
}
