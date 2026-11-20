<?php

namespace App\Campaigning\Mcp;

use App\Campaigning\CampaignResolver;
use App\Campaigning\CampaignVoter;
use App\Campaigning\RecipientsReader;
use App\Security\ToolAccess;
use App\Security\User;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;

/**
 * `GET /api/campaigns/{id}/recipients`, for an AI (ADR 0163).
 */
final readonly class ShowCampaignRecipientsTool
{
    public function __construct(
        private ToolAccess $access,
        private CampaignResolver $campaigns,
        private RecipientsReader $recipientsReader,
        private ToolResults $results,
    ) {
    }

    /**
     * @param string $id the id of the campaign
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'lire-destinataires-campagne', description: 'Les destinataires d\'une campagne figée ou lancée, chacun avec son statut (à traiter, traité, écarté, effacé) ; avec le droit de gérer les données personnelles.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function __invoke(string $id): array
    {
        $member = $this->access->member();
        $campaign = $this->campaigns->read($member, $id);
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $this->access->denyUnlessGranted(CampaignVoter::ACCESS, $campaign);
        $this->access->denyUnlessGranted(User::PERSONAL_DATA);

        return $this->results->of($this->recipientsReader->read($member->memberId(), $campaign));
    }
}
