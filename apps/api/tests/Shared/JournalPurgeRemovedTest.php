<?php

namespace Tests\App\Shared;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * The purge of the former activity journal ran on every instance: its command is gone (ADR 0096).
 */
final class JournalPurgeRemovedTest extends KernelTestCase
{
    public function testTheConsoleOffersNoPurgeOfTheJournal(): void
    {
        $this->assertFalse(new Application(self::bootKernel())->has('app:journal:purge'));
    }
}
