<?php

namespace App\Campaigning\Mcp;

use App\Campaigning\CampaignReader;
use App\Security\ToolAccess;
use App\Security\User;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;

/**
 * `GET /api/campaigns`, for an AI (ADR 0163).
 */
final readonly class ListCampaignsTool
{
    public function __construct(private ToolAccess $access, private CampaignReader $campaignReader, private ToolResults $results)
    {
    }

    /**
     * @return array<string, mixed>
     */
    #[McpTool(name: 'lister-campagnes', description: 'Les campagnes de l\'équipe, chacune avec sa cible comptée, son statut et sa progression ; avec le droit de gérer les données personnelles.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(): array
    {
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $this->access->denyUnlessGranted(User::PERSONAL_DATA);

        return $this->results->of($this->campaignReader->list($this->access->member()->memberId()));
    }
}
