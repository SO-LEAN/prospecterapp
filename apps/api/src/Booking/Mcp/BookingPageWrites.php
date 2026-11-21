<?php

namespace App\Booking\Mcp;

use App\Booking\MyBookingPageReader;
use App\Security\ToolAccess;
use App\Shared\CommandBus;
use App\Shared\Mcp\ToolInputs;
use App\Shared\Mcp\ToolResults;
use Symfony\Component\Clock\ClockInterface;

/**
 * What every tool writing the booking page of its member does as its controller (ADR 0163): any member, on their own
 * page, the input of the endpoint checked, its command dispatched, the page answered as the screen then reads it.
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
     * @param TInput|null                                               $input    the input of the endpoint, none for an endpoint without
     * @param \Closure(TInput|null, string, \DateTimeImmutable): object $command  the command of the endpoint, from its input, by the member, at this moment
     * @param ?string                                                   $timeZone the time zone the page is answered on; UTC otherwise
     *
     * @return array<string, mixed>
     */
    public function write(?object $input, \Closure $command, ?string $timeZone = null): array
    {
        $this->access->denyUnlessGranted('ROLE_USER');
        $memberId = $this->access->member()->memberId();
        $this->commandBus->dispatch($command(null === $input ? null : $this->inputs->valid($input), $memberId, $this->clock->now()));

        return $this->results->of($this->myBookingPageReader->read($memberId, $timeZone));
    }
}
