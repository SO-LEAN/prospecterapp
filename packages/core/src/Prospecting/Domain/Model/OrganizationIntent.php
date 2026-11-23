<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

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
    /** Attach a person the account prospects. */
    case AddContact;
    /** Detach one of its contacts. */
    case RemoveContact;
    /** Remove it from the prospecting base, and erase the contacts that no other organization names. */
    case StopProspecting;
    /** After a check, state that it is no duplicate of an organization it resembles. */
    case DismissPossibleDuplicate;
    /** Fill what it lacks, and never change what is recorded (ADR 0177). */
    case Complete;
}
