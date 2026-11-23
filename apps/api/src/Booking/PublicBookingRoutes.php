<?php

declare(strict_types=1);

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
 * Limits Booking's public routes by the link's code (ADR 0153). Anyone with a link reads these routes without signing
 * in. A route asks for the limit with its default `LIMITED_BY_ITS_CODE`. Over the limit, the answer is a problem+json
 * 429 that nobody caches, and the client writes its wording. The limit per address belongs to the installation's edge,
 * and to the HTTP API on AWS. An oversized code leads nowhere: it is neither counted nor looked up, because a table key
 * holds 2 KB at most.
 */
#[AsEventListener(priority: self::ONCE_ROUTED)]
final readonly class PublicBookingRoutes
{
    public const string LIMITED_BY_ITS_CODE = '_limited_by_its_code';
    /**
     * Far above a drawn code's 22 characters, and far below what a table key holds.
     */
    private const int LONGEST_CODE = 64;
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
        $code = $attributes->getString('code');
        if (true !== $attributes->get(self::LIMITED_BY_ITS_CODE) || self::isOversized($code)) {
            return;
        }

        $limit = $this->publicBookingLinkLimiter->create($code)->consume();
        if (!$limit->isAccepted()) {
            $event->setResponse($this->tooManyRequests($limit->getRetryAfter()));
        }
    }

    public static function isOversized(string $code): bool
    {
        return mb_strlen($code) > self::LONGEST_CODE;
    }

    /**
     * The limiter counts on the real clock, so the delay it gives, in seconds, does too.
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
