<?php

declare(strict_types=1);

namespace Tests\Core\Application;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\SharedKernel\Application\Exception\ErrorType;
use Prospecter\Core\SharedKernel\Application\Exception\UseCaseRefused;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Application\RefusalTranslatingHandler;
use Prospecter\Core\SharedKernel\Domain\Exception\NotAllowed;

class RefusalTranslatingHandlerTest extends TestCase
{
    public function testTheAnswerOfTheHandlerReachesTheOutputPortOfItsClient(): void
    {
        $output = new \ArrayObject();

        $answer = static function (\ArrayObject $output): void {
            $output->append('answer to the query');
        };

        new RefusalTranslatingHandler($this->handlerDoing($answer))(new \stdClass(), $output);

        $this->assertSame(['answer to the query'], $output->getArrayCopy());
    }

    public function testARefusalOfTheDomainIsTranslated(): void
    {
        $refusal = NotAllowed::because('Only the person may change it');

        try {
            new RefusalTranslatingHandler($this->handlerDoing(static fn () => throw $refusal))(new \stdClass(), new \ArrayObject());
            $this->fail('The refusal was expected');
        } catch (UseCaseRefused $translated) {
            $this->assertSame(ErrorType::NotAllowed, $translated->type);
            $this->assertSame($refusal, $translated->getPrevious());
        }
    }

    public function testAFailureOfTheInfrastructureGoesThroughUntouched(): void
    {
        $failure = new \RuntimeException('The database is gone');

        $this->expectExceptionObject($failure);

        new RefusalTranslatingHandler($this->handlerDoing(static fn () => throw $failure))(new \stdClass(), new \ArrayObject());
    }

    /**
     * @param \Closure(\ArrayObject<int, string>): mixed $work
     */
    private function handlerDoing(\Closure $work): QueryHandler
    {
        return new readonly class($work) implements QueryHandler {
            /**
             * @param \Closure(\ArrayObject<int, string>): mixed $work
             */
            public function __construct(private \Closure $work)
            {
            }

            /**
             * @param \ArrayObject<int, string> $output
             */
            public function __invoke(\stdClass $query, \ArrayObject $output): void
            {
                ($this->work)($output);
            }
        };
    }
}
