<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Model;

enum PersonIntent
{
    case Rename;
    case ChangeJobTitle;
    /** Note how one addresses a contact: never a member, whose civility is not noted. */
    case ChangeCivility;
    case ChangeContactDetails;
    case ChangePicture;
    /** Erase a prospected person: never a member, who is no contact. */
    case Forget;
    /** Say, once checked, that a contact is no duplicate of one they resemble: never a member, who is none. */
    case DismissPossibleDuplicate;
    /** Fill what a contact lacks, never changing what is noted (ADR 0177): never a member, who completes their profile themself. */
    case Complete;

    public function concernsAContactOnly(): bool
    {
        return in_array($this, [self::ChangeCivility, self::Forget, self::DismissPossibleDuplicate, self::Complete], true);
    }
}
