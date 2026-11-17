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

    public function concernsAContactOnly(): bool
    {
        return in_array($this, [self::ChangeCivility, self::Forget], true);
    }
}
