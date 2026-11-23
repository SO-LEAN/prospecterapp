<?php

declare(strict_types=1);

namespace App\Connectors\Input;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * The full name of the topic in the account's Google Cloud that its mailboxes publish their changes to. It comes with
 * the service account that the subscription pushes the changes with (ADR 0154).
 */
final readonly class GmailNotificationsInput
{
    public function __construct(
        #[Assert\NotBlank]
        public ?string $topic = null,
        #[Assert\NotBlank]
        #[Assert\Email]
        public ?string $pushAccount = null,
    ) {
    }
}
