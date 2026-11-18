<?php

namespace App\Connectors\Input;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * The topic of the Google Cloud of the account its mailboxes publish their changes to, in full, and the account of
 * service the subscription pushes them with (ADR 0154).
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
