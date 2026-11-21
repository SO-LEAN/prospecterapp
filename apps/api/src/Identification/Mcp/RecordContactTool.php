<?php

namespace App\Identification\Mcp;

use App\Identification\ContactRecorder;
use App\Identification\Designation;
use App\Prospecting\OrganizationResolver;
use App\Prospecting\OrganizationVoter;
use App\Security\ToolAccess;
use App\Security\User;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Mcp\Server\Capability\Attribute\Schema;

/**
 * Records in one call a contact and the organization they work for, finding what Prospecter knows already (ADR 0177):
 * no endpoint, the screens not asking it, as `identify`.
 *
 * @phpstan-import-type RecordedPerson from ContactRecorder
 * @phpstan-import-type RecordedOrganization from ContactRecorder
 */
final readonly class RecordContactTool
{
    private const array TEXT = ['type' => 'string'];
    private const array TEXTS = ['type' => 'array', 'items' => self::TEXT];

    private const array PERSON = [
        'type' => 'object',
        'properties' => [
            'firstName' => self::TEXT,
            'lastName' => self::TEXT,
            'title' => ['type' => 'string', 'enum' => ['ms', 'mr', 'x'], 'description' => 'How one addresses them: ms, mr, or x when unknown.'],
            'jobTitle' => self::TEXT,
            'email' => self::TEXT,
            'phoneNumber' => self::TEXT + ['description' => 'Their number, international (+33 6 12 34 56 78) or national in France.'],
            'links' => self::TEXTS + ['description' => 'The addresses of their pages on any tool: LinkedIn, Malt or any other site.'],
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
            'email' => self::TEXT,
            'phoneNumber' => self::TEXT,
            'city' => self::TEXT,
        ],
        'additionalProperties' => false,
    ];

    public function __construct(private ToolAccess $access, private OrganizationResolver $organizations, private ContactRecorder $recorder, private ToolResults $results)
    {
    }

    /**
     * @param RecordedPerson       $person          the person as you see them, each part optional
     * @param RecordedOrganization $organization    the organization they work for as you see it, each part optional
     * @param ?string              $organizationId  the organization your member retained among those to confirm, which wins over `newOrganization`
     * @param bool                 $newOrganization true when your member said it is none of those to confirm
     * @param ?string              $personId        the contact your member retained among those to confirm, which wins over `newPerson`
     * @param bool                 $newPerson       true when your member said they are none of those to confirm
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'record-contact', description: 'Record a person and the organization they work for, in one call: Prospecter finds them by their keys (as `identify`), creates what it does not know, fills what is empty without changing what is noted, and attaches the contact to the organization; calling twice creates nothing more. Answers for each `created`, `known` with the fields completed and those you gave otherwise than noted, or `toConfirm` with candidates (nothing written: ask your member, then call again naming the one retained or saying it is another one). Needs the right to manage the personal data.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: false))]
    public function __invoke(
        #[Schema(definition: self::PERSON)]
        array $person,
        #[Schema(definition: self::ORGANIZATION)]
        array $organization,
        ?string $organizationId = null,
        bool $newOrganization = false,
        ?string $personId = null,
        bool $newPerson = false,
    ): array {
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $this->access->denyUnlessGranted(User::PERSONAL_DATA);

        if (null !== $organizationId) {
            $this->access->denyUnlessGranted(OrganizationVoter::ACCESS, $this->organizations->read($organizationId));
        }

        return $this->results->of($this->recorder->record($this->access->member()->memberId(), $person, $organization, Designation::of($organizationId, $newOrganization), Designation::of($personId, $newPerson)));
    }
}
