<?php

namespace App\Shared\Command;

use App\Shared\Messaging\FailedInvocation;
use App\Shared\Messaging\IntegrationFailures;
use App\Shared\Messaging\IntegrationWorker;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Gives the worker again each event of the queue of the failures, whether Lambda gave it up or the worker refused
 * it, then forgets it: an event refused is given again once what refused it is corrected and deployed; delivered
 * twice, it is applied once (the inbox keeps an event 15 days, longer than the queue, 14 days). A message keeping no
 * invocation is left in the queue.
 */
#[AsCommand(name: 'app:integration:redrive', description: 'Give the worker again the events of the queue of the failures')]
final readonly class RedriveIntegrationFailuresCommand
{
    public function __construct(private IntegrationFailures $failures, private IntegrationWorker $worker)
    {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $failed = $this->everyFailure();
        $redriven = array_filter($failed, static fn (FailedInvocation $message): bool => null !== $message->invocation);
        array_walk($redriven, $this->redrive(...));

        foreach (array_diff_key($failed, $redriven) as $left) {
            $io->warning(sprintf('The message %s keeps no invocation of the worker (%s): left in the queue', $left->id, $left->reason));
        }

        $io->success(sprintf('Gave the worker again %d events of the failures.', count($redriven)));

        return count($redriven) === count($failed) ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * Each message received is hidden from the next receive while the redrive lasts.
     *
     * @return list<FailedInvocation>
     */
    private function everyFailure(): array
    {
        $failed = [];

        while ([] !== $received = $this->failures->next()) {
            array_push($failed, ...$received);
        }

        return $failed;
    }

    private function redrive(FailedInvocation $failed): void
    {
        $this->worker->invoke($failed->invocation ?? []);
        $this->failures->forget($failed);
    }
}
