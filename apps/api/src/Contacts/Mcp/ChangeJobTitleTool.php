<?php

namespace App\Contacts\Mcp;

use App\Contacts\ContactReader;
use App\PersonalData\Input\JobTitleInput;
use App\Security\ToolAccess;
use App\Shared\CommandBus;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolInputs;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\PersonalData\Application\ChangeJobTitle\ChangeJobTitle;
use Symfony\Component\Clock\ClockInterface;

/**
 * `PATCH /api/contacts/{id}/job-title`, for an AI (ADR 0163).
 */
#[Adapts('api_contacts_change_job_title')]
final readonly class ChangeJobTitleTool
{
    public function __construct(
        private ToolAccess $access,
        private ToolInputs $inputs,
        private CommandBus $commandBus,
        private ClockInterface $clock,
        private ContactReader $contactReader,
        private ToolResults $results,
    ) {
    }

    /**
     * @param string  $id       the id of the contact
     * @param ?string $jobTitle what the contact does in their organization; none when empty
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'set-contact-job-title', description: 'Change what a contact does in their organization (their job title); needs the right to manage the personal data. Answers the contact.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: false))]
    public function __invoke(string $id, ?string $jobTitle = null): array
    {
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $input = $this->inputs->valid(new JobTitleInput($jobTitle));
        $memberId = $this->access->member()->memberId();
        $this->commandBus->dispatch(new ChangeJobTitle($memberId, $id, $input->jobTitle, $this->clock->now()));

        return $this->results->of($this->contactReader->read($memberId, $id));
    }
}
