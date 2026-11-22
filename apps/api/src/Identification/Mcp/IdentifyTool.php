<?php

declare(strict_types=1);

namespace App\Identification\Mcp;

use App\Identification\IdentificationReader;
use App\Security\ToolAccess;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Mcp\Server\Capability\Attribute\Schema;

/**
 * Which persons and organizations Prospecter knows, before an AI creates any (ADR 0173): no endpoint, the screens
 * not asking it yet, as `get-me`.
 *
 * @phpstan-import-type PersonCluesArray from IdentificationReader
 * @phpstan-import-type OrganizationCluesArray from IdentificationReader
 */
final readonly class IdentifyTool
{
    /**
     * What one call identifies at most, of each.
     */
    private const int AT_MOST = 100;

    private const array TEXT = ['type' => 'string'];
    private const array TEXTS = ['type' => 'array', 'items' => self::TEXT];

    private const array PERSON = [
        'type' => 'object',
        'properties' => [
            'firstName' => self::TEXT,
            'lastName' => self::TEXT,
            'links' => self::TEXTS + ['description' => 'The addresses of their pages on any tool: LinkedIn, Malt, Welcome to the Jungle or any other site.'],
            'emails' => self::TEXTS,
            'phoneNumbers' => self::TEXTS + ['description' => 'Their numbers, international (+33 6 12 34 56 78) or national in France; only a mobile identifies them.'],
            'organization' => ['type' => 'integer', 'minimum' => 0, 'description' => 'The position, from 0, in `organizations` of the organization they work for: its homonyms come first among the candidates.'],
        ],
        'additionalProperties' => false,
    ];

    private const array ORGANIZATION = [
        'type' => 'object',
        'properties' => [
            'name' => self::TEXT,
            'siret' => self::TEXT,
            'links' => self::TEXTS + ['description' => 'The addresses of its pages on any tool: LinkedIn, Welcome to the Jungle or any other site.'],
            'website' => self::TEXT,
            'emails' => self::TEXTS + ['description' => 'Its addresses, or those of persons working there: the domain of an address designates the organization of that website, but a public mailbox.'],
            'city' => self::TEXT + ['description' => 'Its city: the organizations named alike there come first among the candidates.'],
        ],
        'additionalProperties' => false,
    ];

    public function __construct(private ToolAccess $access, private IdentificationReader $identifications, private ToolResults $results)
    {
    }

    /**
     * @param list<PersonCluesArray>       $persons       the persons as you see them, each clue optional
     * @param list<OrganizationCluesArray> $organizations the organizations as you see them, each clue optional
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'identify', description: 'Before creating persons or organizations, which ones Prospecter knows: each is known (its id), new, or to confirm among candidates (ask your member), in the order given; nothing is written. A person is identified by a link, an email or a mobile, then by their first and last names (by their names only without the right to manage the personal data); an organization by its SIRET, a link, its website, its email or the domain of an email, then by its name.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(
        #[Schema(definition: ['type' => 'array', 'items' => self::PERSON, 'maxItems' => self::AT_MOST])]
        array $persons = [],
        #[Schema(definition: ['type' => 'array', 'items' => self::ORGANIZATION, 'maxItems' => self::AT_MOST])]
        array $organizations = [],
    ): array {
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');

        return $this->results->of($this->identifications->identify($this->access->member()->memberId(), $persons, $organizations));
    }
}
