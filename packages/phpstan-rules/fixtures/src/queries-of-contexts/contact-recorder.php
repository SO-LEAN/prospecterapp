<?php

declare(strict_types=1);

namespace App\Identification;

use Prospecter\Core\PersonalData\Contract\Service\NameContacts\NameContacts;
use Prospecter\Core\Prospecting\Contract\Service\NameOrganizationsOfContacts\NameOrganizationsOfContacts;

final readonly class ContactRecorder
{
    public function names(): NameContacts
    {
        return new NameContacts('reader', ['person']);
    }

    public function organizations(): NameOrganizationsOfContacts
    {
        return new NameOrganizationsOfContacts('account', ['person']);
    }
}
