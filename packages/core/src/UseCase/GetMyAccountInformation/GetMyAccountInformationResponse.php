<?php

declare(strict_types=1);

namespace Prospecter\Core\UseCase\GetMyAccountInformation;

use Prospecter\Core\Entity\File;

final readonly class GetMyAccountInformationResponse
{
    public function __construct(
        public string $userName,
        public ?string $firstName,
        public ?string $lastName,
        public ?File $picture,
        public ?string $phoneNumber,
        public ?string $email,
        public ?string $language,
        public ?string $organizationCorporateName,
        public ?string $organizationForm,
        public ?File $organizationLogo,
    ) {
    }
}
