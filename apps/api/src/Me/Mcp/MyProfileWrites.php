<?php

declare(strict_types=1);

namespace App\Me\Mcp;

use App\Me\MyAccountComposition;
use App\Security\ToolAccess;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Mcp\ToolInputs;
use App\Shared\Mcp\ToolResults;
use Symfony\Component\Clock\ClockInterface;

/**
 * What every tool that writes its member's profile does, as its controller does (ADR 0163). Any member may write
 * their own profile. The tool checks the endpoint's input and dispatches its command. It then returns the member's
 * account as the screen reads it.
 */
final readonly class MyProfileWrites
{
    public function __construct(
        private ToolAccess $access,
        private ToolInputs $inputs,
        private CommandBus $commandBus,
        private ClockInterface $clock,
        private MyAccountComposition $myAccountComposition,
        private ToolResults $results,
    ) {
    }

    /**
     * @template TInput of object
     *
     * @param TInput                                                       $input   the endpoint's input
     * @param \Closure(TInput, string, string, \DateTimeImmutable): object $command builds the endpoint's command from its input, the member, their profile and the current time
     *
     * @return array<string, mixed>
     */
    public function write(object $input, \Closure $command): array
    {
        $member = $this->writable();

        return $this->dispatched($member, $command($this->inputs->valid($input), $member->memberId(), $member->personId(), $this->clock->now()));
    }

    /**
     * Does what write() does, for an endpoint without an input.
     *
     * @param \Closure(string, string, \DateTimeImmutable): object $command builds the endpoint's command from the member, their profile and the current time
     *
     * @return array<string, mixed>
     */
    public function change(\Closure $command): array
    {
        $member = $this->writable();

        return $this->dispatched($member, $command($member->memberId(), $member->personId(), $this->clock->now()));
    }

    /**
     * Returns the member, or refuses them when they may not write their profile. Call it before downloading anything
     * for the profile.
     */
    public function writable(): User
    {
        $this->access->denyUnlessGranted('ROLE_USER');

        return $this->access->member();
    }

    /**
     * @return array<string, mixed> their account as the screen reads it after the command
     */
    private function dispatched(User $member, object $command): array
    {
        $this->commandBus->dispatch($command);

        return $this->results->of($this->myAccountComposition->read($member->memberId()));
    }
}
