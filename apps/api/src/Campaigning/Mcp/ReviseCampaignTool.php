<?php

namespace App\Campaigning\Mcp;

use App\Campaigning\Input\CampaignInput;
use App\Shared\Mcp\Adapts;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Prospecter\Core\Campaigns\Application\CampaignSheet;
use Prospecter\Core\Campaigns\Application\ReviseCampaign\ReviseCampaign;

/**
 * `PATCH /api/campaigns/{id}`, for an AI (ADR 0163).
 */
#[Adapts('api_campaigns_revise')]
final readonly class ReviseCampaignTool
{
    public function __construct(private CampaignWrites $writes)
    {
    }

    /**
     * @param string       $id      the id of the campaign, a draft
     * @param string       $name    its name
     * @param string       $channel how each contact is contacted: email, call, sms or linkedIn
     * @param list<string> $tags    the tags its contacts carry, the tag of the channel counting with them in the 5 tags of the target
     * @param string       $message the message reminded for each contact, its variables {civilité}, {prénom}, {nom}, {organisation} and {fonction}
     * @param ?string      $subject the subject of an e-mail
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'reviser-campagne', description: 'Change the name, the channel, the tags and the message of a draft campaign, given whole; needs the right to manage the personal data. Answers the campaign.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: false))]
    public function __invoke(string $id, string $name, string $channel, array $tags, string $message, ?string $subject = null): array
    {
        return $this->writes->write(
            $id,
            new CampaignInput($name, $channel, $tags, $message, $subject),
            static fn (CampaignInput $input, CampaignSheet $campaign, string $memberId, \DateTimeImmutable $now): ReviseCampaign => $input->revisionOf($memberId, $campaign->id, $now),
        );
    }
}
