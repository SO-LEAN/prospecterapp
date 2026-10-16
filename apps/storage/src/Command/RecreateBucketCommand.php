<?php

namespace App\Command;

use App\Bucket;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Recreates the bucket of the files empty (`make db`).
 */
#[AsCommand(name: 'app:bucket:recreate', description: 'Drop and create the S3 bucket of the files (destroys them)')]
final readonly class RecreateBucketCommand
{
    public function __construct(private Bucket $bucket)
    {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $this->bucket->recreate();
        $io->success(sprintf('Bucket %s recreated.', $this->bucket->name));

        return Command::SUCCESS;
    }
}
