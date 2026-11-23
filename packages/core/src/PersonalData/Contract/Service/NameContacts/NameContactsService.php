<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Contract\Service\NameContacts;

/**
 * The service PersonalData offers the other contexts (ADR 0045, 0089). It refuses nothing. Its answer leaves out a
 * person who is unknown, forgotten or in another account.
 */
interface NameContactsService
{
    public function __invoke(NameContacts $query, NameContactsOutput $output): void;
}
