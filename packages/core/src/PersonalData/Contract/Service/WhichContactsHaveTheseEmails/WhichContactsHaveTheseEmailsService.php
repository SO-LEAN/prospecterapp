<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Contract\Service\WhichContactsHaveTheseEmails;

/**
 * The service PersonalData offers the other contexts (ADR 0045, 0089): it refuses nothing, what is unknown is
 * absent.
 */
interface WhichContactsHaveTheseEmailsService
{
    public function __invoke(WhichContactsHaveTheseEmails $query, WhichContactsHaveTheseEmailsOutput $output): void;
}
