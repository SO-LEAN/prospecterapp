<?php

namespace App\Campaigns\Command;

use App\Shared\CommandBus;
use Prospecter\Core\Campaigns\Application\EraseCampaign\EraseCampaign;
use Prospecter\Core\SharedKernel\Application\Exception\UseCaseRefused;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Erases a campaign whatever its status, known by its identity alone: the cleaning of a test, a request of a person.
 * Reserved to the operations, the application never deleting a started campaign.
 */
#[AsCommand(name: 'app:campaign:erase', description: 'Erase a campaign whatever its status, for the operations only')]
final readonly class EraseCampaignCommand
{
    public function __construct(private CommandBus $commands)
    {
    }

    public function __invoke(SymfonyStyle $io, #[Argument(description: 'The identity of the campaign')] string $id): int
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
