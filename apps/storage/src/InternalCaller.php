<?php

namespace App;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * Only the services of the platform (the API) store and remove files: they send the shared token.
 */
final readonly class InternalCaller
{
    public function __construct(private string $token)
    {
    }

    public function ensure(Request $request): void
    {
        if (!hash_equals(sprintf('Bearer %s', $this->token), (string) $request->headers->get('Authorization'))) {
            throw new UnauthorizedHttpException('Bearer', 'A valid storage token is required.');
        }
    }
}
