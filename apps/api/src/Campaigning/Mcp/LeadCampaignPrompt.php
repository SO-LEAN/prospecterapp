<?php

declare(strict_types=1);

namespace App\Campaigning\Mcp;

use Mcp\Server\Capability\Attribute\McpPrompt;

/**
 * The steps an AI follows to run a started campaign for its member, one recipient at a time. The AI sends each message
 * with its own tools, and Prospecter logs it (ADR 0144, 0186).
 */
final readonly class LeadCampaignPrompt
{
    private const string STEPS = <<<'STEPS'
        Run the Prospecter campaign "%1$s" for me, one recipient after another. Prospecter sends nothing: you send each message yourself.

        1. Find it with `list-campaigns`: it must be started. When "%1$s" is not the ID or the exact name of a single started campaign, ask me which one to run, and send nothing until I answer. Do not guess.
        2. Ask for its next recipient with `get-next-recipient`: how to reach them, the message rendered for them, and its instruction.
        3. Send the message yourself on its channel: from my mailbox for an e-mail (Claude's Gmail connector), from my LinkedIn for a LinkedIn message (Claude's Chrome extension). Follow its instruction. As is: send it without changing a word. Adapted: you may rewrite it for this person, from their profile and our past exchanges, but always keep the message's idea.
        4. Log what you sent with `log-sent-message`: the text you actually sent, and the e-mail's Message-ID or the LinkedIn conversation. Logged first by my mailbox import, it joins the campaign; already logged for the campaign, go on.
        5. A recipient you cannot reach (no address, a LinkedIn profile that is gone): once I confirm, dismiss them with `dismiss-campaign-recipient` and say why.
        6. Never send a second message to a recipient: when `get-next-recipient` gives you a recipient you already wrote to, stop and tell me who.
        7. Go back to step 2 until `get-next-recipient` answers that the campaign is no longer running (`campaignNotRunning`), or until I tell you to stop. End with a report: sent, dismissed.

        If Prospecter refuses a call, stop and ask me what to do. Do not work around the refusal.
        STEPS;

    /**
     * @param string $campaign the campaign's ID, or its name in quotes
     *
     * @return list<array{role: string, content: string}>
     */
    #[McpPrompt(name: 'run-campaign', title: 'Run a campaign', description: 'Send each recipient of a started campaign their message with your own tools, and log it in Prospecter.')]
    public function __invoke(string $campaign): array
    {
        return [['role' => 'user', 'content' => sprintf(self::STEPS, $campaign)]];
    }
}
