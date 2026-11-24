<?php

declare(strict_types=1);

namespace Tests\App\Shared;

use App\Shared\Typed;
use App\Shared\UseCaseExceptionListener;
use Prospecter\Core\Booking\Domain\Exception\BookingPageWithoutLink;
use Prospecter\Core\Connectors\Domain\Exception\NotTheHolder;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownUpload;
use Prospecter\Core\SharedKernel\Application\Exception\UseCaseRefused;
use Prospecter\Core\SharedKernel\Domain\Contact\InvalidPhoneNumber;
use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Tests\App\Base\ReadsResources;

/**
 * Each kind of the core's refusals is translated once for the whole API, here, and not by each endpoint's tests.
 */
final class UseCaseExceptionListenerTest extends KernelTestCase
{
    use ReadsResources;

    private const string AN_API_PATH = '/api/uploads';

    public function testAnUnknownResourceIsNotFound(): void
    {
        $response = $this->responseTo(UseCaseRefused::because(UnknownUpload::identifiedBy('an-upload')));

        $this->assertProblem(Response::HTTP_NOT_FOUND, ['title' => 'Not Found', 'status' => 404, 'reason' => 'unknownUpload', 'violations' => []], $response);
    }

    public function testARuleOnAFieldIsAViolationOfThatField(): void
    {
        $response = $this->responseTo(UseCaseRefused::because(InvalidPhoneNumber::of('12')));

        $this->assertProblem(Response::HTTP_UNPROCESSABLE_ENTITY, ['title' => 'Validation Failed', 'status' => 422, 'reason' => 'invalidPhoneNumber', 'violations' => [
            ['propertyPath' => 'phoneNumber', 'title' => 'Phone number "12" is not valid', 'reason' => 'invalidPhoneNumber', 'context' => ['phoneNumber' => '12']],
        ]], $response);
    }

    public function testARuleOnTheWholeRequestIsAViolationOfNoField(): void
    {
        $response = $this->responseTo(UseCaseRefused::because(BusinessRuleViolation::because('The change breaks a rule')));

        $this->assertProblem(Response::HTTP_UNPROCESSABLE_ENTITY, ['violations' => [
            ['propertyPath' => '', 'title' => 'The change breaks a rule', 'reason' => 'businessRuleViolation', 'context' => []],
        ]], $response);
    }

    public function testAnIntentionTheStateDoesNotOfferIsAConflict(): void
    {
        $response = $this->responseTo(UseCaseRefused::because(BookingPageWithoutLink::doesNotChangeIt()));

        $this->assertProblem(Response::HTTP_CONFLICT, ['title' => 'Conflict', 'status' => 409, 'reason' => 'bookingPageWithoutLink'], $response);
    }

    public function testAnIntentionTheCallerHasNoRightToIsForbidden(): void
    {
        $response = $this->responseTo(UseCaseRefused::because(NotTheHolder::ofTheExchange()));

        $this->assertProblem(Response::HTTP_FORBIDDEN, ['title' => 'Forbidden', 'status' => 403, 'reason' => 'notTheHolder'], $response);
    }

    public function testARefusalOutsideTheApiIsLeftToSymfony(): void
    {
        $this->assertNull($this->listenerAloneAnswers(UseCaseRefused::because(NotTheHolder::ofTheExchange()), '/cognito/oauth2/token'));
    }

    public function testAnyOtherExceptionIsAFailureLeftToSymfony(): void
    {
        $this->assertNull($this->listenerAloneAnswers(new \RuntimeException('DynamoDB is down'), self::AN_API_PATH));
    }

    /**
     * @param array<string, mixed> $expected
     */
    private function assertProblem(int $status, array $expected, ?Response $response): void
    {
        $this->assertNotNull($response, 'The listener answers the refusal');
        $this->assertSame($status, $response->getStatusCode());
        $this->assertSame('application/problem+json', $response->headers->get('Content-Type'));
        $problem = self::record(json_decode((string) $response->getContent(), true));
        $answered = [];

        foreach (array_keys($expected) as $field) {
            $answered[$field] = $problem[$field] ?? null;
        }

        $this->assertSame($expected, $answered);
    }

    /**
     * The kernel's dispatcher, so the listener's wiring is proven too.
     */
    private function responseTo(\Throwable $exception): ?Response
    {
        $event = $this->exceptionEvent($exception, self::AN_API_PATH);
        Typed::object(self::getContainer()->get('event_dispatcher'), EventDispatcherInterface::class)->dispatch($event, KernelEvents::EXCEPTION);

        return $event->getResponse();
    }

    /**
     * The listener alone: on the kernel's dispatcher, Symfony's error listener would answer what it leaves.
     */
    private function listenerAloneAnswers(\Throwable $exception, string $path): ?Response
    {
        $event = $this->exceptionEvent($exception, $path);
        Typed::object(self::getContainer()->get(UseCaseExceptionListener::class), UseCaseExceptionListener::class)($event);

        return $event->getResponse();
    }

    private function exceptionEvent(\Throwable $exception, string $path): ExceptionEvent
    {
        return new ExceptionEvent(self::bootKernel(), Request::create($path), HttpKernelInterface::MAIN_REQUEST, $exception);
    }
}
