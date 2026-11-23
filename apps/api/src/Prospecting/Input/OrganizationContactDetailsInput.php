<?php

declare(strict_types=1);

namespace App\Prospecting\Input;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * An organization's contact details: those a person has, plus its links to outside tools and its website.
 */
final readonly class OrganizationContactDetailsInput
{
    public function __construct(
        #[Assert\Email]
        #[Assert\Length(max: 255)]
        public ?string $email = null,
        #[Assert\Length(max: 255)]
        #[OA\Property(description: 'In the international format (+33 3 88 11 22 33) or in France\'s national format (03 88 11 22 33). It is kept in E.164 and refused when it is not a valid number', example: '03 88 11 22 33')]
        public ?string $phoneNumber = null,
        #[Assert\Country]
        public ?string $language = null,
        /** @var list<string> */
        #[Assert\All([new Assert\Type('string'), new Assert\Length(max: 255)])]
        #[OA\Property(description: 'The addresses of its pages on outside tools (a company on LinkedIn or Welcome to the Jungle, or any other site), five at most. Each one is recognized by its source and an identifier. A link is refused when it is not a web page\'s address, when it is a known tool\'s page that designates a person or that Prospecter does not recognize, or when another organization in the account has it', items: new OA\Items(type: 'string'), example: ['https://www.welcometothejungle.com/fr/companies/acme'])]
        public array $links = [],
        #[Assert\Length(max: 255)]
        #[OA\Property(description: 'A website\'s address, with or without its scheme. It is kept as its origin, with https when typed without a scheme. It is refused when it is not a site\'s address, or when another organization in the account has a website with the same domain (its host without www)', example: 'www.acme.fr')]
        public ?string $website = null,
    ) {
    }
}
