<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Prospecter\Core\Prospecting\Application\EraseExchange\EraseExchange;
use Prospecter\Core\Prospecting\Application\NoteExchange\NoteExchange;
use Prospecter\Core\Prospecting\Application\NoteOrganizationExchange\NoteOrganizationExchange;
use Prospecter\Core\Prospecting\Domain\Exception\ExchangeAlreadyNoted;
use Prospecter\Core\Prospecting\Domain\Exception\ExchangeErasedBefore;
use Prospecter\Core\Prospecting\Domain\Model\Channel;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\SharedKernel\Application\Exception\ErrorType;
use Tests\Core\Acceptance\Expect;
use Tests\Core\Acceptance\Vocabulary;
use Tests\Core\Double\StateOf;

/**
 * The exchanges an AI notes once only, recognized by their fingerprint, whoever noted them first (ADR 0166).
 */
final class FingerprintContext implements Context
{
    use SharesTheScenario;

    private ?string $firstNoted = null;

    #[Given('/^l\'IA a noté l\'e-mail (?<direction>reçu|envoyé) (?:de|à) "(?<contact>[^"]+)" le (?<day>\S+) à (?<time>\S+) de Message-ID "(?<messageId>[^"]*)"$/')]
    public function theAINotedAnEMail(string $direction, string $contact, string $day, string $time, string $messageId): void
    {
        $this->firstNoted = $this->aiNotes($contact, Channel::Email, $direction, $day, $time, null, $messageId, null);
    }

    #[When('/^l\'IA note l\'e-mail (?<direction>reçu|envoyé) (?:de|à) "(?<contact>[^"]+)" le (?<day>\S+) à (?<time>\S+) de Message-ID "(?<messageId>[^"]*)"$/')]
    public function theAINotesAnEMail(string $direction, string $contact, string $day, string $time, string $messageId): void
    {
        $this->aiNotes($contact, Channel::Email, $direction, $day, $time, null, $messageId, null);
    }

    #[Given('/^l\'IA a noté le message LinkedIn (?<direction>reçu|envoyé) (?:de|à) "(?<contact>[^"]+)" dans la conversation "(?<conversation>[^"]*)" le (?<day>\S+) à (?<time>\S+) : "(?<text>[^"]*)"$/')]
    public function theAINotedALinkedInMessage(string $direction, string $contact, string $conversation, string $day, string $time, string $text): void
    {
        $this->firstNoted = $this->aiNotes($contact, Channel::LinkedIn, $direction, $day, $time, $text, null, $conversation);
    }

    #[When('/^l\'IA note le message LinkedIn (?<direction>reçu|envoyé) (?:de|à) "(?<contact>[^"]+)" dans la conversation "(?<conversation>[^"]*)" le (?<day>\S+) à (?<time>\S+) : "(?<text>[^"]*)"$/')]
    public function theAINotesALinkedInMessage(string $direction, string $contact, string $conversation, string $day, string $time, string $text): void
    {
        $this->aiNotes($contact, Channel::LinkedIn, $direction, $day, $time, $text, null, $conversation);
    }

    #[When('/^l\'IA note le SMS (?<direction>reçu|envoyé) (?:de|à) "(?<contact>[^"]+)" le (?<day>\S+) à (?<time>\S+)$/')]
    public function theAINotesAnSms(string $direction, string $contact, string $day, string $time): void
    {
        $this->aiNotes($contact, Channel::Sms, $direction, $day, $time, null, null, null);
    }

    #[Given('/^l\'IA a noté le message Malt (?<direction>reçu|envoyé) (?:de|à) "(?<organization>[^"]+)" elle-même le (?<day>\S+) à (?<time>\S+) : "(?<text>[^"]*)"$/')]
    public function theAINotedAMessageWithTheOrganization(string $direction, string $organization, string $day, string $time, string $text): void
    {
        $this->firstNoted = $this->aiNotesWithTheOrganization($organization, Channel::Malt, $direction, $day, $time, $text);
    }

    #[When('/^l\'IA note le message Malt (?<direction>reçu|envoyé) (?:de|à) "(?<organization>[^"]+)" elle-même le (?<day>\S+) à (?<time>\S+) : "(?<text>[^"]*)"$/')]
    public function theAINotesAMessageWithTheOrganization(string $direction, string $organization, string $day, string $time, string $text): void
    {
        $this->aiNotesWithTheOrganization($organization, Channel::Malt, $direction, $day, $time, $text);
    }

    #[Given('/^l\'IA a noté depuis "(?<organization>[^"]+)" un appel (?<direction>reçu|envoyé) (?:de|à) "(?<contact>[^"]+)" le (?<day>\S+) à (?<time>\S+)$/')]
    public function theAINotedACallFromTheOrganization(string $organization, string $direction, string $contact, string $day, string $time): void
    {
        $this->aiNotesWithTheOrganization($organization, Channel::Call, $direction, $day, $time, null, $this->scenario->personIdOf($contact));
    }

    #[When('/^l\'IA note le SMS (?<direction>reçu|envoyé) (?:de|à) "(?<contact>[^"]+)" le (?<day>\S+) à (?<time>\S+) de Message-ID "(?<messageId>[^"]*)"$/')]
    public function theAINotesAnSmsWithAMessageId(string $direction, string $contact, string $day, string $time, string $messageId): void
    {
        $this->aiNotes($contact, Channel::Sms, $direction, $day, $time, null, $messageId, null);
    }

    #[Then('c\'est déjà noté, avec l\'échange existant')]
    public function itIsAlreadyNoted(): void
    {
        $refusal = $this->application->refusal();
        Expect::that($refusal?->getPrevious() instanceof ExchangeAlreadyNoted, sprintf('Already noted was expected, got: %s', $refusal?->getMessage() ?? 'no refusal'));
        Expect::same(ErrorType::NotPossibleInItsState, $refusal?->type);
        $existing = $refusal?->context['exchange'] ?? '';
        Expect::that(null !== $this->application->database->exchange($existing), sprintf('The exchange %s was expected to exist', $existing));
        Expect::that(null === $this->firstNoted || $this->firstNoted === $existing, 'The exchange noted first was expected');
    }

    #[Given('j\'ai supprimé l\'échange noté par l\'IA')]
    public function iErasedTheExchangeNotedByTheAI(): void
    {
        $this->application->dispatch(new EraseExchange($this->application->signedIn(), (string) $this->firstNoted, $this->application->now()));
    }

    #[Then('/^c\'est supprimé par moi le (?<day>\S+) à (?<time>\S+), non renoté$/')]
    public function itWasErasedByMe(string $day, string $time): void
    {
        $refusal = $this->application->refusal();
        Expect::that($refusal?->getPrevious() instanceof ExchangeErasedBefore, sprintf('Erased before was expected, got: %s', $refusal?->getMessage() ?? 'no refusal'));
        Expect::same(ErrorType::NotPossibleInItsState, $refusal?->type);
        Expect::same($this->application->signedIn(), $refusal?->context['erasedBy'] ?? null);
        Expect::same(Vocabulary::frenchMoment(sprintf('%s à %s', $day, $time))->format(\DATE_ATOM), $refusal?->context['erasedAt'] ?? null);
    }

    #[Then('le compte ne garde l\'empreinte d\'aucun échange')]
    public function theAccountKeepsNoFingerprint(): void
    {
        Expect::same(0, $this->application->database->fingerprintCount());
    }

    #[Then(':contact n\'a qu\'un échange')]
    public function hasOneExchange(string $contact): void
    {
        $this->hasExchanges($contact, 1);
    }

    #[Then(':contact a :count échanges')]
    public function hasExchanges(string $contact, int $count): void
    {
        $person = $this->scenario->personIdOf($contact);
        $with = array_filter($this->application->database->exchanges(), static fn (Exchange $exchange): bool => StateOf::the($exchange)->value('contact')?->value === $person);
        Expect::same($count, count($with));
    }

    private function aiNotesWithTheOrganization(string $organization, Channel $channel, string $direction, string $day, string $time, ?string $text, ?string $contact = null): string
    {
        $id = $this->application->newIdentity();
        $this->application->dispatch(new NoteOrganizationExchange(
            prospectorId: $this->application->signedIn(),
            id: $id,
            organizationId: $this->scenario->idOf($organization),
            personId: $contact,
            channel: $channel->value,
            direction: Vocabulary::direction($direction),
            occurredAt: Vocabulary::frenchMoment(sprintf('%s à %s', $day, $time)),
            text: $text,
            missed: false,
            executedAt: $this->application->now(),
            noteOnce: true,
            byAnAI: true,
        ));
        $this->scenario->rememberTheExchange($id);

        return $id;
    }

    private function aiNotes(string $contact, Channel $channel, string $direction, string $day, string $time, ?string $text, ?string $messageId, ?string $linkedInConversation): string
    {
        $id = $this->application->newIdentity();
        $this->application->dispatch(new NoteExchange(
            prospectorId: $this->application->signedIn(),
            id: $id,
            personId: $this->scenario->personIdOf($contact),
            channel: $channel->value,
            direction: Vocabulary::direction($direction),
            occurredAt: Vocabulary::frenchMoment(sprintf('%s à %s', $day, $time)),
            text: $text,
            missed: false,
            executedAt: $this->application->now(),
            messageId: $messageId,
            linkedInConversation: $linkedInConversation,
            noteOnce: true,
            byAnAI: true,
        ));
        $this->scenario->rememberTheExchange($id);

        return $id;
    }
}
