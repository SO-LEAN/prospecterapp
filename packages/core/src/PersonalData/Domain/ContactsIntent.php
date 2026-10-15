<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain;

/**
 * What a reader may do with the contacts of their account, as a whole.
 */
enum ContactsIntent
{
    case Collect;
    case SearchByText;
}
