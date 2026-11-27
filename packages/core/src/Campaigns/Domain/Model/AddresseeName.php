<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Model;

/**
 * How a contact is named, as the member may read them: their name, their title and their job.
 */
final readonly class AddresseeName
{
    public function __construct(
        public ?string $firstName,
        public ?string $lastName,
        public string $civility,
        public ?string $jobTitle,
    ) {
    }
}
