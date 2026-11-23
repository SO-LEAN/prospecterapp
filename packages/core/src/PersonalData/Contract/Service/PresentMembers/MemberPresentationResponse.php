<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Contract\Service\PresentMembers;

/**
 * The published language for how a member presents themselves to the people they give a link to (ADR 0153). It holds
 * their name and their picture's address, unmasked, because these are the member's to give.
 */
final readonly class MemberPresentationResponse
{
    public function __construct(
        public string $personId,
        public ?string $firstName,
        public ?string $lastName,
        public ?string $pictureUrl,
    ) {
    }
}
