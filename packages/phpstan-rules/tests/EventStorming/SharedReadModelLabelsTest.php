<?php

declare(strict_types=1);

namespace Tests\PHPStan\EventStorming;

use PHPUnit\Framework\TestCase;
use Prospecter\PHPStan\EventStorming\SharedReadModelLabels;

final class SharedReadModelLabelsTest extends TestCase
{
    public function testTheRefusalNamesEveryGroupOfQueries(): void
    {
        self::assertSame(
            "These queries give their views the same label:\nShop: ConsultCart, ConsultTill\nTill: ConsultHistory, ConsultTimeline",
            SharedReadModelLabels::named(['Shop: ConsultCart, ConsultTill', 'Till: ConsultHistory, ConsultTimeline'])->getMessage(),
        );
    }
}
