<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\NextRecipient;

/**
 * The first recipient of a campaign still to handle, and its message rendered for them.
 */
final readonly class RecipientToHandle
{
    /**
     * @param string      $channel     `email`, `call`, `sms` or `linkedIn`
     * @param string|null $subject     the subject of an e-mail
     * @param string      $messageMode how an AI treats the message: `asIs`, sent as rendered, or `adapted` to the person
     * @param int         $pending     the recipients still to handle, this one included
     */
    public function __construct(
        public string $campaignId,
        public string $contactId,
        public string $channel,
        public ?string $subject,
        public string $text,
        public string $messageMode,
        public int $pending,
    ) {
    }
}
