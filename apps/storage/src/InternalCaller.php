<?php

declare(strict_types=1);

namespace App;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * Only the platform's services (the API) store and remove files. They send the shared token in its own header. On AWS,
 * the Authorization header carries their role's signature (ADR 0068).
 */
final readonly class InternalCaller
{
    public const string TOKEN_HEADER = 'X-Storage-Token';

    public function __construct(private string $token)
    {
    }

    public function ensure(Request $request): void
    {
        if (!hash_equals($this->token, (string) $request->headers->get(self::TOKEN_HEADER))) {
            throw new UnauthorizedHttpException(self::TOKEN_HEADER, 'A valid storage token is required.');
        }
    }
}
