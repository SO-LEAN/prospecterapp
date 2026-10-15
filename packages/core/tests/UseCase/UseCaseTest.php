<?php

declare(strict_types=1);

namespace Tests\Core\UseCase;

use PHPUnit\Framework\TestCase;
use Tests\Core\UseCase\Stub\StubUseCase;

class UseCaseTest extends TestCase
{
    public function testAUseCaseSaysTheIntentItServes(): void
    {
        $this->assertSame('I want to stub use case', (string) new StubUseCase());
    }
}
