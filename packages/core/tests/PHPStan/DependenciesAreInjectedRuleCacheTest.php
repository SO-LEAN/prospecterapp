<?php

declare(strict_types=1);

namespace Tests\Core\PHPStan;

use Prospecter\PHPStan\DependenciesAreInjectedRule;

/**
 * Whether the rule refuses a file depends on the dependencies of the class it builds, classes that the file does not
 * name.
 */
final class DependenciesAreInjectedRuleCacheTest extends ResultCacheTestCase
{
    public function testEachFileBuildingAClassIsAnalysedAgainOnceADependencyOfThatClassBecomesAService(): void
    {
        $this->giveTheTransport('value.php');
        $this->assertSame([], $this->analyse());

        $this->giveTheTransport('service.php');

        $this->assertSame(['Newsletter.php', 'Reminder.php'], $this->analyse());
    }

    protected function rule(): string
    {
        return DependenciesAreInjectedRule::class;
    }

    protected function fixtures(): string
    {
        return __DIR__.'/../../../phpstan-rules/fixtures/cache-of-services/';
    }

    private function giveTheTransport(string $fixture): void
    {
        $this->write('transports/'.$fixture, 'Transport.php');
    }
}
