<?php

namespace App\Shared\Resource;

use OpenApi\Attributes as OA;
use Prospecter\Core\SharedKernel\Application\ExternalLink;

/**
 * A link of a person or an organization to a tool outside.
 */
final readonly class ExternalLinkResource
{
    public function __construct(
        #[OA\Property(description: 'A known tool (linkedInProfile, linkedInCompany, malt, welcomeToTheJungle), or the host of the address without www', example: 'welcomeToTheJungle')]
        public string $source,
        #[OA\Property(description: 'What the link designates on its source, in lower case: what recognizes it', example: 'acme')]
        public string $identifier,
        #[OA\Property(description: 'The address as it was pasted, https added when typed without its scheme: what opens it', example: 'https://www.welcometothejungle.com/fr/companies/acme')]
        public string $address,
    ) {
    }

    /**
     * @param list<ExternalLink> $links
     *
     * @return list<self>
     */
    public static function allOf(array $links): array
    {
        return array_map(static fn (ExternalLink $link): self => new self($link->source, $link->identifier, $link->address), $links);
    }
}
