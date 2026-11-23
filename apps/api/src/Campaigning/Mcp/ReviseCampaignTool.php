<?php

declare(strict_types=1);

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
     * @param string       $id      the id of a draft campaign
     * @param string       $name    the campaign's name
     * @param string       $channel how each contact is contacted: email, call, sms or linkedIn
     * @param list<string> $tags    the tags its contacts carry; the channel's tag counts with them within the target's 5 tags
     * @param string       $message the message shown for each contact, with the variables {title}, {firstName}, {lastName}, {organization} and {jobTitle}
     * @param ?string      $subject an e-mail's subject
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'update-campaign', description: 'Change a draft campaign\'s name, channel, tags and message, all given at once. Needs the right to manage personal data. Returns the campaign.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: false))]
    public function __invoke(string $id, string $name, string $channel, array $tags, string $message, ?string $subject = null): array
    {
        return $this->writes->write(
            $id,
            new CampaignInput($name, $channel, $tags, $message, $subject),
            static fn (CampaignInput $input, CampaignSheet $campaign, string $memberId, \DateTimeImmutable $now): ReviseCampaign => $input->revisionOf($memberId, $campaign->id, $now),
        );
    }
}
