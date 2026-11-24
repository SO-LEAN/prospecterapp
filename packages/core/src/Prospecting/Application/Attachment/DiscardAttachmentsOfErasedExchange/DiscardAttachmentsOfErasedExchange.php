<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Attachment\DiscardAttachmentsOfErasedExchange;

final readonly class DiscardAttachmentsOfErasedExchange
{
    /**
     * @param list<string> $files the erased exchange's files in the private storage
     */
    public function __construct(
        public array $files,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
