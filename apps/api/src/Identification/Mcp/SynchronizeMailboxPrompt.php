<?php

declare(strict_types=1);

namespace App\Identification\Mcp;

use Mcp\Server\Capability\Attribute\McpPrompt;

/**
 * The steps an AI follows to log, once each, the e-mails its member exchanged with the people Prospecter knows. The AI
 * reads the mailbox with its own tools. The member asks for the sync in their own words.
 */
final readonly class SynchronizeMailboxPrompt
{
    private const string IDENTIFYING = <<<'STEP'
        Ask Prospecter who it knows among the correspondents with `identify`, in batches of at most a hundred: each person with their first and last names and their e-mail address, and their company with its name and the e-mail address (its domain points to the company). Keep only the `known` correspondents, except the ones I ask you to create: record those with `record-contact`. For each person or company that is `toConfirm`, show me its candidates and ask me which one it is, or whether it is someone else.
        STEP;

    private const string NOTING = <<<'STEP'
        Log each e-mail exchanged with a kept correspondent with `log-contact-exchange` on the contact (`log-organization-exchange` when it is the company's address): the channel `email`, the direction (`inbound` received, `outbound` sent), `occurredAt` the moment it was sent, its subject then its text, and `messageId`, its Message-ID header as you read it in the mailbox. Log an e-mail sent to two contacts with each of them.
        STEP;

    private const string REPORT = <<<'STEP'
        End with a report: created, completed, already logged, ignored. A `noted` e-mail counts as created; `alreadyNoted` and `erasedBefore` count as already logged. A contact I asked you to create counts as created when it is `created`, and as completed when it is `known` with completed fields. An e-mail from a correspondent Prospecter does not know counts as ignored. A sync run again counts only items already logged or ignored.
        STEP;

    private const string RULES = <<<'STEP'
        Rules:
        - Never create a `toConfirm` person or company without my answer.
        - Never log an e-mail without its Message-ID when you can read it.
        - If Prospecter refuses a call, stop and ask me what to do. Do not work around the refusal.
        STEP;

    /**
     * @param ?string $since how far back to go, in quotes ("7 days", "November 1"…), asked when not given
     *
     * @return list<array{role: string, content: string}>
     */
    #[McpPrompt(name: 'sync-mailbox', title: 'Sync my mailbox', description: 'Log the e-mails you exchanged with your contacts in Prospecter, without duplicates.')]
    public function __invoke(?string $since = null): array
    {
        return [['role' => 'user', 'content' => implode("\n\n", [self::reading($since), self::IDENTIFYING, self::NOTING, self::REPORT, self::RULES])]];
    }

    private static function reading(?string $since): string
    {
        return sprintf('Sync my mailbox into Prospecter. %s Read it with your own tools (Claude\'s Gmail connector, for example): Prospecter does not read it for you.', SynchronizedSince::said($since));
    }
}
