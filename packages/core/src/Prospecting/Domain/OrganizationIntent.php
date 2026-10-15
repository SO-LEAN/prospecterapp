<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain;

enum OrganizationIntent
{
    case ReviseLegalIdentity;
    case ChangeContactDetails;
    case Relocate;
    case WriteObservations;
    case AttachToHolding;
    case DetachFromHolding;
    case ChangeLogo;
    case RemoveLogo;
    /** Attach a person the account prospects; needs the right to manage personal data. */
    case AddContact;
    /** Detach one of its contacts; needs the right to manage personal data. */
    case RemoveContact;
    /** Remove it from the prospecting base; with contacts, needs the right to manage personal data. */
    case StopProspecting;
}
