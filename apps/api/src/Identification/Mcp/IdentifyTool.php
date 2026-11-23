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
 * Tells which persons and organizations Prospecter knows, before an AI creates any (ADR 0173). Like `get-me`, it has
 * no endpoint, because no screen needs it yet.
 *
 * @phpstan-import-type PersonCluesArray from IdentificationReader
 * @phpstan-import-type OrganizationCluesArray from IdentificationReader
 */
final readonly class IdentifyTool
{
    /**
     * The most persons, and the most organizations, that one call identifies.
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
            'phoneNumbers' => self::TEXTS + ['description' => 'Their numbers, in the international format (+33 6 12 34 56 78) or the French national format. Only a mobile number identifies them.'],
            'organization' => ['type' => 'integer', 'minimum' => 0, 'description' => 'The position in `organizations` of the organization they work for, starting at 0. Namesakes in that organization come first among the candidates.'],
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
            'emails' => self::TEXTS + ['description' => 'Its addresses, or the addresses of people working there. The domain of an address designates the organization with that website, unless it is a public mailbox provider.'],
            'city' => self::TEXT + ['description' => 'Its city. Organizations with a similar name in that city come first among the candidates.'],
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
    #[McpTool(name: 'identify', description: 'Find which persons and organizations Prospecter already knows, before you create any. The answer follows the order given. Each one is known (with its id), new, or to confirm among candidates (ask your member). Nothing is written. A person is identified by a link, an email or a mobile number, then by their first and last names. Without the right to manage personal data, a person is identified by their names only. An organization is identified by its SIRET, a link, its website, its email or an email domain, then by its name.', annotations: new ToolAnnotations(readOnlyHint: true))]
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
