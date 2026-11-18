<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\AskToImportMail;

/**
 * I am about to ask the provider for the reading of my mailbox, my calendar with it, to import my e-mails: the
 * `state` it will give back with its answer, and the verifier of its code (PKCE), both drawn by the client, are kept
 * for me while I answer it (ADR 0154).
 */
final readonly class AskToImportMail
{
    public function __construct(
        public string $memberId,
        public string $provider,
        public string $state,
        public string $codeVerifier,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
