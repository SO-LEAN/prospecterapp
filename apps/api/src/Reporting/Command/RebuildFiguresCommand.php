<?php

namespace App\Reporting\Command;

use App\Shared\Persistence\ConditionFailed;
use Prospecter\Core\Reporting\Application\RebuildFigures\RebuildFigures;
use Prospecter\Core\Reporting\Application\RebuildFigures\RebuildFiguresHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Counts the figures of every account again, from the state of the other contexts, in a new version made the active
 * one (ADR 0103): once Reporting is put in service on an instance, then whenever its figures went wrong. Run from the
 * command line, out of the time limit of a function; its steps commit their own units, so it calls its handler
 * without the unit of work of a command. It writes only the table of Reporting, figures derived from the state.
 * A step cancelled by a recording of the worker on the same figures starts the rebuild again, from a version emptied
 * first, three times at most (ADR 0104).
 */
#[AsCommand(name: 'app:reporting:rebuild', description: 'Count the figures of every account again, from the state of the other contexts')]
final readonly class RebuildFiguresCommand
{
    private const int RETRIES_OF_A_CONFLICT = 3;

    public function __construct(private RebuildFiguresHandler $rebuildFigures)
    {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        for ($retry = 1;; ++$retry) {
            $io->comment('Waiting a minute for the facts being recorded, then counting every account again...');

            try {
                ($this->rebuildFigures)(new RebuildFigures());
                break;
            } catch (ConditionFailed $conflict) {
                if ($retry > self::RETRIES_OF_A_CONFLICT) {
                    throw $conflict;
                }

                $io->warning('A fact recorded meanwhile cancelled the rebuild: starting it again.');
            }
        }

        $io->success('The figures of every account are counted again.');

        return Command::SUCCESS;
    }
}
