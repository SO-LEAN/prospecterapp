<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Contract\Service\WhichAreContacts;

/**
 * The service PersonalData offers the other contexts (ADR 0045, 0089). It refuses nothing. What is unknown is absent.
 */
interface WhichAreContactsService
{
    public function __invoke(WhichAreContacts $query, WhichAreContactsOutput $output): void;
}
