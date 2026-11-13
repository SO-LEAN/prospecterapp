# language: fr
Fonctionnalité: Publier les faits de Prospecting
  En tant que contexte de Prospecter
  Je veux connaître l'état de chaque organisation et de chaque échange qui change, sans lire les tables de Prospecting
  Afin de compter les organisations et les échanges d'un compte

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et "Michel" est membre de "SOLEAN IT" avec le droit aux données personnelles
    Et je suis connecté en tant que "Michel"
    Et le géocodeur situe "Strasbourg" en 7.75, 48.58

  Scénario: Une organisation géolocalisée publie un fait présent
    Quand je commence à prospecter l'organisation :
      | raison sociale | ACME       |
      | ville          | Strasbourg |
    Alors le fait publié de "ACME" est présente, géolocalisée, révision 1

  Scénario: Une organisation déplacée sans point géographique n'est plus géolocalisée
    Soit je commence à prospecter l'organisation :
      | raison sociale | ACME       |
      | ville          | Strasbourg |
    Quand je déménage "ACME" à l'adresse :
      | ville | Nulle-part |
    Alors le fait publié de "ACME" est présente, non géolocalisée, révision 2

  Scénario: Une organisation qu'on arrête de prospecter donne un fait terminal
    Soit l'organisation "ACME" fait partie de mes organisations avec :
      | ville | Strasbourg |
    Quand j'arrête de prospecter "ACME"
    Alors le fait publié de "ACME" est absente, non géolocalisée, révision 0

  Scénario: Un échange corrigé publie son nouveau moment
    Soit l'organisation "ACME" fait partie de mes organisations
    Et "Michel" a noté le 15/10/2026 à 14h35 un appel reçu de "ACME" elle-même à 14h30 : "Rappeler en mars"
    Quand je corrige cet échange :
      | moment | 14/10/2026 09:00 |
    Alors le fait publié de cet échange est présent, fait, noté le "14/10/2026 09:00", révision 2

  Scénario: Un échange effacé avec la seule organisation qui le gardait donne un fait terminal
    Soit l'organisation "ACME" fait partie de mes organisations
    Et "Michel" a noté le 15/10/2026 à 14h35 un appel reçu de "ACME" elle-même à 14h30 : "Rappeler en mars"
    Quand j'arrête de prospecter "ACME"
    Alors le fait publié de cet échange est absent, révision 0

  Scénario: Un échange planifié publie qu'il est planifié
    Soit l'organisation "ACME" fait partie de mes organisations
    Quand je planifie une visio avec "ACME" le 17/11/2026 à 14h00
    Alors le fait publié de cet échange est présent, planifié, noté le "17/11/2026 14:00", révision 1

  Plan du scénario: Un échange planifié qu'on traite publie son état
    Soit l'organisation "ACME" fait partie de mes organisations
    Et j'ai planifié une visio avec "ACME" le 17/11/2026 à 14h00
    Et nous sommes le 17/11/2026 à 15h00
    Quand <geste>
    Alors le fait publié de cet échange est présent, <statut>, noté le "<moment>", révision 2

    Exemples:
      | geste                                       | statut   | moment           |
      | je marque cette visio faite                 | fait     | 17/11/2026 14:00 |
      | je marque cette visio manquée               | manqué   | 17/11/2026 14:00 |
      | je déplace cette visio au 18/11/2026 à 9h00 | planifié | 18/11/2026 09:00 |
      | j'annule cette visio                        | annulé   | 17/11/2026 14:00 |
