<?php

namespace App\Identification\Mcp;

use Mcp\Server\Capability\Attribute\McpPrompt;
use Mcp\Server\Exception\RegistryException;

/**
 * The steps an AI follows to synchronize the contacts or the messages of LinkedIn of its member, read with its own
 * tools, so that nothing is created nor noted twice: the member asks it in their words.
 */
final readonly class SynchronizeLinkedInPrompt
{
    private const string IDENTIFYING = <<<'STEP'
        Avant de rien créer, demande à Prospecter qui il connaît avec `identify`, par lots de cent au plus : chaque personne avec ses prénom et nom et l'adresse de son profil LinkedIn parmi ses liens, et son entreprise avec son nom et l'adresse de sa page LinkedIn. Pour chaque personne ou entreprise `toConfirm`, montre-moi ses candidats et demande-moi laquelle c'est, ou si c'en est une autre. Présente-moi les personnes `new` et laisse-moi choisir celles qui m'intéressent ; ignore les autres.
        STEP;

    private const string RECORDING = <<<'STEP'
        Enregistre chaque personne retenue avec `record-contact` : son nom, sa fonction, l'adresse de son profil LinkedIn parmi ses liens, et son entreprise avec l'adresse de sa page LinkedIn.
        STEP;

    private const string NOTING = <<<'STEP'
        Note chaque message avec `log-contact-exchange` sur le contact (`log-organization-exchange` pour une page d'entreprise) : le canal `linkedIn`, le sens (`inbound` reçu, `outbound` envoyé), `occurredAt` à la minute où il a été envoyé, son texte, et `linkedInConversation`, l'identifiant de la conversation ou l'adresse de son fil (`linkedin.com/messaging/thread/<id>/`).
        STEP;

    private const string RULES = <<<'STEP'
        Règles :
        - Ne crée jamais une personne ou une entreprise `toConfirm` sans ma réponse.
        - Ne note jamais un message sans sa conversation quand tu la connais.
        - Si Prospecter refuse un appel, arrête-toi et demande-moi quoi faire, sans contourner son refus.
        STEP;

    private const string REPORT = <<<'STEP'
        Termine par un bilan : créés, complétés, déjà notés, ignorés. Un contact `created` compte comme créé ; `known` avec des champs complétés, comme complété ; `known` sans rien de complété, comme déjà noté ; un message `noted` compte comme créé, `alreadyNoted` et `erasedBefore` comptent comme déjà notés ; ce que j'ai écarté, comme ignoré. Une synchronisation relancée ne doit compter que des déjà notés.
        STEP;

    /**
     * @param string  $what  contacts ou messages
     * @param ?string $since depuis quand (« 7 jours », « 1er novembre »…), demandé sinon
     *
     * @return list<array{role: string, content: string}>
     */
    #[McpPrompt(name: 'sync-linkedin', title: 'Synchroniser LinkedIn', description: 'Enregistre dans Prospecter vos contacts LinkedIn intéressants, ou note vos messages LinkedIn, sans rien doublonner.')]
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
        return sprintf('Synchronise dans Prospecter mes %s LinkedIn%s. Lis-les avec tes propres outils (l\'extension Chrome de Claude par exemple) : Prospecter ne lit pas LinkedIn.', $what->value, SynchronizedSince::said($since));
    }
}
