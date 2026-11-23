<?php

declare(strict_types=1);

namespace App\Shared;

use App\Shared\Persistence\ConditionFailed;
use App\Shared\Persistence\DynamoUnitOfWork;
use App\Shared\Persistence\RevisionRead;
use Prospecter\Core\SharedKernel\Application\Addition;
use Prospecter\Core\SharedKernel\Application\Exception\UseCaseRefused;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Sends a core command to its handler, synchronously. The bus throws a core refusal back unchanged, so the API
 * translates it like any other refusal (404, 409, 422). A transaction can cancel an addition because the data it read
 * changed in the meantime. The bus then runs the command again on the new data, three times at most, after a short
 * random wait (ADR 0181). A command that carries the revision its client read applies to that revision only (ADR 0187).
 */
final readonly class CommandBus
{
    private const int REPLAYS_OF_AN_ADDITION = 3;
    private const int LONGEST_WAIT_BEFORE_A_REPLAY_IN_MS = 100;

    public function __construct(private MessageBusInterface $commandBus, private ClockInterface $clock, private DynamoUnitOfWork $unitOfWork)
    {
    }

    public function dispatch(object $command, ?RevisionRead $read = null): void
    {
        if (null === $read) {
            $this->dispatchReplayingAnAddition($command);

            return;
        }

        $this->unitOfWork->holdTo($read);

        try {
            $this->dispatchReplayingAnAddition($command);
        } finally {
            $this->unitOfWork->release();
        }
    }

    private function dispatchReplayingAnAddition(object $command): void
    {
        for ($replay = 1;; ++$replay) {
            try {
                $this->dispatchOnce($command);

                return;
            } catch (ConditionFailed $conflict) {
                if (!$command instanceof Addition || $replay > self::REPLAYS_OF_AN_ADDITION) {
                    throw UseCaseRefused::because($conflict->asRefusal());
                }

                $this->clock->sleep(random_int(1, self::LONGEST_WAIT_BEFORE_A_REPLAY_IN_MS) / 1000);
            }
        }
    }

    private function dispatchOnce(object $command): void
    {
        try {
            $this->commandBus->dispatch($command);
        } catch (HandlerFailedException $failure) {
            throw $failure->getPrevious() ?? $failure;
        }
    }
}
