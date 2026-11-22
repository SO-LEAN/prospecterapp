<?php

declare(strict_types=1);

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
     * @param TInput                                                       $input   the input of the endpoint
     * @param \Closure(TInput, string, string, \DateTimeImmutable): object $command the command of the endpoint, from its input, by the member, on their profile, at this moment
     *
     * @return array<string, mixed>
     */
    public function write(object $input, \Closure $command): array
    {
        $member = $this->writable();

        return $this->dispatched($member, $command($this->inputs->valid($input), $member->memberId(), $member->personId(), $this->clock->now()));
    }

    /**
     * It does the same as write() for an endpoint without an input.
     *
     * @param \Closure(string, string, \DateTimeImmutable): object $command the command of the endpoint, by the member, on their profile, at this moment
     *
     * @return array<string, mixed>
     */
    public function change(\Closure $command): array
    {
        $member = $this->writable();

        return $this->dispatched($member, $command($member->memberId(), $member->personId(), $this->clock->now()));
    }

    /**
     * The member, refused unless they may write their profile: checked before anything is downloaded for it.
     */
    public function writable(): User
    {
        $this->access->denyUnlessGranted('ROLE_USER');

        return $this->access->member();
    }

    /**
     * @return array<string, mixed> their account as the screen reads it once the command is dispatched
     */
    private function dispatched(User $member, object $command): array
    {
        $this->commandBus->dispatch($command);

        return $this->results->of($this->myAccountReader->read($member->memberId()));
    }
}
