<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Model;

/**
 * The message of a campaign as the member sends or says it to one contact.
 */
final readonly class RenderedMessage
{
    public function __construct(
        public ?string $subject,
        public string $text,
    ) {
    }
}
