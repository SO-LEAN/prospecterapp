<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Contract\Service\WhichAreContacts;

interface WhichAreContactsOutput
{
    /**
     * @param list<string> $personIds the persons asked who are contacts of the account; an unknown person is absent
     */
    public function present(array $personIds): void;
}
