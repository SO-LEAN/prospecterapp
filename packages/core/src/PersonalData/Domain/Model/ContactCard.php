<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\ExternalReferences;

/**
 * What the card of a person says when they are collected: their name, how they are reached, their civility, their
 * job title and their links, each unknown or not.
 */
final readonly class ContactCard
{
    public ExternalReferences $references;

    public function __construct(
        public PersonName $name = new PersonName(),
        public ContactDetails $contactDetails = new ContactDetails(),
        public Civility $civility = Civility::X,
        public ?string $jobTitle = null,
        ?ExternalReferences $references = null,
    ) {
        $this->references = $references ?? ExternalReferences::none();
    }
}
