<?php

declare(strict_types=1);

namespace Prospecter\Core\UseCase\UpdateOrganization;

use Prospecter\Core\Entity\Address;

/**
 * The whole organization: what is left out is cleared, except the logo, kept unless a new one is sent.
 */
final readonly class UpdateOrganizationRequest
{
    public function __construct(
        public int $id,
        public ?string $phoneNumber,
        public ?string $email,
        public ?string $language,
        public ?string $corporateName,
        public ?string $form,
        public ?string $type,
        public ?Address $address,
        public ?string $observations,
        public ?\SplFileInfo $logo,
        public ?int $holdBy,
    ) {
    }
}
