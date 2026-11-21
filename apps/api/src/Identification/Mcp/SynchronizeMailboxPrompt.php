<?php

namespace App\Identification\Mcp;

use Mcp\Server\Capability\Attribute\McpPrompt;

/**
 * The steps an AI follows to note the e-mails of the mailbox of its member, read with its own tools, exchanged with
 * those Prospecter knows, each once: the member asks it in their words.
 */
final readonly class SynchronizeMailboxPrompt
{
    private const string IDENTIFYING = <<<'STEP'
        Demande à Prospecter qui il connaît parmi les correspondants avec `identify`, par lots de cent au plus : chaque personne avec ses prénom et nom et son adresse e-mail, et son entreprise avec son nom et l'adresse e-mail (son domaine la désigne). Ne garde que les correspondants `known`, sauf ceux que je te demande de créer : ceux-là, enregistre-les avec `record-contact`. Pour chaque personne ou entreprise `toConfirm`, montre-moi ses candidats et demande-moi laquelle c'est, ou si c'en est une autre.
        STEP;

    private const string NOTING = <<<'STEP'
        Note chaque e-mail échangé avec un correspondant gardé avec `log-contact-exchange` sur le contact (`log-organization-exchange` quand c'est l'adresse de l'entreprise) : le canal `email`, le sens (`inbound` reçu, `outbound` envoyé), `occurredAt` le moment de son envoi, son sujet puis son texte, et `messageId`, son en-tête Message-ID tel que tu le lis dans la boîte. Un e-mail envoyé à deux contacts se note avec chacun.
        STEP;

    private const string REPORT = <<<'STEP'
        Termine par un bilan : créés, complétés, déjà notés, ignorés. Un e-mail `noted` compte comme créé, `alreadyNoted` et `erasedBefore` comptent comme déjà notés ; un contact que je t'ai demandé de créer compte comme créé s'il est `created`, comme complété s'il est `known` avec des champs complétés ; un e-mail d'un correspondant que Prospecter ne connaît pas, comme ignoré. Une synchronisation relancée ne doit compter que des déjà notés et des ignorés.
        STEP;

    private const string RULES = <<<'STEP'
        Règles :
        - Ne crée jamais une personne ou une entreprise `toConfirm` sans ma réponse.
        - Ne note jamais un e-mail sans son Message-ID quand tu le lis.
        - Si Prospecter refuse un appel, arrête-toi et demande-moi quoi faire, sans contourner son refus.
        STEP;

    /**
     * @param ?string $since depuis quand (« 7 jours », « 1er novembre »…), demandé sinon
     *
     * @return list<array{role: string, content: string}>
     */
    #[McpPrompt(name: 'sync-mailbox', title: 'Synchroniser ma boîte e-mail', description: 'Note dans Prospecter les e-mails échangés avec vos contacts, sans rien doublonner.')]
    public function __invoke(?string $since = null): array
    {
        return [['role' => 'user', 'content' => implode("\n\n", [self::reading($since), self::IDENTIFYING, self::NOTING, self::REPORT, self::RULES])]];
    }

    private static function reading(?string $since): string
    {
        return sprintf('Synchronise dans Prospecter ma boîte e-mail. %s Lis-la avec tes propres outils (le connecteur Gmail de Claude par exemple) : Prospecter ne la lit pas pour toi.', SynchronizedSince::said($since));
    }
}
