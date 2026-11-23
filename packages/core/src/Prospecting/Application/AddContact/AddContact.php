<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\AddContact;

use Prospecter\Core\SharedKernel\Application\Addition;

/**
 * Attaches a contact to an organization. The contact is a person the account prospects, collected beforehand in
 * PersonalData (ADR 0041, 0042).
 */
final readonly class AddContact implements Addition
{
    public function __construct(
        public string $prospectorId,
        public string $id,
        public string $personId,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
