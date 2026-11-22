<?php

declare(strict_types=1);

namespace App\Prospecting\Mcp;

use App\Prospecting\Presenter\MeetingPlacePresenter;
use App\Security\ToolAccess;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Prospecting\Application\ProposeMeetingPlace\ProposeMeetingPlace;
use Prospecter\Core\SharedKernel\Application\QueryHandler;

/**
 * `GET /api/contacts/{id}/meeting-place`, for an AI (ADR 0163).
 */
#[Adapts('api_contacts_meeting_place')]
final readonly class ProposeMeetingPlaceTool
{
    public function __construct(private ToolAccess $access, private QueryHandler $proposeMeetingPlace, private ToolResults $results)
    {
    }

    /**
     * @param string $id the id of the contact
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'get-meeting-place', description: 'The place proposed to meet a contact: the address of their organization.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(string $id): array
    {
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $presenter = new MeetingPlacePresenter();
        $this->proposeMeetingPlace->__invoke(new ProposeMeetingPlace($this->access->member()->memberId(), $id), $presenter);

        return $this->results->of($presenter->resource);
    }
}
