<?php

namespace App\Prospecting\Mcp;

use App\Prospecting\Presenter\MeetingPlacePresenter;
use App\Security\ToolAccess;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Prospecting\Application\ProposeMeetingPlace\ProposeMeetingPlace;
use Prospecter\Core\SharedKernel\Application\QueryHandler;

/**
 * `GET /api/contacts/{id}/meeting-place`, for an AI (ADR 0163).
 */
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
    #[McpTool(name: 'proposer-lieu-de-rendez-vous', description: 'Le lieu proposé pour rencontrer un contact : l\'adresse de son organisation.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(string $id): array
    {
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $presenter = new MeetingPlacePresenter();
        ($this->proposeMeetingPlace)(new ProposeMeetingPlace($this->access->member()->memberId(), $id), $presenter);

        return $this->results->of($presenter->resource);
    }
}
