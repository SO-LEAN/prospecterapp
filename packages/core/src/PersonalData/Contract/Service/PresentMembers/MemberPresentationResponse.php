<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Contract\Service\PresentMembers;

/**
 * The published language of a member as they present themselves to whom they give a link (ADR 0153): their name and
 * the address of their picture, in clear, theirs to give.
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
