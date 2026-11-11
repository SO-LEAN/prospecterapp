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
 * it, then forgets it, a batch at a time: an event refused is given again once what refused it is corrected and
 * deployed; delivered twice, it is applied once (the inbox keeps an event 15 days, longer than the queue, 14 days).
 * A message keeping no invocation is left in the queue, hidden while the redrive lasts.
 */
#[AsCommand(name: 'app:integration:redrive', description: 'Give the worker again the events of the queue of the failures')]
final readonly class RedriveIntegrationFailuresCommand
{
    public function __construct(private IntegrationFailures $failures, private IntegrationWorker $worker)
    {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $redriven = 0;
        $left = [];

        while ([] !== $received = $this->failures->next()) {
            foreach ($received as $failed) {
                if (null === $failed->invocation) {
                    $left[] = $failed;
                    continue;
                }
                $this->redrive($failed, $failed->invocation);
                ++$redriven;
            }
        }

        foreach ($left as $failed) {
            $io->warning(sprintf('The message %s keeps no invocation of the worker (%s): left in the queue', $failed->id, $failed->reason));
        }

        $io->success(sprintf('Gave the worker again %d events of the failures.', $redriven));

        return [] === $left ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * @param array<string, mixed> $invocation
     */
    private function redrive(FailedInvocation $failed, array $invocation): void
    {
        $this->worker->invoke($invocation);
        $this->failures->forget($failed);
    }
}
