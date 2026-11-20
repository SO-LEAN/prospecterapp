<?php

namespace App\Prospecting\Mcp;

use App\Prospecting\OrganizationReader;
use App\Prospecting\OrganizationResolver;
use App\Prospecting\OrganizationVoter;
use App\Security\ToolAccess;
use App\Shared\CommandBus;
use App\Shared\Mcp\ToolInputs;
use App\Shared\Mcp\ToolResults;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Symfony\Component\Clock\ClockInterface;

/**
 * What every tool writing an organization does as its controller (ADR 0163): the organization of the route read,
 * the member refused unless a prospector with access to it (and the other attributes of the controller), then the
 * input of the endpoint checked, its command dispatched, the organization answered as the screen then reads it.
 */
final readonly class OrganizationWrites
{
    public function __construct(
        private ToolAccess $access,
        private OrganizationResolver $organizations,
        private ToolInputs $inputs,
        private CommandBus $commandBus,
        private ClockInterface $clock,
        private OrganizationReader $organizationReader,
        private ToolResults $results,
    ) {
    }

    /**
     * @template TInput of object
     *
     * @param TInput|null                                                                  $input   the input of the endpoint, none for an endpoint without
     * @param \Closure(TInput|null, OrganizationSheet, string, \DateTimeImmutable): object $command the command of the endpoint, from its input, for the organization, by the member, at this moment
     *
     * @return array<string, mixed>
     */
    public function write(string $id, ?object $input, \Closure $command, string ...$attributes): array
    {
        $organization = $this->writable($id, ...$attributes);
        $this->commandBus->dispatch($command(null === $input ? null : $this->inputs->valid($input), $organization, $this->access->member()->memberId(), $this->clock->now()));

        return $this->results->of($this->organizationReader->read($organization->id));
    }

    private function writable(string $id, string ...$attributes): OrganizationSheet
    {
        $organization = $this->organizations->read($id);
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $this->access->denyUnlessGranted(OrganizationVoter::ACCESS, $organization);
        foreach ($attributes as $attribute) {
            $this->access->denyUnlessGranted($attribute);
        }

        return $organization;
    }
}
