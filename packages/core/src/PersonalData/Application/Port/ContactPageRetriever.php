<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\Port;

use Prospecter\Core\PersonalData\Application\SearchContacts\ContactPage;
use Prospecter\Core\PersonalData\Domain\Model\Reader;
use Prospecter\Core\SharedKernel\Domain\SearchKey;

/**
 * Reads the contacts of the account of the reader in slices without restoring them (ADR 0088), each as the
 * reader may read it, with what the reader may do with the contacts (`Reader::contactsIntents()`).
 */
interface ContactPageRetriever
{
    /**
     * Newest first, after the cursor: those found by the search (ADR 0071), all of them without one.
     */
    public function sliceFor(Reader $reader, ?SearchKey $search, ?string $after, int $max): ContactPage;
}
