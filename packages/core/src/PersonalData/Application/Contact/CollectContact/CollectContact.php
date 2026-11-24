<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\Contact\CollectContact;

use Prospecter\Core\SharedKernel\Application\Handler\Addition;

/**
 * Keeps a person the account prospects as a contact of these organizations, under the id the client gives. A contact
 * has at least one organization (ADR 0036, 0042). `byAnAI` is true when an AI acting as the reader collects them.
 */
final readonly class CollectContact implements Addition
{
    /**
     * @param list<string> $links           their links' addresses
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
        public bool $byAnAI = false,
    ) {
    }
}
