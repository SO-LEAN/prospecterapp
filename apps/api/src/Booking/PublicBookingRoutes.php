<?php

namespace App\Booking;

use App\Shared\Resource\ProblemResource;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * The public routes of Booking, read without signing in by whomever holds a link, are limited by the code of the link
 * (ADR 0153): a route says so by its default `LIMITED_BY_ITS_CODE`. Over the limit, a problem+json 429 the client
 * words, kept by nobody; the limit by address is the edge's of the installation, and the HTTP API's on AWS.
 */
#[AsEventListener(priority: self::ONCE_ROUTED)]
final readonly class PublicBookingRoutes
{
    public const string LIMITED_BY_ITS_CODE = '_limited_by_its_code';
    private const int ONCE_ROUTED = 16;
    private const string REASON = 'tooManyRequests';

    public function __construct(
        #[Target('public_booking_link.limiter')]
        private RateLimiterFactoryInterface $publicBookingLinkLimiter,
        private NormalizerInterface $normalizer,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        $attributes = $event->getRequest()->attributes;
        if (true !== $attributes->get(self::LIMITED_BY_ITS_CODE)) {
            return;
        }

        $limit = $this->publicBookingLinkLimiter->create((string) $attributes->get('code'))->consume();
        if (!$limit->isAccepted()) {
            $event->setResponse($this->tooManyRequests($limit->getRetryAfter()));
        }
    }

    /**
     * The limiter counts on the real clock: so does the delay it gives, in seconds.
     */
    private function tooManyRequests(\DateTimeImmutable $retryAfter): JsonResponse
    {
        $problem = new ProblemResource('Too Many Requests', Response::HTTP_TOO_MANY_REQUESTS, 'This link was opened too often, try again later', reason: self::REASON);

        return new JsonResponse($this->normalizer->normalize($problem), $problem->status, [
            'Content-Type' => 'application/problem+json',
            'Retry-After' => (string) max(0, $retryAfter->getTimestamp() - time()),
            'Cache-Control' => 'no-store',
        ]);
    }
}
