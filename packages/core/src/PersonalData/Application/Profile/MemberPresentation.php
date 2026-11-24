<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\Profile;

use Prospecter\Core\SharedKernel\Application\Response\FileResponse;

/**
 * A person as they would present themselves to someone they give a link to. It holds their unmasked name and picture.
 * It also holds why their account keeps them, which decides whether they present themselves at all
 * (`Rule\MemberPresentation`).
 */
final readonly class MemberPresentation
{
    public function __construct(
        public string $personId,
        public ?string $firstName,
        public ?string $lastName,
        public ?FileResponse $picture,
        public string $purpose,
    ) {
    }
}
