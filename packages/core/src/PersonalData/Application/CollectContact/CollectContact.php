<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\CollectContact;

use Prospecter\Core\SharedKernel\Application\Addition;

/**
 * Keeps a person the account prospects, under the identity the client gives it, as a contact of these
 * organizations: at least one (ADR 0036, 0042).
 */
final readonly class CollectContact implements Addition
{
    /**
     * @param list<string> $links           the addresses of their links
     * @param list<string> $organizationIds
     */
    public function __construct(
        public string $readerId,
        public string $id,
        public ?string $firstName,
        public ?string $lastName,
        public string $civility,
        public ?string $jobTitle,
        public ?string $email,
        public ?string $phoneNumber,
        public ?string $language,
        public array $links,
        public array $organizationIds,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
