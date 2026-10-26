<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\SearchContacts;

use Prospecter\Core\SharedKernel\Application\OutputPort;

interface SearchContactsOutput extends OutputPort
{
    public function present(SearchContactsResponse $page): void;
}
