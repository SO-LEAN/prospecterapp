<?php

namespace App\Campaigning\Mcp;

use Mcp\Server\Capability\Attribute\McpPrompt;

/**
 * The steps an AI follows to lead a started campaign for its member, recipient after recipient: it sends each message
 * with its own tools, Prospecter notes it (ADR 0144, 0186).
 */
final readonly class LeadCampaignPrompt
{
    private const string STEPS = <<<'STEPS'
        Mène pour moi la campagne « %s » de Prospecter, destinataire après destinataire. Prospecter n'envoie rien : c'est toi qui envoies chaque message.

        1. Trouve-la avec `list-campaigns` : elle doit être démarrée.
        2. Demande son destinataire suivant avec `get-next-recipient` : comment le joindre, le message rendu pour lui et son instruction.
        3. Envoie toi-même le message par son canal : par ma boîte e-mail pour un e-mail (le connecteur Gmail de Claude), par mon LinkedIn pour un message LinkedIn (l'extension de Claude pour Chrome). Suis son instruction : tel quel, envoie-le sans en changer un mot ; adapté, tu peux le réécrire pour cette personne, d'après son profil et nos échanges passés, en gardant toujours l'idée du message.
        4. Note ce que tu as envoyé avec `log-sent-message` : le texte réellement envoyé, et le Message-ID de l'e-mail ou la conversation du message LinkedIn. Déjà noté (l'import de ma boîte a pu le noter avant toi), continue.
        5. Un destinataire que tu ne peux pas joindre (pas d'adresse, un profil LinkedIn disparu) : écarte-le avec `dismiss-campaign-recipient` en disant pourquoi, une fois que je l'ai confirmé.
        6. N'envoie jamais un second message à un destinataire : quand `get-next-recipient` te redonne un destinataire à qui tu as écrit, arrête-toi et dis-moi qui.
        7. Recommence à l'étape 2, jusqu'à ce que `get-next-recipient` réponde que la campagne n'est plus menée (`campaignNotRunning`), ou que je te dise d'arrêter. Termine par un bilan : envoyés, écartés.

        Si Prospecter refuse un appel, arrête-toi et demande-moi quoi faire, sans contourner son refus.
        STEPS;

    /**
     * @param string $campagne le nom de la campagne, ou son identifiant
     *
     * @return list<array{role: string, content: string}>
     */
    #[McpPrompt(name: 'run-campaign', title: 'Mener une campagne', description: 'Envoie à chaque destinataire d\'une campagne démarrée son message par vos outils, et le note dans Prospecter.')]
    public function __invoke(string $campagne): array
    {
        return [['role' => 'user', 'content' => sprintf(self::STEPS, $campagne)]];
    }
}
