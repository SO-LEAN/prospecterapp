<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Model;

/**
 * What a message names a contact by: their name, their first organization and their job title, each unknown or not.
 */
final readonly class Addressee
{
    public function __construct(
        public ?string $firstName,
        public ?string $lastName,
        public ?string $organization,
        public ?string $jobTitle,
    ) {
    }
}
