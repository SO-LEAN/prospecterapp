<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Policy;

use Prospecter\Core\PersonalData\Domain\Model\Purpose;

/**
 * Who presents themselves, their name and their picture in clear, to anyone holding a link they gave (ADR 0153): a
 * member, by the profile their account keeps of them; never a person the account prospects.
 */
final readonly class MemberPresentationPolicy
{
    #[\NoDiscard]
    public static function presents(Purpose $purpose): bool
    {
        return Purpose::Membership === $purpose;
    }
}
