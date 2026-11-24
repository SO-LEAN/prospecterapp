<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\MailImport\AskToImportMail;

/**
 * I am about to ask the provider to read my mailbox along with my calendar, to import my e-mails. The client draws
 * the `state` that the provider gives back with its answer, and the code verifier (PKCE). Both are kept for me while
 * I answer the provider (ADR 0154).
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
