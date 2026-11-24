<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\ForgetContactLeftUnattached;

final readonly class ForgetContactLeftUnattached
{
    /**
     * @param string $holderId the member who held the contact to attach
     */
    public function __construct(
        public string $holderId,
        public string $contactId,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
