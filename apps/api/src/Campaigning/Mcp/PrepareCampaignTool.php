<?php

namespace App\Campaigning\Mcp;

use App\Campaigning\CampaignReader;
use App\Campaigning\Input\CampaignInput;
use App\Security\ToolAccess;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Identities;
use App\Shared\Mcp\Adapts;
use App\Shared\Mcp\ToolInputs;
use App\Shared\Mcp\ToolResults;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\Capability\Attribute\McpTool;
use Symfony\Component\Clock\ClockInterface;

/**
 * `POST /api/campaigns`, for an AI (ADR 0163).
 */
#[Adapts('api_campaigns_prepare')]
final readonly class PrepareCampaignTool
{
    public function __construct(
        private ToolAccess $access,
        private ToolInputs $inputs,
        private Identities $identities,
        private CommandBus $commandBus,
        private ClockInterface $clock,
        private CampaignReader $campaignReader,
        private ToolResults $results,
    ) {
    }

    /**
     * @param string       $name    its name
     * @param string       $channel how each contact is contacted: email, call, sms or linkedIn
     * @param list<string> $tags    the tags its contacts carry, the tag of the channel counting with them in the 5 tags of the target
     * @param string       $message the message reminded for each contact, its variables {civilité}, {prénom}, {nom}, {organisation} and {fonction}
     * @param ?string      $subject the subject of an e-mail
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'create-campaign', description: 'Prepare a campaign of the team, as a draft targeting the contacts carrying its tags whom its channel reaches; needs the right to manage the personal data. Answers the campaign.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: false, openWorldHint: false))]
    public function __invoke(string $name, string $channel, array $tags, string $message, ?string $subject = null): array
    {
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $this->access->denyUnlessGranted(User::PERSONAL_DATA);
        $input = $this->inputs->valid(new CampaignInput($name, $channel, $tags, $message, $subject));
        $memberId = $this->access->member()->memberId();
        $id = $this->identities->next();
        $this->commandBus->dispatch($input->preparationOf($memberId, $id, $this->clock->now()));

        return $this->results->of($this->campaignReader->read($memberId, $id));
    }
}
