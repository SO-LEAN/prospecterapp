<?php

namespace App\Campaigning\Mcp;

use App\Campaigning\CampaignReader;
use App\Campaigning\CampaignResolver;
use App\Campaigning\CampaignVoter;
use App\Security\ToolAccess;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Mcp\ToolInputs;
use App\Shared\Mcp\ToolResults;
use Prospecter\Core\Campaigns\Application\CampaignSheet;
use Symfony\Component\Clock\ClockInterface;

/**
 * What every tool writing a campaign does as its controller (ADR 0163): the campaign of the route read, the member
 * refused unless a prospector of its team with the right to manage the personal data, then the input of the endpoint
 * checked, its command dispatched, the campaign answered as the screen then reads it.
 */
final readonly class CampaignWrites
{
    public function __construct(
        private ToolAccess $access,
        private CampaignResolver $campaigns,
        private ToolInputs $inputs,
        private CommandBus $commandBus,
        private ClockInterface $clock,
        private CampaignReader $campaignReader,
        private ToolResults $results,
    ) {
    }

    /**
     * @template TInput of object
     *
     * @param TInput|null                                                              $input   the input of the endpoint, none for an endpoint without
     * @param \Closure(TInput|null, CampaignSheet, string, \DateTimeImmutable): object $command the command of the endpoint, from its input, for the campaign, by the member, at this moment
     *
     * @return array<string, mixed>
     */
    public function write(string $id, ?object $input, \Closure $command): array
    {
        $campaign = $this->dispatched($id, $input, $command);

        return $this->results->of($this->campaignReader->read($this->access->member()->memberId(), $campaign->id));
    }

    /**
     * The command dispatched, nothing answered.
     *
     * @template TInput of object
     *
     * @param TInput|null                                                              $input
     * @param \Closure(TInput|null, CampaignSheet, string, \DateTimeImmutable): object $command
     */
    public function dispatched(string $id, ?object $input, \Closure $command): CampaignSheet
    {
        $member = $this->access->member();
        $campaign = $this->campaigns->read($member, $id);
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $this->access->denyUnlessGranted(CampaignVoter::ACCESS, $campaign);
        $this->access->denyUnlessGranted(User::PERSONAL_DATA);
        $this->commandBus->dispatch($command(null === $input ? null : $this->inputs->valid($input), $campaign, $member->memberId(), $this->clock->now()));

        return $campaign;
    }
}
