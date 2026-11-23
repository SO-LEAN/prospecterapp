<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Model;

/**
 * An e-mail's `Message-ID` header as a mailbox gives it, the same in every mailbox the e-mail reached.
 * Prospecting has its own `MessageId` for what an AI gives. Both identify the e-mail the same way (ADR 0154, 0166).
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
