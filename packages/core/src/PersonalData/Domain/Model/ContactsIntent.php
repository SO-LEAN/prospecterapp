<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Model;

/**
 * What a reader may do with the contacts of their account, as a whole; a contact is collected from an
 * organization, never alone.
 */
enum ContactsIntent
{
    case SearchByText;
}
