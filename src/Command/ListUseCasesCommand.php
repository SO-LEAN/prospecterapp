<?php

namespace App\Command;

use Solean\CleanProspecter\UseCase\UseCasesFacade;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListUseCasesCommand extends Command
{
    /**
     * @var UseCasesFacade
     */
    private $useCasesFacade;

    /**
     * @param UseCasesFacade $useCasesFacade
     */
    public function __construct(UseCasesFacade $useCasesFacade)
    {
        $this->useCasesFacade = $useCasesFacade;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:use-cases')
            ->setDescription('List use cases')
            ->setHelp('This command allows you to list recorded use cases.');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $usesCases = $this->useCasesFacade->getUseCases();
        sort($usesCases, SORT_NATURAL);
        $count = count($usesCases);

        $output->writeln($this->comment($count, ' use case(s) registered'));

        foreach ($usesCases as $key => $useCase) {
            $output->writeln($this->inform($key + 1, '/', $count, ' : ', $useCase));
        }
    }

    /**
     * @param string ...$text
     *
     * @return string
     */
    private function comment(string ...$text): string
    {
        return sprintf('<comment>%s</comment>', implode($text));
    }

    /**
     * @param string ...$text
     *
     * @return string
     */
    private function inform(string ...$text): string
    {
        return sprintf('<info>%s</info>', implode($text));
    }
}
