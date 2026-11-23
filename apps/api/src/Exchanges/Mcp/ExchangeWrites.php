<?php

declare(strict_types=1);

namespace App\Exchanges\Mcp;

use App\Exchanges\ExchangeReader;
use App\Exchanges\ExchangeResolver;
use App\Exchanges\ExchangeVoter;
use App\Prospecting\Persistence\ExchangeRevisions;
use App\Security\ToolAccess;
use App\Shared\CommandBus;
use App\Shared\Mcp\ToolInputs;
use App\Shared\Mcp\ToolResults;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ExchangeDetail;
use Symfony\Component\Clock\ClockInterface;

/**
 * What every tool that writes an exchange does, the way its controller does (ADR 0163). It reads the route's
 * exchange. It refuses the member unless they are a prospector with access to the exchange and hold the controller's
 * other attributes. It then checks the endpoint's input and dispatches its command. It answers the exchange as the
 * screen then reads it.
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
        private ExchangeRevisions $revisions,
    ) {
    }

    /**
     * @template TInput of object
     *
     * @param TInput                                                               $input   the endpoint's input
     * @param \Closure(TInput, ExchangeDetail, string, \DateTimeImmutable): object $command builds the endpoint's command from its input, for the exchange, by the member, at this moment
     *
     * @return array<string, mixed>
     */
    public function write(string $id, object $input, \Closure $command, string ...$attributes): array
    {
        return $this->answer($this->dispatched($id, $input, $command, ...$attributes)->id);
    }

    /**
     * It does the same as write() for an endpoint without an input.
     *
     * @param \Closure(ExchangeDetail, string, \DateTimeImmutable): object $command builds the endpoint's command for the exchange, by the member, at this moment
     *
     * @return array<string, mixed>
     */
    public function change(string $id, \Closure $command, string ...$attributes): array
    {
        return $this->answer($this->changed($id, $command, ...$attributes)->id);
    }

    /**
     * Like write(), but the correction applies only to the exchange's revision that the AI read, when it gives one
     * (ADR 0187).
     *
     * @template TInput of object
     *
     * @param TInput                                                               $input   the endpoint's input
     * @param \Closure(TInput, ExchangeDetail, string, \DateTimeImmutable): object $command builds the endpoint's command from its input, for the exchange, by the member, at this moment
     *
     * @return array<string, mixed>
     */
    public function writeOnRevision(string $id, ?int $revision, object $input, \Closure $command, string ...$attributes): array
    {
        $exchange = $this->writable($id, ...$attributes);
        $this->dispatch($exchange, $command($this->inputs->valid($input), $exchange, $this->access->member()->memberId(), $this->clock->now()), $revision);

        return $this->answer($exchange->id);
    }

    /**
     * The exchange as the screen then reads it. After a move, it offers to move the exchange back to the moment it was
     * moved from, while that moment is still in the future.
     *
     * @return array<string, mixed>
     */
    public function answer(string $id, ?\DateTimeImmutable $movedFrom = null): array
    {
        return $this->results->of($this->exchangeReader->read($this->access->member()->memberId(), $id, $movedFrom));
    }

    /**
     * Dispatches the command and answers nothing to the AI.
     *
     * @template TInput of object
     *
     * @param TInput                                                               $input
     * @param \Closure(TInput, ExchangeDetail, string, \DateTimeImmutable): object $command
     *
     * @return ExchangeDetail the exchange as it was before
     */
    public function dispatched(string $id, object $input, \Closure $command, string ...$attributes): ExchangeDetail
    {
        $exchange = $this->writable($id, ...$attributes);
        $this->dispatch($exchange, $command($this->inputs->valid($input), $exchange, $this->access->member()->memberId(), $this->clock->now()));

        return $exchange;
    }

    /**
     * It does the same as dispatched() for an endpoint without an input.
     *
     * @param \Closure(ExchangeDetail, string, \DateTimeImmutable): object $command builds the endpoint's command for the exchange, by the member, at this moment
     *
     * @return ExchangeDetail the exchange as it was before
     */
    public function changed(string $id, \Closure $command, string ...$attributes): ExchangeDetail
    {
        $exchange = $this->writable($id, ...$attributes);
        $this->dispatch($exchange, $command($exchange, $this->access->member()->memberId(), $this->clock->now()));

        return $exchange;
    }

    private function dispatch(ExchangeDetail $exchange, object $command, ?int $revision = null): void
    {
        $this->commandBus->dispatch($command, $this->revisions->readAt($exchange->id, $revision));
    }

    /**
     * Reads the route's exchange. It refuses the member unless the endpoint allows them to write it.
     */
    public function writable(string $id, string ...$attributes): ExchangeDetail
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
