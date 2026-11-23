<?php

declare(strict_types=1);

namespace App\Shared;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * An installation's edge (ADR 0067) adds a secret to the requests it passes on. Behind the edge, the API answers only
 * the requests that carry this secret. Without a secret (locally, or on an installation without a domain), the API
 * answers every request.
 */
#[AsEventListener(priority: self::BEFORE_ROUTING_AND_SECURITY)]
final readonly class OnlyThroughTheEdge
{
    public const string SECRET_HEADER = 'X-Origin-Verify';
    private const int BEFORE_ROUTING_AND_SECURITY = 256;

    public function __construct(#[Autowire(env: 'ORIGIN_SECRET')] private string $secret)
    {
    }

    public function __invoke(RequestEvent $event): void
    {
        if ($event->isMainRequest() && $this->isAnEdgeInFront() && !$this->comesThroughTheEdge($event)) {
            $event->setResponse(new Response(status: Response::HTTP_FORBIDDEN));
        }
    }

    private function isAnEdgeInFront(): bool
    {
        return '' !== $this->secret;
    }

    private function comesThroughTheEdge(RequestEvent $event): bool
    {
        return hash_equals($this->secret, (string) $event->getRequest()->headers->get(self::SECRET_HEADER));
    }
}
