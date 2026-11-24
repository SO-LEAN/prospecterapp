<?php

declare(strict_types=1);

namespace Tests\PHPStan\EventStorming;

use PHPUnit\Framework\TestCase;
use Prospecter\PHPStan\EventStorming\FeatureTitles;

final class FeatureTitlesTest extends TestCase
{
    private const string FEATURES = __DIR__.'/Fixtures/features';

    public function testEachUseCaseTaggedOnAFeatureTakesItsTitle(): void
    {
        self::assertSame(
            ['OpenBooking' => 'Activer et couper ma prise de rendez-vous', 'CloseBooking' => 'Activer et couper ma prise de rendez-vous'],
            new FeatureTitles(self::FEATURES)->byUseCase(),
        );
    }

    public function testAFeatureThatNamesNoUseCaseIsUntagged(): void
    {
        self::assertSame(['payer.feature'], new FeatureTitles(self::FEATURES)->untagged());
    }
}
