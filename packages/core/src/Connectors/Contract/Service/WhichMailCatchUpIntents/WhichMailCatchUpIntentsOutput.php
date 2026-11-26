<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Contract\Service\WhichMailCatchUpIntents;

interface WhichMailCatchUpIntentsOutput
{
    /**
     * @param list<MailCatchUpIntentsResponse> $contacts      the contacts the reader reaches
     * @param list<MailCatchUpIntentsResponse> $organizations the organizations the reader's account prospects
     */
    public function present(array $contacts, array $organizations): void;
}
