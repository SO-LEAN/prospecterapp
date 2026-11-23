<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\SharedKernel\Domain\Link\Designated;
use Prospecter\Core\SharedKernel\Domain\Link\ExternalReferences;
use Prospecter\Core\SharedKernel\Domain\Link\TooManyLinks;

class ExternalReferencesTest extends TestCase
{
    public function testTwoAddressesOfOneReferenceAreKeptOnce(): void
    {
        $references = ExternalReferences::designating(['https://www.linkedin.com/in/ada', 'fr.linkedin.com/in/Ada/'], Designated::Person);

        $this->assertSame(['https://www.linkedin.com/in/ada'], array_column($references->all(), 'address'));
    }

    public function testMoreThanFiveLinksAreRefused(): void
    {
        $this->expectExceptionObject(TooManyLinks::beyond(5));

        ExternalReferences::designating(array_map(static fn (int $site): string => sprintf('https://site%d.test/acme', $site), range(1, 6)), Designated::Organization);
    }

    public function testARefusalOfTooManyLinksNamesTheMost(): void
    {
        try {
            ExternalReferences::designating(array_map(static fn (int $site): string => sprintf('https://site%d.test/acme', $site), range(1, 6)), Designated::Organization);
            $this->fail('The links should have been refused');
        } catch (TooManyLinks $refusal) {
            $this->assertSame(['max' => '5'], $refusal->context);
        }
    }

    public function testFiveLinksAreKept(): void
    {
        $this->assertCount(5, ExternalReferences::designating(array_map(static fn (int $site): string => sprintf('https://site%d.test/acme', $site), range(1, 5)), Designated::Organization)->all());
    }
}
