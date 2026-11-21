<?php

namespace App\Campaigning\Mcp;

use Mcp\Schema\Content\PromptMessage;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Enum\Role;
use Mcp\Server\Capability\Attribute\McpPrompt;

/**
 * The way an AI leads a started campaign for its member, recipient after recipient: it sends each message itself,
 * Prospecter notes it (ADR 0144). Reading nothing, it is given to any member signed in; the tools it names check
 * their rights.
 */
final readonly class LeadCampaignPrompt
{
    private const string STEPS = <<<'STEPS'
        Lead the campaign « %s » of Prospecter for me, recipient after recipient.

        1. Find it by lister-campagnes; it must be started.
        2. Ask destinataire-suivant for its next recipient: how to reach them, the message rendered for them and its instruction.
        3. Send the message yourself, by its channel: by my mailbox for an e-mail (Gmail), by my LinkedIn for a LinkedIn message (the Chrome extension of Claude). Follow its instruction: as is, send it without changing a word; adapted, you may rewrite it for this person, from their profile and our past exchanges, always keeping the idea of the message. Prospecter sends nothing.
        4. Note what you sent by noter-l-envoi, with the text really sent and the Message-ID of the e-mail, or the conversation of the LinkedIn message. Already noted, go on.
        5. A recipient you cannot reach (no address, a LinkedIn profile gone): dismiss them by ecarter-destinataire-campagne, saying why, once I confirmed.
        6. Start again at step 2, until destinataire-suivant answers that the campaign is no longer led (campaignNotRunning), or I tell you to stop. Then tell me how many you sent and dismissed.
        STEPS;

    /**
     * @param string $campagne the name of the campaign, or its id
     *
     * @return list<PromptMessage>
     */
    #[McpPrompt(name: 'mener-une-campagne', title: 'Mener une campagne', description: 'Lead a started campaign: take each recipient, send them its message by your own tools, note it in Prospecter, again until the campaign ends.')]
    public function __invoke(string $campagne): array
    {
        return [new PromptMessage(Role::User, new TextContent(sprintf(self::STEPS, $campagne)))];
    }
}
