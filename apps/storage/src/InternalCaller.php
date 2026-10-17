<?php

namespace App;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * Only the services of the platform (the API) store and remove files: they send the shared token in its own header,
 * the Authorization header carrying the signature of their role on AWS (ADR 0068).
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
