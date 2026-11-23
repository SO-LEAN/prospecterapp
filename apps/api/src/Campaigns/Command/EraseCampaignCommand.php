<?php

declare(strict_types=1);

namespace App\Campaigns\Command;

use App\Shared\CommandBus;
use Prospecter\Core\Campaigns\Application\EraseCampaign\EraseCampaign;
use Prospecter\Core\SharedKernel\Application\Exception\UseCaseRefused;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Erases a campaign whatever its status, found by its id alone. The operations run it to clean up a test, or on a
 * person's request. Only the operations run it, because the application never deletes a started campaign.
 */
#[AsCommand(name: 'app:campaign:erase', description: 'Erase a campaign whatever its status, for the operations only')]
final readonly class EraseCampaignCommand
{
    public function __construct(private CommandBus $commands)
    {
    }

    public function __invoke(SymfonyStyle $io, #[Argument(description: 'The campaign\'s id')] string $id): int
    {
        try {
            $this->commands->dispatch(new EraseCampaign($id));
        } catch (UseCaseRefused $refusal) {
            $io->error($refusal->getMessage());

            return Command::FAILURE;
        }

        $io->success(sprintf('The campaign %s is erased.', $id));

        return Command::SUCCESS;
    }
}
