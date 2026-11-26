<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Contact\ListOrganizationContacts;

use Prospecter\Core\SharedKernel\Application\Handler\OutputPort;

interface ListOrganizationContactsOutput extends OutputPort
{
    public function present(OrganizationContacts $contacts): void;
}
