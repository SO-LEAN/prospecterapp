<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Contract\Service\WhichEmailsHaveTheseContacts;

/**
 * The service PersonalData offers the other contexts (ADR 0045, 0089). It refuses nothing. What the member may not read
 * is absent.
 */
interface WhichEmailsHaveTheseContactsService
{
    public function __invoke(WhichEmailsHaveTheseContacts $query, WhichEmailsHaveTheseContactsOutput $output): void;
}
