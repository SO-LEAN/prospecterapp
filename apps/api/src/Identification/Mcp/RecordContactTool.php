<?php

declare(strict_types=1);

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
 * Records a contact and the organization they work for in one call, and finds what Prospecter already knows
 * (ADR 0177). Like `identify`, it has no endpoint, because no screen needs it.
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
            'title' => ['type' => 'string', 'enum' => ['ms', 'mr', 'x'], 'description' => 'How to address them: ms, mr, or x when unknown.'],
            'jobTitle' => self::TEXT,
            'email' => self::TEXT,
            'phoneNumber' => self::TEXT + ['description' => 'Their number, in the international format (+33 6 12 34 56 78) or the French national format.'],
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
     * @param RecordedOrganization $organization    the organization they work for, as you see it, each part optional
     * @param ?string              $organizationId  the organization your member picked among the candidates to confirm; it wins over `newOrganization`
     * @param bool                 $newOrganization true when your member said it is none of the candidates to confirm
     * @param ?string              $personId        the contact your member picked among the candidates to confirm; it wins over `newPerson`
     * @param bool                 $newPerson       true when your member said they are none of the candidates to confirm
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'record-contact', description: 'Records a person and the organization they work for, in one call. Prospecter finds them by their keys, as `identify` does. It creates what it does not know. It fills empty fields and never changes what is already logged. It then attaches the contact to the organization. A second identical call creates nothing more. For each of the two, the answer is `created`, `known` or `toConfirm`. `known` lists the fields completed and the fields you gave with a value other than the one logged. `toConfirm` lists the candidates, and nothing is written: ask your member, then call again with the one they picked, or saying it is someone else. It needs the right to manage personal data.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: false))]
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
