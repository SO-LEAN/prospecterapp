<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\PresentMembers;

use Prospecter\Core\SharedKernel\Application\FileResponse;

/**
 * A member as they present themselves to whom they give a link: their name and their picture, in clear.
 */
final readonly class MemberPresentation
{
    public function __construct(
        public string $personId,
        public ?string $firstName,
        public ?string $lastName,
        public ?FileResponse $picture,
    ) {
    }
}
