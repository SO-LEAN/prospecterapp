<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\Person\DescribePersons;

use Prospecter\Core\PersonalData\Application\PersonCard;
use Prospecter\Core\PersonalData\Application\Port\PersonCardRetriever;
use Prospecter\Core\PersonalData\Contract\Service\DescribePersons\DescribePersons;
use Prospecter\Core\PersonalData\Contract\Service\DescribePersons\DescribePersonsOutput;
use Prospecter\Core\PersonalData\Contract\Service\DescribePersons\DescribePersonsService;
use Prospecter\Core\PersonalData\Contract\Service\DescribePersons\LinkResponse;
use Prospecter\Core\PersonalData\Contract\Service\DescribePersons\PersonDescriptionResponse;
use Prospecter\Core\PersonalData\Domain\Exception\UnknownReader;
use Prospecter\Core\PersonalData\Domain\Model\PersonId;
use Prospecter\Core\PersonalData\Domain\Port\Readers;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;
use Prospecter\Core\SharedKernel\Application\Response\ExternalLink;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Describes persons as a list shows them to their reader, for the views of the other contexts (ADR 0217).
 */
final readonly class DescribePersonsHandler implements QueryHandler, DescribePersonsService
{
    public function __construct(
        private PersonCardRetriever $personCardRetriever,
        private Readers $readers,
    ) {
    }

    /**
     * An unknown reader reads nobody. The service refuses nothing.
     */
    public function __invoke(DescribePersons $query, DescribePersonsOutput $output): void
    {
        try {
            $reader = $this->readers->readerFrom(new MemberId($query->readerId));
        } catch (UnknownReader) {
            $output->present([]);

            return;
        }

        $cards = $this->personCardRetriever->ofIds(array_map(static fn (string $id): PersonId => new PersonId($id), $query->personIds), $reader);

        $output->present(array_map(self::responseOf(...), $cards));
    }

    private static function responseOf(PersonCard $card): PersonDescriptionResponse
    {
        return new PersonDescriptionResponse(
            personId: $card->id,
            masked: $card->masked,
            firstName: $card->firstName,
            lastName: $card->lastName,
            title: $card->civility,
            jobTitle: $card->jobTitle,
            email: $card->email,
            phoneNumber: $card->phoneNumber,
            language: $card->language,
            links: array_map(static fn (ExternalLink $link): LinkResponse => new LinkResponse($link->source, $link->identifier, $link->address), $card->links),
        );
    }
}
