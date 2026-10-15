<?php

declare(strict_types=1);

namespace Prospecter\Core\UseCase\CreateOrganization;

use Prospecter\Core\Entity\Address;

final readonly class CreateOrganizationRequest
{
    public function __construct(
        public ?int $ownedBy,
        public ?string $phoneNumber,
        public ?string $email,
        public ?string $language,
        public ?string $corporateName,
        public ?string $type,
        public ?string $form,
        public ?Address $address,
        public ?string $observations,
        public ?\SplFileInfo $logo,
        public ?int $holdBy,
    ) {
    }
}
