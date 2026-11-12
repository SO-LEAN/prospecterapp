<?php

namespace Tests\App\Keys;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\App\Base\ApiTestCase;

/**
 * The keys the tables derive from the aggregates are written again by a command named after what it does, as many
 * times as needed.
 */
class RebuildKeysCommandTest extends ApiTestCase
{
    public function testTheKeysOfEveryAggregateAreWrittenAgain(): void
    {
        $tester = new CommandTester(new Application(self::$kernel)->find('app:keys:rebuild'));

        $this->assertSame(Command::SUCCESS, $tester->execute([]));
        $this->assertMatchesRegularExpression('/Wrote the keys of \d+ organizations, \d+ persons, \d+ exchanges and \d+ accounts\./', $tester->getDisplay());
        $this->assertSame(Command::SUCCESS, $tester->execute([]));
    }

    public function testTheFormerNameIsGone(): void
    {
        $this->assertFalse(new Application(self::$kernel)->has('app:search:rebuild'));
    }
}
