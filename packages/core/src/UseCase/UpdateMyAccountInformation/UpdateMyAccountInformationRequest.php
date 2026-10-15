<?php

declare(strict_types=1);

namespace Prospecter\Core\UseCase\UpdateMyAccountInformation;

/**
 * My whole profile and my organization's: what is left out is cleared, except the picture and the logo,
 * kept unless new ones are sent.
 */
final readonly class UpdateMyAccountInformationRequest
{
    public function __construct(
        public string $userName,
        public ?string $firstName,
        public ?string $lastName,
        public ?\SplFileInfo $picture,
        public ?string $phoneNumber,
        public ?string $email,
        public ?string $language,
        public ?string $organizationCorporateName,
        public ?string $organizationForm,
        public ?\SplFileInfo $organizationLogo,
    ) {
    }
}
