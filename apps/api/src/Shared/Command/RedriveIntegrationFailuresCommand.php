<?php

declare(strict_types=1);

namespace App\Shared\Command;

use App\Shared\Messaging\FailedInvocation;
use App\Shared\Messaging\IntegrationFailures;
use App\Shared\Messaging\IntegrationWorker;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Gives each event in the failure queue to the worker again, then forgets it, one batch at a time. An event lands there
 * when Lambda gave it up or when the worker refused it. Give a refused event again once its cause is fixed and
 * deployed. An event delivered twice is applied once: the inbox keeps an event for 15 days, longer than the queue's 14
 * days. A message without an invocation stays in the queue, hidden while the redrive runs.
 */
#[AsCommand(name: 'app:integration:redrive', description: 'Give the failure queue\'s events to the worker again')]
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
