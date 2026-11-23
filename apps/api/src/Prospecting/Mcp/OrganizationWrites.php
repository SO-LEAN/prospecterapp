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
 * What every tool that writes an organization does in place of its controller (ADR 0163). It reads the route's
 * organization. It refuses the member unless they are a prospector with access to it and pass the controller's
 * other attributes. It then checks the endpoint's input, dispatches its command, and returns the organization as
 * the screen reads it afterwards.
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
     * @param TInput                                                                  $input   the endpoint's input
     * @param \Closure(TInput, OrganizationSheet, string, \DateTimeImmutable): object $command builds the endpoint's command from its input, for the organization, by the member, at this moment
     *
     * @return array<string, mixed>
     */
    public function write(string $id, object $input, \Closure $command, string ...$attributes): array
    {
        $organization = $this->writable($id, ...$attributes);

        return $this->written($organization, $command($this->inputs->valid($input), $organization, $this->access->member()->memberId(), $this->clock->now()));
    }

    /**
     * Does the same as write() for an endpoint without an input.
     *
     * @param \Closure(OrganizationSheet, string, \DateTimeImmutable): object $command builds the endpoint's command for the organization, by the member, at this moment
     *
     * @return array<string, mixed>
     */
    public function change(string $id, \Closure $command, string ...$attributes): array
    {
        $organization = $this->writable($id, ...$attributes);

        return $this->written($organization, $command($organization, $this->access->member()->memberId(), $this->clock->now()));
    }

    /**
     * Like write(), but the change applies only to the organization's revision that the AI read, when the AI gives
     * one (ADR 0187).
     *
     * @template TInput of object
     *
     * @param TInput                                                                  $input   the endpoint's input
     * @param \Closure(TInput, OrganizationSheet, string, \DateTimeImmutable): object $command builds the endpoint's command from its input, for the organization, by the member, at this moment
     *
     * @return array<string, mixed>
     */
    public function writeOnRevision(string $id, ?int $revision, object $input, \Closure $command, string ...$attributes): array
    {
        $organization = $this->writable($id, ...$attributes);

        return $this->written($organization, $command($this->inputs->valid($input), $organization, $this->access->member()->memberId(), $this->clock->now()), $revision);
    }

    /**
     * @return array<string, mixed> the organization as the screen reads it afterwards
     */
    private function written(OrganizationSheet $organization, object $command, ?int $revision = null): array
    {
        $this->commandBus->dispatch($command, $this->revisions->readAt($organization->id, $revision));

        return $this->results->of($this->organizationReader->read($organization->id));
    }

    /**
     * Reads the route's organization. Refuses the member unless the endpoint lets them write it.
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
