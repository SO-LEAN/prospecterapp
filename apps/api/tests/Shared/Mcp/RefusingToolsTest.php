<?php

declare(strict_types=1);

namespace Tests\App\Shared\Mcp;

use App\Security\AccessProblems;
use App\Shared\Mcp\RefusingTools;
use App\Shared\RefusalProblems;
use App\Shared\ServiceUnavailable;
use App\Shared\Typed;
use App\Shared\UnavailabilityProblems;
use Mcp\Server\Capability\Registry\ElementReference;
use Mcp\Server\Capability\Registry\ReferenceHandlerInterface;
use Mcp\Server\Exception\ToolCallException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\Booking\Domain\Exception\BookingPageWithoutLink;
use Prospecter\Core\SharedKernel\Application\Exception\UseCaseRefused;
use Psr\Log\LogLevel;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Tests\App\Double\InMemoryLogger;

/**
 * A core refusal answers the AI the problem its endpoint would answer. This proves the MCP translation once for every
 * tool. A service out of reach answers the AI its problem, its cause logged as the API logs it for the SPA.
 */
#[Group('unit')]
class RefusingToolsTest extends TestCase
{
    public function testAServiceOutOfReachIsLoggedWithItsCause(): void
    {
        $cause = new \RuntimeException('HTTP 503 returned');
        $logger = new InMemoryLogger();

        try {
            $this->tools(ServiceUnavailable::privateFiles($cause), $logger)->handle(new ElementReference(static fn (): null => null), []);
            $this->fail('The tool answered');
        } catch (ToolCallException) {
            $this->assertSame([[LogLevel::ERROR, $cause]], array_map(static fn (array $record): array => [$record['level'], Typed::object($record['context']['exception'], \Throwable::class)->getPrevious()], $logger->records));
        }
    }

    public function testACoreRefusalAnswersTheAiItsEndpointsProblem(): void
    {
        try {
            $this->tools(UseCaseRefused::because(BookingPageWithoutLink::doesNotChangeIt()), new InMemoryLogger())->handle(new ElementReference(static fn (): null => null), []);
            $this->fail('The tool answered');
        } catch (ToolCallException $error) {
            $problem = Typed::document(json_decode($error->getMessage(), true));
            $this->assertSame([409, 'bookingPageWithoutLink'], [$problem['status'], $problem['reason']]);
        }
    }

    private function tools(\Throwable $failure, InMemoryLogger $logger): RefusingTools
    {
        $failing = new readonly class($failure) implements ReferenceHandlerInterface {
            public function __construct(private \Throwable $failure)
            {
            }

            public function handle(ElementReference $reference, array $arguments): mixed
            {
                throw $this->failure;
            }
        };

        return new RefusingTools($failing, new AccessProblems(), new RefusalProblems(), new UnavailabilityProblems(), new Serializer([new ObjectNormalizer()]), $logger);
    }
}
