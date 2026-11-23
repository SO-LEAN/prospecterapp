<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\ForgetContactsLeftWithoutOrganization;

final readonly class ForgetContactsLeftWithoutOrganization
{
    /**
     * @param string       $readerId   the member whose gesture left the contacts alone
     * @param list<string> $contactIds
     */
    public function __construct(
        public string $readerId,
        public array $contactIds,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
