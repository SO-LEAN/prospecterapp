<?php

namespace App\Me\Mcp;

use App\Me\MyAccountReader;
use App\Security\ToolAccess;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Mcp\ToolInputs;
use App\Shared\Mcp\ToolResults;
use Symfony\Component\Clock\ClockInterface;

/**
 * What every tool writing the profile of its member does as its controller (ADR 0163): any member, their own
 * profile, the input of the endpoint checked, its command dispatched, their account answered as the screen then reads
 * it.
 */
final readonly class MyProfileWrites
{
    public function __construct(
        private ToolAccess $access,
        private ToolInputs $inputs,
        private CommandBus $commandBus,
        private ClockInterface $clock,
        private MyAccountReader $myAccountReader,
        private ToolResults $results,
    ) {
    }

    /**
     * @template TInput of object
     *
     * @param TInput|null                                                       $input   the input of the endpoint, none for an endpoint without
     * @param \Closure(TInput|null, string, string, \DateTimeImmutable): object $command the command of the endpoint, from its input, by the member, on their profile, at this moment
     *
     * @return array<string, mixed>
     */
    public function write(?object $input, \Closure $command): array
    {
        $member = $this->writable();
        $this->commandBus->dispatch($command(null === $input ? null : $this->inputs->valid($input), $member->memberId(), $member->personId(), $this->clock->now()));

        return $this->results->of($this->myAccountReader->read($member->memberId()));
    }

    /**
     * The member, refused unless they may write their profile: checked before anything is downloaded for it.
     */
    public function writable(): User
    {
        $this->access->denyUnlessGranted('ROLE_USER');

        return $this->access->member();
    }
}
