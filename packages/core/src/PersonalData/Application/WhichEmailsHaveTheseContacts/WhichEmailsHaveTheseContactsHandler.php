<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\WhichEmailsHaveTheseContacts;

use Prospecter\Core\PersonalData\Application\PersonCard;
use Prospecter\Core\PersonalData\Application\Port\PersonCardRetriever;
use Prospecter\Core\PersonalData\Contract\Service\WhichEmailsHaveTheseContacts\WhichEmailsHaveTheseContacts;
use Prospecter\Core\PersonalData\Contract\Service\WhichEmailsHaveTheseContacts\WhichEmailsHaveTheseContactsOutput;
use Prospecter\Core\PersonalData\Contract\Service\WhichEmailsHaveTheseContacts\WhichEmailsHaveTheseContactsService;
use Prospecter\Core\PersonalData\Domain\Exception\UnknownReader;
use Prospecter\Core\PersonalData\Domain\Model\PersonId;
use Prospecter\Core\PersonalData\Domain\Port\Readers;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * A contact's address leaves PersonalData only for a member who reads the contact unmasked and may manage their
 * account's personal data (ADR 0031). Whoever reads the address does not keep it.
 */
final readonly class WhichEmailsHaveTheseContactsHandler implements QueryHandler, WhichEmailsHaveTheseContactsService
{
    public function __construct(
        private PersonCardRetriever $personCardRetriever,
        private Readers $readers,
    ) {
    }

    /**
     * An unknown reader reads nobody. The service refuses nothing.
     */
    public function __invoke(WhichEmailsHaveTheseContacts $query, WhichEmailsHaveTheseContactsOutput $output): void
    {
        try {
            $reader = $this->readers->readerFrom(new MemberId($query->readerId));
        } catch (UnknownReader) {
            $output->present([]);

            return;
        }

        $cards = $this->personCardRetriever->ofIds(array_map(static fn (string $id): PersonId => new PersonId($id), $query->contactIds), $reader);
        $inClear = array_filter($cards, static fn (PersonCard $card): bool => !$card->masked);

        $output->present(array_column(array_map(static fn (PersonCard $card): array => [$card->id, $card->email], $inClear), 1, 0));
    }
}
