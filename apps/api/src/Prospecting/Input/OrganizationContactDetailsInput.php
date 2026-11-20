<?php

namespace App\Prospecting\Input;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The contact details of an organization: those of a person, its links to the tools outside and its website.
 */
final readonly class OrganizationContactDetailsInput
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
        #[OA\Property(description: 'The addresses of its pages on the tools outside (a company on LinkedIn or Welcome to the Jungle, any other site), five at most; each recognized by its source and an identifier, refused when it is no address of a web page, a page of a known tool designating a person or none it recognizes, or the one of another organization of the account', items: new OA\Items(type: 'string'), example: ['https://www.welcometothejungle.com/fr/companies/acme'])]
        public array $links = [],
        #[Assert\Length(max: 255)]
        #[OA\Property(description: 'The address of a site on the web, with or without its scheme; kept as its origin (https when typed without), refused when it is no address of a site, or when another organization of the account has a site of the same domain (its host without www)', example: 'www.acme.fr')]
        public ?string $website = null,
    ) {
    }
}
