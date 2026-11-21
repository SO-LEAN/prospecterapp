<?php

namespace App\Me\Mcp;

use App\PersonalData\Input\PersonNameInput;
use App\Shared\Mcp\Adapts;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\PersonalData\Application\RenamePerson\RenamePerson;

/**
 * `PATCH /api/me/name`, for an AI (ADR 0163).
 */
#[Adapts('api_me_change_name')]
final readonly class ChangeMyNameTool
{
    public function __construct(private MyProfileWrites $writes)
    {
    }

    /**
     * @param ?string $firstName their first name
     * @param ?string $lastName  their last name
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'set-my-name', description: 'Change the first and last names of the member in their profile. Answers their account.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: false))]
    public function __invoke(?string $firstName = null, ?string $lastName = null): array
    {
        return $this->writes->write(
            new PersonNameInput($firstName, $lastName),
            static fn (PersonNameInput $input, string $memberId, string $personId, \DateTimeImmutable $now): RenamePerson => new RenamePerson($memberId, $personId, $input->firstName, $input->lastName, $now),
        );
    }
}
