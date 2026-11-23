<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\Port;

use Prospecter\Core\PersonalData\Application\SearchContacts\ContactPage;
use Prospecter\Core\PersonalData\Domain\Model\Reader;
use Prospecter\Core\SharedKernel\Domain\Search\SearchKey;

/**
 * Reads the contacts of the reader's account in slices, without restoring them (ADR 0088). Each contact is read as
 * the reader may read it. The page tells what the reader may do with the contacts (`Reader::contactsIntents()`).
 */
interface ContactPageRetriever
{
    /**
     * Returns the contacts newest first, after the cursor. With a search, it returns those the search finds (ADR 0071).
     * Without one, it returns all of them.
     */
    public function sliceFor(Reader $reader, ?SearchKey $search, ?string $after, int $max): ContactPage;
}
