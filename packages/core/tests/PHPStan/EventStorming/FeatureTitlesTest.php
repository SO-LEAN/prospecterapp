<?php

declare(strict_types=1);

namespace Tests\Core\PHPStan\EventStorming;

use PHPUnit\Framework\TestCase;
use Prospecter\PHPStan\EventStorming\FeatureTitles;

final class FeatureTitlesTest extends TestCase
{
    private const string FEATURES = __DIR__.'/../../../../phpstan-rules/fixtures/event-storming/features';

    public function testEachUseCaseTaggedOnAFeatureTakesItsTitle(): void
    {
        self::assertSame(
            ['OpenBooking' => 'Activer et couper ma prise de rendez-vous', 'CloseBooking' => 'Activer et couper ma prise de rendez-vous'],
            new FeatureTitles(self::FEATURES)->byUseCase(),
        );
    }
}
