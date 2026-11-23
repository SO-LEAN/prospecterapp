<?php

declare(strict_types=1);

namespace App\Contacts\Mcp;

use App\Contacts\ContactReader;
use App\PersonalData\Input\JobTitleInput;
use App\PersonalData\Persistence\PersonRevisions;
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
        private PersonRevisions $revisions,
    ) {
    }

    /**
     * @param string  $id       the contact's id
     * @param ?string $jobTitle the contact's job in their organization. An empty value removes it
     * @param ?int    $revision the contact's `revision` as you read it. If it changed since, the change is refused (changedMeanwhile) and you read the contact again
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'set-contact-job-title', description: 'Change a contact\'s job title in their organization. It needs the right to manage personal data. Returns the contact.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: false))]
    public function __invoke(string $id, ?string $jobTitle = null, ?int $revision = null): array
    {
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $input = $this->inputs->valid(new JobTitleInput($jobTitle));
        $memberId = $this->access->member()->memberId();
        $this->commandBus->dispatch(new ChangeJobTitle(readerId: $memberId, id: $id, jobTitle: $input->jobTitle, executedAt: $this->clock->now()), $this->revisions->readAt($id, $revision));

        return $this->results->of($this->contactReader->read($memberId, $id));
    }
}
