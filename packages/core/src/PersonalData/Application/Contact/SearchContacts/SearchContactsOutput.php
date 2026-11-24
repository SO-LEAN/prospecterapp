<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\Contact\SearchContacts;

use Prospecter\Core\PersonalData\Application\Contact\ContactPage;
use Prospecter\Core\SharedKernel\Application\Handler\OutputPort;

interface SearchContactsOutput extends OutputPort
{
    public function present(ContactPage $page): void;
}
