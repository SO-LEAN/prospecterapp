<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\StartProspecting;

/**
 * Enters an organization in the prospecting base of the account, under the identity the client gives it; `byAnAI`
 * when an AI enters it, acting as the prospector.
 */
final readonly class StartProspecting
{
    public function __construct(
        public string $prospectorId,
        public string $id,
        public ?string $corporateName,
        public ?string $form,
        public ?string $type,
        public ?string $email,
        public ?string $phoneNumber,
        public ?string $language,
        public ?string $street,
        public ?string $postalCode,
        public ?string $city,
        public ?string $country,
        public ?string $observations,
        public ?string $holdingId,
        public \DateTimeImmutable $executedAt,
        /** @var list<string> the addresses of its links */
        public array $links = [],
        public ?string $website = null,
        public bool $byAnAI = false,
    ) {
    }
}
