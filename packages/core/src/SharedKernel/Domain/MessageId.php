<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain;

/**
 * An e-mail's `Message-ID` header, the same in every mailbox the e-mail reached. Connectors reads it from a mailbox.
 * Prospecting reads it from what an AI gives. Both identify the e-mail the same way (ADR 0154, 0166).
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
     * The header is hashed, so that the e-mail's content stays in the mailbox.
     */
    public function identity(): string
    {
        return hash(self::HASH, $this->normalized);
    }
}
