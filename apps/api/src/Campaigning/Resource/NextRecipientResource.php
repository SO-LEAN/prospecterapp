<?php

declare(strict_types=1);

namespace App\Campaigning\Resource;

use App\Contacts\Resource\ContactInListResource;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * The recipient that an AI running a campaign handles next (ADR 0144). It holds how the AI reaches them, the message
 * the AI sends them itself and how to treat it, and how many recipients are left. `get-next-recipient` returns it, a
 * tool without an endpoint.
 */
final readonly class NextRecipientResource
{
    /**
     * @param string            $channel     `email`, `call`, `sms` or `linkedIn`
     * @param string            $messageMode `asIs` or `adapted`
     * @param string            $instruction what the AI does with the message, worded for it
     * @param int               $pending     the count of recipients still to handle, this one included
     * @param ?DailyCapResource $dailyCap    the campaign's daily cap, null without one
     * @param ?string           $warning     what the AI does once the daily cap is reached, worded for it; null before
     */
    public function __construct(
        public string $campaignId,
        public ContactInListResource $contact,
        public string $channel,
        public CampaignMessageResource $message,
        public string $messageMode,
        public string $instruction,
        public int $pending,
        public ?DailyCapResource $dailyCap,
        public ?string $warning,
        #[SerializedName('_links')]
        #[Context([AbstractObjectNormalizer::SKIP_NULL_VALUES => true])]
        public RecipientLinksResource $links,
    ) {
    }
}
