<?php

declare(strict_types=1);

namespace App\Booking\Mcp;

use App\Booking\MyBookingPageReader;
use App\Security\ToolAccess;
use App\Shared\CommandBus;
use App\Shared\Mcp\ToolInputs;
use App\Shared\Mcp\ToolResults;
use Symfony\Component\Clock\ClockInterface;

/**
 * What every tool that writes its member's booking page does, as the endpoint's controller does (ADR 0163). Any member
 * can write their own page. The tool checks the endpoint's input, dispatches its command, and answers the page as the
 * screen then reads it.
 */
final readonly class BookingPageWrites
{
    public function __construct(
        private ToolAccess $access,
        private ToolInputs $inputs,
        private CommandBus $commandBus,
        private ClockInterface $clock,
        private MyBookingPageReader $myBookingPageReader,
        private ToolResults $results,
    ) {
    }

    /**
     * @template TInput of object
     *
     * @param TInput                                               $input    the endpoint's input
     * @param \Closure(TInput, string, \DateTimeImmutable): object $command  builds the endpoint's command from its input, for the member, at this moment
     * @param ?string                                              $timeZone the time zone of the answered page, UTC by default
     *
     * @return array<string, mixed>
     */
    public function write(object $input, \Closure $command, ?string $timeZone = null): array
    {
        $this->access->denyUnlessGranted('ROLE_USER');
        $memberId = $this->access->member()->memberId();
        $this->commandBus->dispatch($command($this->inputs->valid($input), $memberId, $this->clock->now()));

        return $this->answered($memberId, $timeZone);
    }

    /**
     * Does the same as write(), for an endpoint without an input.
     *
     * @param \Closure(string, \DateTimeImmutable): object $command  builds the endpoint's command for the member, at this moment
     * @param ?string                                      $timeZone the time zone of the answered page, UTC by default
     *
     * @return array<string, mixed>
     */
    public function change(\Closure $command, ?string $timeZone = null): array
    {
        $this->access->denyUnlessGranted('ROLE_USER');
        $memberId = $this->access->member()->memberId();
        $this->commandBus->dispatch($command($memberId, $this->clock->now()));

        return $this->answered($memberId, $timeZone);
    }

    /**
     * @return array<string, mixed> the member's booking page as the screen then reads it
     */
    private function answered(string $memberId, ?string $timeZone): array
    {
        return $this->results->of($this->myBookingPageReader->read($memberId, $timeZone));
    }
}
