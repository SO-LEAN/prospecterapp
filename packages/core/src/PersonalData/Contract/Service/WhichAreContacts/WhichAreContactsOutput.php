<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Contract\Service\WhichAreContacts;

interface WhichAreContactsOutput
{
    /**
     * @param list<string> $personIds those, among the persons asked, who are contacts of the account; an unknown one is absent
     */
    public function present(array $personIds): void;
}
