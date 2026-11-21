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
    /** Remove it from the prospecting base, erasing its contacts no other organization names. */
    case StopProspecting;
    /** Say, once checked, that it is no duplicate of an organization it resembles. */
    case DismissPossibleDuplicate;
    /** Fill what it lacks, never changing what is noted (ADR 0177). */
    case Complete;
}
