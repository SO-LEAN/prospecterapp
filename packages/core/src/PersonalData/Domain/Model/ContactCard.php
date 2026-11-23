<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\Contact\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Link\ExternalReferences;

/**
 * What a person's card says when they are collected: their name, how to reach them, their title, their job title and
 * their links. Each of them may be unknown.
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
