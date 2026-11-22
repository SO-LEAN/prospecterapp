<?php

declare(strict_types=1);

namespace App\Prospecting\Mcp;

use App\Prospecting\OrganizationReader;
use App\Prospecting\OrganizationResolver;
use App\Prospecting\OrganizationVoter;
use App\Prospecting\Persistence\OrganizationRevisions;
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
        private OrganizationRevisions $revisions,
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

        return $this->written($organization, $command(null === $input ? null : $this->inputs->valid($input), $organization, $this->access->member()->memberId(), $this->clock->now()));
    }

    /**
     * Like write(), with the change held to the organization's revision the AI read, when it gives one (ADR 0187).
     *
     * @template TInput of object
     *
     * @param TInput                                                                  $input   the input of the endpoint
     * @param \Closure(TInput, OrganizationSheet, string, \DateTimeImmutable): object $command the command of the endpoint, from its input, for the organization, by the member, at this moment
     *
     * @return array<string, mixed>
     */
    public function writeOnRevision(string $id, ?int $revision, object $input, \Closure $command, string ...$attributes): array
    {
        $organization = $this->writable($id, ...$attributes);

        return $this->written($organization, $command($this->inputs->valid($input), $organization, $this->access->member()->memberId(), $this->clock->now()), $revision);
    }

    /**
     * @return array<string, mixed> the organization as the screen then reads it
     */
    private function written(OrganizationSheet $organization, object $command, ?int $revision = null): array
    {
        $this->commandBus->dispatch($command, $this->revisions->readAt($organization->id, $revision));

        return $this->results->of($this->organizationReader->read($organization->id));
    }

    /**
     * The organization of the route, the member refused unless they may write it as its endpoint allows.
     */
    public function writable(string $id, string ...$attributes): OrganizationSheet
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
