<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Contract\Service\NameContacts;

/**
 * The service PersonalData offers the other contexts (ADR 0045, 0089): it refuses nothing, a person unknown, of
 * another account or forgotten is absent from its answer.
 */
interface NameContactsService
{
    public function __invoke(NameContacts $query, NameContactsOutput $output): void;
}
