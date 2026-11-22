<?php

declare(strict_types=1);

namespace App\Campaigning\Resource;

use App\Contacts\Resource\ContactInListResource;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * The recipient an AI leading a campaign handles next, how it reaches them, the message it sends them itself and how
 * it treats it, and how many are left (ADR 0144): `get-next-recipient`, a tool without an endpoint.
 */
final readonly class NextRecipientResource
{
    /**
     * @param string            $channel     `email`, `call`, `sms` or `linkedIn`
     * @param string            $messageMode `asIs` or `adapted`
     * @param string            $instruction what the AI does with the message, worded for it
     * @param int               $pending     the recipients still to handle, this one included
     * @param ?DailyCapResource $dailyCap    the daily cap of the campaign, none without one
     * @param ?string           $warning     what the AI does once the daily cap is reached, worded for it; none before
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
