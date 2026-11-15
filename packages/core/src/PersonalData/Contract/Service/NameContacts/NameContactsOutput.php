<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Contract\Service\NameContacts;

interface NameContactsOutput
{
    /**
     * @param list<ContactNameResponse> $names those of the persons the reader reaches, none for an unknown reader
     */
    public function present(array $names): void;
}
