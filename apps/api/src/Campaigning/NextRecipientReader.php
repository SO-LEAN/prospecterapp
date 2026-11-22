<?php

declare(strict_types=1);

namespace App\Campaigning;

use App\Campaigning\Resource\CampaignMessageResource;
use App\Campaigning\Resource\NextRecipientResource;
use App\Campaigning\Resource\RecipientLinksResource;
use App\Contacts\ContactInListResourceFactory;
use App\Contacts\OrganizationsOfContacts;
use App\Contacts\Resource\ContactInListResource;
use App\Contacts\TagsOfContacts;
use App\Shared\Links;
use Prospecter\Core\Campaigns\Application\NextRecipient\KeptRecipientToHandle;
use Prospecter\Core\Campaigns\Application\NextRecipient\NextRecipient;
use Prospecter\Core\Campaigns\Application\NextRecipient\RecipientToHandle;
use Prospecter\Core\PersonalData\Application\ConsultPersons\ConsultPersons;
use Prospecter\Core\PersonalData\Application\ConsultPersons\KeptPersons;
use Prospecter\Core\PersonalData\Application\PersonCard;
use Prospecter\Core\SharedKernel\Application\QueryHandler;

/**
 * The next recipient of a campaign composed of three contexts, as the recipients are (`RecipientsReader`): Campaigns
 * gives them by their identity and renders their message, PersonalData reads how they are reached, Prospecting and
 * Campaigns tell their organizations and their tags. What the AI does with the message is worded for it here, from
 * the mode the core answers: Prospecter sends nothing (ADR 0144).
 */
final readonly class NextRecipientReader
{
    private const array INSTRUCTIONS = [
        'asIs' => 'Send the message as is, without changing a word, with your own tools: your member\'s mailbox for an e-mail, their LinkedIn for a LinkedIn message. Then log it with `log-sent-message` and the text you sent.',
        'adapted' => 'You may rewrite the message for this person, from what you know of them (their profile, your past exchanges), but always keep the message\'s idea. Send it with your own tools: your member\'s mailbox for an e-mail, their LinkedIn for a LinkedIn message. Then log it with `log-sent-message` and the text you actually sent.',
    ];

    public function __construct(
        private QueryHandler $nextRecipient,
        private QueryHandler $consultPersons,
        private OrganizationsOfContacts $organizationsOfContacts,
        private TagsOfContacts $tagsOfContacts,
        private ContactInListResourceFactory $contactInListResourceFactory,
        private Links $links,
    ) {
    }

    public function read(string $reader, string $campaignId): NextRecipientResource
    {
        $next = $this->nextOf($reader, $campaignId);
        $recipient = ['id' => $campaignId, 'contactId' => $next->contactId];

        return new NextRecipientResource(
            campaignId: $campaignId,
            contact: $this->contactOf($reader, $next->contactId),
            channel: $next->channel,
            message: new CampaignMessageResource($next->subject, $next->text),
            messageMode: $next->messageMode,
            instruction: self::INSTRUCTIONS[$next->messageMode],
            pending: $next->pending,
            links: new RecipientLinksResource(
                noteExchange: $this->links->to('api_campaigns_note_exchange', $recipient),
                dismiss: $this->links->to('api_campaigns_dismiss_recipient', $recipient),
            ),
        );
    }

    private function nextOf(string $reader, string $campaignId): RecipientToHandle
    {
        $kept = new KeptRecipientToHandle();
        ($this->nextRecipient)(new NextRecipient($reader, $campaignId), $kept);

        return $kept->recipient ?? throw new \LogicException('The next recipient was not presented');
    }

    private function contactOf(string $reader, string $contactId): ContactInListResource
    {
        $contacts = [$contactId];

        return $this->contactInListResourceFactory->create(
            $this->personOf($reader, $contactId),
            $this->organizationsOfContacts->read($reader, $contacts)[$contactId] ?? [],
            $this->tagsOfContacts->read($reader, $contacts)[$contactId] ?? [],
        );
    }

    /**
     * A recipient to handle is a contact PersonalData reads to the member managing the personal data.
     */
    private function personOf(string $reader, string $contactId): PersonCard
    {
        $kept = new KeptPersons();
        ($this->consultPersons)(new ConsultPersons($reader, [$contactId]), $kept);

        return $kept->persons[$contactId] ?? throw new \LogicException('The next recipient is not read');
    }
}
