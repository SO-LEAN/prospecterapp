<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Model;

/**
 * What a reader may do with their account's contacts as a whole. A contact collected from the list still names its
 * organizations.
 */
enum ContactsIntent
{
    case SearchByText;
    case Collect;
}
