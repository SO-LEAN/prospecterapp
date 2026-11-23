<?php

declare(strict_types=1);

namespace App\Shared\Resource;

use OpenApi\Attributes as OA;
use Prospecter\Core\SharedKernel\Application\ExternalLink;

/**
 * A person's or an organization's link to an outside tool.
 */
final readonly class ExternalLinkResource
{
    public function __construct(
        #[OA\Property(description: 'A known tool (linkedInProfile, linkedInCompany, malt, welcomeToTheJungle), or the address\'s host without www', example: 'welcomeToTheJungle')]
        public string $source,
        #[OA\Property(description: 'What the link points to on its source, in lower case. It identifies the link.', example: 'acme')]
        public string $identifier,
        #[OA\Property(description: 'The address as it was pasted, with https added when it was typed without a scheme. It opens the link.', example: 'https://www.welcometothejungle.com/fr/companies/acme')]
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
