<?php

namespace App\Command;

use App\Bucket;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Recreates the buckets of the files and of the private files empty (`make db`).
 */
#[AsCommand(name: 'app:bucket:recreate', description: 'Drop and create the S3 buckets of the files and of the private files (destroys them)')]
final readonly class RecreateBucketCommand
{
    public function __construct(private Bucket $bucket, private Bucket $privateBucket)
    {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        foreach ([$this->bucket, $this->privateBucket] as $bucket) {
            $bucket->recreate();
            $io->success(sprintf('Bucket %s recreated.', $bucket->name));
        }

        return Command::SUCCESS;
    }
}
