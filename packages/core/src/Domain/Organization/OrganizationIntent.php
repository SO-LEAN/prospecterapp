<?php

declare(strict_types=1);

namespace Prospecter\Core\Domain\Organization;

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
}
