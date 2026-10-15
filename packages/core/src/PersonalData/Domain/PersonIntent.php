<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain;

enum PersonIntent
{
    case Rename;
    case ChangeJobTitle;
    case ChangeContactDetails;
    case ChangePicture;
    /** Erase a prospected person: never a member, who is no contact. */
    case Forget;
}
