<?php

declare(strict_types=1);

namespace Tests\App\Exchanges;

use App\Exchanges\NotedOnceAnswers;
use App\Exchanges\Resource\NotingResource;
use Prospecter\Core\Prospecting\Domain\Exception\ExchangeNotedMeanwhile;
use Prospecter\Core\SharedKernel\Application\Exception\UseCaseRefused;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Two notings of the same exchange at once: the second, refused by the safety net of the registry, is answered as
 * already noted, without the exchange it does not know, never as an error (ADR 0166).
 */
class NotedOnceAnswersTest extends KernelTestCase
{
    public function testAnExchangeNotedMeanwhileIsAnsweredAlreadyNoted(): void
    {
        $answers = self::getContainer()->get(NotedOnceAnswers::class);

        $answer = $answers->of('01900000-0000-7000-8000-000000000001', '01900000-0000-7000-8000-0000000000e1', static function (): never {
            throw UseCaseRefused::because(ExchangeNotedMeanwhile::byAnother());
        });

        $this->assertSame(NotingResource::ALREADY_NOTED, $answer->status);
        $this->assertNull($answer->exchange);
    }
}
