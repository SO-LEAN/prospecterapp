<?php

declare(strict_types=1);

namespace App\Identification\Mcp;

use Mcp\Server\Capability\Attribute\McpPrompt;
use Mcp\Server\Exception\RegistryException;

/**
 * The steps an AI follows to sync its member's LinkedIn contacts or messages, which it reads with its own tools, so
 * that nothing is created or logged twice. The member asks for it in their own words.
 */
final readonly class SynchronizeLinkedInPrompt
{
    private const string IDENTIFYING = <<<'STEP'
        Before you create anything, ask Prospecter who it knows with `identify`, in batches of at most a hundred: each person with their first and last names and their LinkedIn profile URL among their links, and their company with its name and its LinkedIn page URL. Give each person's public profile address (`linkedin.com/in/<name>/`), never the member ID a conversation shows (`linkedin.com/in/ACoAA…`). When you only have that ID, open the profile and read the public address there. For each person or company that is `toConfirm`, show me its candidates and ask me which one it is, or whether it is someone else. Show me the `new` persons and let me pick the ones I care about; ignore the others.
        STEP;

    private const string RECORDING = <<<'STEP'
        Record each person I picked with `record-contact`: their name, their job title, their LinkedIn profile URL among their links, and their company with its LinkedIn page URL.
        STEP;

    private const string NOTING = <<<'STEP'
        Log each message with `log-contact-exchange` on the contact (`log-organization-exchange` for a company page): the channel `linkedIn`, the direction (`inbound` received, `outbound` sent), `occurredAt` at the minute it was sent, its text, and `linkedInConversation`, the conversation ID or the thread URL (`linkedin.com/messaging/thread/<id>/`).
        STEP;

    private const string RULES = <<<'STEP'
        Rules:
        - Never create a `toConfirm` person or company without my answer.
        - Never log a message without its conversation when you know it.
        - If Prospecter refuses a call, stop and ask me what to do. Do not work around the refusal.
        STEP;

    private const string REPORT = <<<'STEP'
        End with a report: created, completed, already logged, ignored. A `created` contact counts as created. A `known` contact with completed fields counts as completed; `known` with nothing completed counts as already logged. A `noted` message counts as created; `alreadyNoted` and `erasedBefore` count as already logged. What I turned down counts as ignored. A sync run again counts only items already logged.
        STEP;

    /**
     * @param string  $what  contacts or messages
     * @param ?string $since how far back to go, in quotes ("7 days", "November 1"…), asked when not given
     *
     * @return list<array{role: string, content: string}>
     */
    #[McpPrompt(name: 'sync-linkedin', title: 'Sync LinkedIn', description: 'Record your relevant LinkedIn contacts in Prospecter, or log your LinkedIn messages, without duplicates.')]
    public function __invoke(string $what, ?string $since = null): array
    {
        return [['role' => 'user', 'content' => implode("\n\n", self::stepsOf(self::synchronized($what), $since))]];
    }

    private static function synchronized(string $what): WhatLinkedInSynchronizes
    {
        return WhatLinkedInSynchronizes::tryFrom($what) ?? throw RegistryException::invalidParams('`what` is `contacts` or `messages`.');
    }

    /**
     * @return list<string>
     */
    private static function stepsOf(WhatLinkedInSynchronizes $what, ?string $since): array
    {
        $noting = WhatLinkedInSynchronizes::Messages === $what ? [self::NOTING] : [];

        return [self::reading($what, $since), self::IDENTIFYING, self::RECORDING, ...$noting, self::REPORT, self::RULES];
    }

    private static function reading(WhatLinkedInSynchronizes $what, ?string $since): string
    {
        return sprintf('Sync my LinkedIn %s into Prospecter. %s Read them with your own tools (Claude\'s Chrome extension, for example): Prospecter does not read LinkedIn.', $what->value, SynchronizedSince::said($since));
    }
}
