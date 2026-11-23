<?php

declare(strict_types=1);

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
     * @param string       $name     the campaign's name
     * @param string       $channel  how each contact is contacted: email, call, sms or linkedIn
     * @param list<string> $tags     the tags its contacts carry; the channel's tag counts with them within the target's 5 tags
     * @param string       $message  the message shown for each contact, with the variables {title}, {firstName}, {lastName}, {organization} and {jobTitle}
     * @param ?string      $subject  an e-mail's subject
     * @param ?int         $dailyCap the most recipients processed in a day, a guard where you stop to ask your member; null for no cap
     *
     * @return array<string, mixed>
     */
    #[McpTool(name: 'create-campaign', description: 'Prepare a team campaign as a draft. It targets the contacts that carry its tags and that its channel reaches. Needs the right to manage personal data. Returns the campaign.', annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: false, openWorldHint: false))]
    public function __invoke(string $name, string $channel, array $tags, string $message, ?string $subject = null, ?int $dailyCap = null): array
    {
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $this->access->denyUnlessGranted(User::PERSONAL_DATA);
        $input = $this->inputs->valid(new CampaignInput($name, $channel, $tags, $message, $subject, dailyCap: $dailyCap));
        $memberId = $this->access->member()->memberId();
        $id = $this->identities->next();
        $this->commandBus->dispatch($input->preparationOf($memberId, $id, $this->clock->now()));

        return $this->results->of($this->campaignReader->read($memberId, $id));
    }
}
