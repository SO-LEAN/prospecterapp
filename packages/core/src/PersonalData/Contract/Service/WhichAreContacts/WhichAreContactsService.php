<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Contract\Service\WhichAreContacts;

/**
 * The service PersonalData offers the other contexts (ADR 0045, 0089): it refuses nothing, what is unknown is
 * absent.
 */
interface WhichAreContactsService
{
    public function __invoke(WhichAreContacts $query, WhichAreContactsOutput $output): void;
}
