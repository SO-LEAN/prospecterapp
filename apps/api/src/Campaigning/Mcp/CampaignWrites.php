<?php

declare(strict_types=1);

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
 * What every tool that writes a campaign does, as its controller does (ADR 0163). It reads the route's campaign. It
 * refuses the member unless they are a prospector of its team with the right to manage personal data. Then it checks
 * the endpoint's input, dispatches its command, and returns the campaign as the screen then reads it.
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
     * @param TInput                                                              $input   the endpoint's input
     * @param \Closure(TInput, CampaignSheet, string, \DateTimeImmutable): object $command the endpoint's command, built from its input, for the campaign, by the member, at this moment
     *
     * @return array<string, mixed>
     */
    public function write(string $id, object $input, \Closure $command): array
    {
        return $this->answered($this->dispatched($id, $input, $command));
    }

    /**
     * It does the same as write() for an endpoint without an input.
     *
     * @param \Closure(CampaignSheet, string, \DateTimeImmutable): object $command the endpoint's command, for the campaign, by the member, at this moment
     *
     * @return array<string, mixed>
     */
    public function change(string $id, \Closure $command): array
    {
        return $this->answered($this->changed($id, $command));
    }

    /**
     * Dispatches the command and returns nothing.
     *
     * @template TInput of object
     *
     * @param TInput                                                              $input
     * @param \Closure(TInput, CampaignSheet, string, \DateTimeImmutable): object $command
     */
    public function dispatched(string $id, object $input, \Closure $command): CampaignSheet
    {
        $campaign = $this->writable($id);
        $this->commandBus->dispatch($command($this->inputs->valid($input), $campaign, $this->access->member()->memberId(), $this->clock->now()));

        return $campaign;
    }

    /**
     * It does the same as dispatched() for an endpoint without an input.
     *
     * @param \Closure(CampaignSheet, string, \DateTimeImmutable): object $command
     */
    public function changed(string $id, \Closure $command): CampaignSheet
    {
        $campaign = $this->writable($id);
        $this->commandBus->dispatch($command($campaign, $this->access->member()->memberId(), $this->clock->now()));

        return $campaign;
    }

    /**
     * Reads the route's campaign. It refuses the member unless they are a prospector of its team with the right to
     * manage personal data.
     */
    private function writable(string $id): CampaignSheet
    {
        $campaign = $this->campaigns->read($this->access->member(), $id);
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $this->access->denyUnlessGranted(CampaignVoter::ACCESS, $campaign);
        $this->access->denyUnlessGranted(User::PERSONAL_DATA);

        return $campaign;
    }

    /**
     * @return array<string, mixed> the campaign as the screen then reads it
     */
    private function answered(CampaignSheet $campaign): array
    {
        return $this->results->of($this->campaignReader->read($this->access->member()->memberId(), $campaign->id));
    }
}
