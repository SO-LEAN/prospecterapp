<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain;

/**
 * The header `Message-ID` of an e-mail, the same in every mailbox it reached: Connectors reads it from a mailbox,
 * Prospecting from what an AI gives, and both identify the e-mail alike (ADR 0154, 0166).
 */
final readonly class MessageId
{
    private const string HASH = 'sha256';

    private string $normalized;

    public function __construct(string $header)
    {
        $this->normalized = mb_strtolower(trim($header, " \t<>"));
    }

    public function isMissing(): bool
    {
        return '' === $this->normalized;
    }

    /**
     * Hashed, so that what the e-mail says stays in the mailbox.
     */
    public function identity(): string
    {
        return hash(self::HASH, $this->normalized);
    }
}
