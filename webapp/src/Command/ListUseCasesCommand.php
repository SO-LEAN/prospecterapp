<?php

namespace App\Command;

use Solean\CleanProspecter\UseCase\UseCasesFacade;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:use-cases', description: 'List use cases', help: 'This command allows you to list recorded use cases.')]
class ListUseCasesCommand extends Command
{
    public function __construct(private readonly UseCasesFacade $useCasesFacade)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $usesCases = $this->useCasesFacade->getUseCases();
        sort($usesCases, SORT_NATURAL);
        $count = count($usesCases);

        $output->writeln($this->comment((string) $count, ' use case(s) registered'));

        foreach ($usesCases as $key => $useCase) {
            $output->writeln($this->inform((string) ($key + 1), '/', (string) $count, ' : ', (string) $useCase));
        }

        return Command::SUCCESS;
    }

    private function comment(string ...$text): string
    {
        return sprintf('<comment>%s</comment>', implode('', $text));
    }

    private function inform(string ...$text): string
    {
        return sprintf('<info>%s</info>', implode('', $text));
    }
}
