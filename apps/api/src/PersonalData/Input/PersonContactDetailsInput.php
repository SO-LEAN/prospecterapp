<?php

declare(strict_types=1);

namespace App\PersonalData\Input;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The contact details of a person: an email, a phone number, a language, and their links to the tools outside.
 */
final readonly class PersonContactDetailsInput
{
    public function __construct(
        #[Assert\Email]
        #[Assert\Length(max: 255)]
        public ?string $email = null,
        #[Assert\Length(max: 255)]
        #[OA\Property(description: 'In the international format (+33 3 88 11 22 33) or in the national format of France (03 88 11 22 33); kept in E.164, refused when it is no valid number', example: '03 88 11 22 33')]
        public ?string $phoneNumber = null,
        #[Assert\Country]
        public ?string $language = null,
        /** @var list<string> */
        #[Assert\All([new Assert\Type('string'), new Assert\Length(max: 255)])]
        #[OA\Property(description: 'The addresses of their pages on the tools outside (a profile on LinkedIn or Malt, any other site), five at most; each recognized by its source and an identifier, refused when it is no address of a web page, a page of a known tool designating an organization or none it recognizes, or one another contact of the account has or had', items: new OA\Items(type: 'string'), example: ['linkedin.com/in/ada-lovelace'])]
        public array $links = [],
    ) {
    }
}
