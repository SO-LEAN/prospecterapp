<?php

namespace App\SignIn\Command;

use App\Demo\DemoCredentials;
use App\SignIn\UserPool;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Recreates the user pool empty, with the members of the demonstration if asked (`make db`).
 */
#[AsCommand(name: 'app:user-pool:recreate', description: 'Drop and create the Cognito user pool and its client (destroys the users)')]
final readonly class RecreateUserPoolCommand
{
    public function __construct(private UserPool $pool, private DemoCredentials $demoCredentials)
    {
    }

    public function __invoke(SymfonyStyle $io, #[Option(description: 'Register the members of the demonstration')] bool $demo = false): int
    {
        $this->pool->recreate();

        if ($demo) {
            $this->demoCredentials->register();
        }

        $io->success(sprintf('User pool %s (%s) recreated%s.', $this->pool->name, $this->pool->id(), $demo ? ' with the members of the demonstration' : ''));

        return Command::SUCCESS;
    }
}
