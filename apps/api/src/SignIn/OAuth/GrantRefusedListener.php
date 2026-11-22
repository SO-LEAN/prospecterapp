<?php

declare(strict_types=1);

namespace App\SignIn\OAuth;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

/**
 * A grant the facade refuses is answered as RFC 6749 says (5.2), the shape every client of OAuth reads.
 */
#[AsEventListener]
final readonly class GrantRefusedListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $refusal = $event->getThrowable();

        if (!$refusal instanceof GrantRefused) {
            return;
        }

        $event->setResponse(new JsonResponse(
            ['error' => $refusal->error, 'error_description' => $refusal->getMessage()],
            Response::HTTP_BAD_REQUEST,
            ['Cache-Control' => 'no-store'],
        ));
    }
}
