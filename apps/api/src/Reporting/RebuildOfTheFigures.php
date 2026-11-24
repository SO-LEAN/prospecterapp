<?php

declare(strict_types=1);

namespace App\Reporting;

use App\Shared\Persistence\ConditionFailed;
use Prospecter\Core\Reporting\Application\RebuildFigures\RebuildFigures;
use Prospecter\Core\Reporting\Application\RebuildFigures\RebuildFiguresHandler;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Counts every account's figures again from the command line, outside the command bus: the handler's steps commit
 * their own units of work (ADR 0103).
 *
 * A worker's recording on the same figures can cancel a step. The rebuild then starts again, three times at most. Each
 * new attempt first abandons the version that the cancelled one was building, then empties it (ADR 0104, 0106).
 *
 * The first attempt and the retries may wait differently. A command that is the only one to write counts its first
 * attempt without waiting. A conflict shows that the worker records, so its retries wait for it (ADR 0209).
 */
final readonly class RebuildOfTheFigures
{
    private const int RETRIES_OF_A_CONFLICT = 3;

    public function __construct(private RebuildFiguresHandler $firstAttempt, private RebuildFiguresHandler $retry)
    {
    }

    public function run(SymfonyStyle $io): void
    {
        for ($attempt = 1;; ++$attempt) {
            try {
                ($this->handlerOf($attempt))(new RebuildFigures());

                return;
            } catch (ConditionFailed $conflict) {
                if ($attempt > self::RETRIES_OF_A_CONFLICT) {
                    throw $conflict;
                }

                $io->warning('A fact recorded during the rebuild cancelled it. Starting it again, after waiting for the recordings in flight.');
            }
        }
    }

    private function handlerOf(int $attempt): RebuildFiguresHandler
    {
        return 1 === $attempt ? $this->firstAttempt : $this->retry;
    }
}
