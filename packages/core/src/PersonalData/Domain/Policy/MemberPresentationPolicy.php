<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Policy;

use Prospecter\Core\PersonalData\Domain\Model\Purpose;

/**
 * Who presents themselves, with their name and picture unmasked, to anyone who holds a link they gave (ADR 0153). A
 * member does, through the profile their account keeps of them. A person the account prospects never does.
 */
final readonly class MemberPresentationPolicy
{
    #[\NoDiscard]
    public static function presents(Purpose $purpose): bool
    {
        return Purpose::Membership === $purpose;
    }
}
