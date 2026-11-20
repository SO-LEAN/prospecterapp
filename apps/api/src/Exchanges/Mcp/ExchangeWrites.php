<?php

namespace App\Exchanges\Mcp;

use App\Exchanges\ExchangeReader;
use App\Exchanges\ExchangeResolver;
use App\Exchanges\ExchangeVoter;
use App\Security\ToolAccess;
use App\Shared\CommandBus;
use App\Shared\Mcp\ToolInputs;
use App\Shared\Mcp\ToolResults;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ExchangeDetail;
use Symfony\Component\Clock\ClockInterface;

/**
 * What every tool writing an exchange does as its controller (ADR 0163): the exchange of the route read, the member
 * refused unless a prospector with access to it (and the other attributes of the controller), then the input of the
 * endpoint checked, its command dispatched, the exchange answered as the screen then reads it.
 */
final readonly class ExchangeWrites
{
    public function __construct(
        private ToolAccess $access,
        private ExchangeResolver $exchanges,
        private ToolInputs $inputs,
        private CommandBus $commandBus,
        private ClockInterface $clock,
        private ExchangeReader $exchangeReader,
        private ToolResults $results,
    ) {
    }

    /**
     * @template TInput of object
     *
     * @param TInput|null                                                               $input   the input of the endpoint, none for an endpoint without
     * @param \Closure(TInput|null, ExchangeDetail, string, \DateTimeImmutable): object $command the command of the endpoint, from its input, for the exchange, by the member, at this moment
     *
     * @return array<string, mixed>
     */
    public function write(string $id, ?object $input, \Closure $command, string ...$attributes): array
    {
        return $this->answer($this->dispatched($id, $input, $command, ...$attributes)->id);
    }

    /**
     * The exchange as the screen then reads it, offered to move it back to the moment it was moved from while to come.
     *
     * @return array<string, mixed>
     */
    public function answer(string $id, ?\DateTimeImmutable $movedFrom = null): array
    {
        return $this->results->of($this->exchangeReader->read($this->access->member()->memberId(), $id, $movedFrom));
    }

    /**
     * The command dispatched, nothing answered.
     *
     * @template TInput of object
     *
     * @param TInput|null                                                               $input
     * @param \Closure(TInput|null, ExchangeDetail, string, \DateTimeImmutable): object $command
     *
     * @return ExchangeDetail the exchange as it was before
     */
    public function dispatched(string $id, ?object $input, \Closure $command, string ...$attributes): ExchangeDetail
    {
        $exchange = $this->writable($id, ...$attributes);
        $this->commandBus->dispatch($command(null === $input ? null : $this->inputs->valid($input), $exchange, $this->access->member()->memberId(), $this->clock->now()));

        return $exchange;
    }

    /**
     * The exchange of the route, the member refused unless they may write it as its endpoint allows.
     */
    private function writable(string $id, string ...$attributes): ExchangeDetail
    {
        $exchange = $this->exchanges->read($this->access->member(), $id);
        $this->access->denyUnlessGranted('ROLE_PROSPECTOR');
        $this->access->denyUnlessGranted(ExchangeVoter::ACCESS, $exchange);
        foreach ($attributes as $attribute) {
            $this->access->denyUnlessGranted($attribute);
        }

        return $exchange;
    }
}
