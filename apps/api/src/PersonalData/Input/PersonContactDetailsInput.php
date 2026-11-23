<?php

declare(strict_types=1);

namespace App\PersonalData\Input;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A person's contact details: an email, a phone number, a language, and their links to outside tools.
 */
final readonly class PersonContactDetailsInput
{
    public function __construct(
        #[Assert\Email]
        #[Assert\Length(max: 255)]
        public ?string $email = null,
        #[Assert\Length(max: 255)]
        #[OA\Property(description: 'In the international format (+33 3 88 11 22 33) or in the French national format (03 88 11 22 33). It is kept in E.164. An invalid number is refused.', example: '03 88 11 22 33')]
        public ?string $phoneNumber = null,
        #[Assert\Country]
        public ?string $language = null,
        /** @var list<string> */
        #[Assert\All([new Assert\Type('string'), new Assert\Length(max: 255)])]
        #[OA\Property(description: 'The addresses of their pages on outside tools (a LinkedIn or Malt profile, or any other site), five at most. Each link is recognized by its source and an identifier. A link is refused when it is not a web page address, when it is a page on a known tool that designates an organization or that the tool does not recognize, or when another contact of the account has or had it.', items: new OA\Items(type: 'string'), example: ['linkedin.com/in/ada-lovelace'])]
        public array $links = [],
    ) {
    }
}
