<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Model;

enum PersonIntent
{
    case Rename;
    case ChangeJobTitle;
    /** Log a contact's title. Never a member's, whose title is not logged. */
    case ChangeCivility;
    case ChangeContactDetails;
    case ChangePicture;
    /** Erase a prospected person. Never a member, who is not a contact. */
    case Forget;
    /** Say, after checking, that a contact is not the duplicate of a contact they resemble. Never a member, who is not a contact. */
    case DismissPossibleDuplicate;
    /** Fill what a contact lacks without changing what is logged (ADR 0177). Never a member, who completes their own profile. */
    case Complete;

    public function concernsAContactOnly(): bool
    {
        return in_array($this, [self::ChangeCivility, self::Forget, self::DismissPossibleDuplicate, self::Complete], true);
    }
}
