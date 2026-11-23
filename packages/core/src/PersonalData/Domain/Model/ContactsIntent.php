<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Model;

/**
 * What a reader may do with their account's contacts as a whole. A contact is collected from an organization, never
 * alone.
 */
enum ContactsIntent
{
    case SearchByText;
}
