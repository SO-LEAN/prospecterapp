# language: fr
@Prospecting/SuspectDuplicateInReturn @Prospecting/LiftSuspicionInReturn
Fonctionnalité: Soupçonner un doublon d'organisation
  En tant que prospecteur
  Je veux qu'une organisation qui ressemble à une autre soit signalée comme doublon possible, sans être refusée
  Afin de ne pas prospecter deux fois la même organisation, notée sans SIRET, sans lien ni site

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et l'organisation "Acme SAS" fait partie de mes organisations avec :
      | ville | Lyon |

  Scénario: Une organisation au nom et à la ville proches est soupçonnée
    Quand je commence à prospecter l'organisation :
      | raison sociale | ACME s.a.s |
      | ville          | Lyon       |
    Alors ce n'est pas refusé
    Et "ACME s.a.s" est signalée comme doublon possible de "Acme SAS"

  Scénario: La liste de mes organisations signale les doublons possibles
    Quand je commence à prospecter l'organisation :
      | raison sociale | ACME s.a.s |
      | ville          | Lyon       |
    Et je liste mes organisations
    Alors la liste affiche :
      | nom        | doublon possible de |
      | ACME s.a.s | Acme SAS            |
      | Acme SAS   | ACME s.a.s          |

  Scénario: L'organisation à laquelle elle ressemble est soupçonnée en retour
    Quand je commence à prospecter l'organisation :
      | raison sociale | ACME s.a.s |
      | ville          | Lyon       |
    Alors "Acme SAS" est signalée comme doublon possible de "ACME s.a.s"

  Scénario: La forme juridique ne compte pas
    Quand je commence à prospecter l'organisation :
      | raison sociale | Acme |
      | ville          | Lyon |
    Alors "Acme" est signalée comme doublon possible de "Acme SAS"

  Scénario: Une autre ville n'éveille pas de soupçon
    Quand je commence à prospecter l'organisation :
      | raison sociale | ACME      |
      | ville          | Marseille |
    Alors "ACME" n'est pas signalée
    Et "Acme SAS" n'est pas signalée

  Scénario: Une organisation renommée comme une autre est soupçonnée
    Soit l'organisation "Globex" fait partie de mes organisations avec :
      | ville | Lyon |
    Quand je révise l'identité de "Globex" :
      | raison sociale | ACME |
    Alors "ACME" est signalée comme doublon possible de "Acme SAS"

  Scénario: Une organisation déménagée dans la ville d'une autre est soupçonnée
    Soit l'organisation "ACME" fait partie de mes organisations avec :
      | ville | Marseille |
    Quand je déménage "ACME" à l'adresse :
      | ville | Lyon |
    Alors "ACME" est signalée comme doublon possible de "Acme SAS"

  Scénario: Une organisation renommée qui ne ressemble plus n'est plus signalée
    Soit l'organisation "ACME s.a.s" fait partie de mes organisations avec :
      | ville | Lyon |
    Et "ACME s.a.s" a été signalée comme doublon possible de "Acme SAS"
    Quand je révise l'identité de "ACME s.a.s" :
      | raison sociale | Globex |
    Alors "Globex" n'est pas signalée

  Scénario: L'organisation à laquelle elle ressemblait n'est plus signalée en retour
    Soit l'organisation "ACME s.a.s" fait partie de mes organisations avec :
      | ville | Lyon |
    Et "ACME s.a.s" a été signalée comme doublon possible de "Acme SAS"
    Quand je déménage "ACME s.a.s" à l'adresse :
      | ville | Marseille |
    Alors "Acme SAS" n'est pas signalée

  Scénario: Le soupçon disparaît avec l'autre organisation
    Soit l'organisation "ACME s.a.s" fait partie de mes organisations avec :
      | ville | Lyon |
    Et "ACME s.a.s" a été signalée comme doublon possible de "Acme SAS"
    Quand j'arrête de prospecter "Acme SAS"
    Alors "ACME s.a.s" n'est pas signalée
    Et aucune levée de soupçon n'a échoué

  Scénario: Un autre compte n'est pas concerné
    Soit l'organisation "Globex" qui utilise Prospecter
    Et je suis connecté en tant que prospecteur de "Globex"
    Quand je commence à prospecter l'organisation :
      | raison sociale | ACME |
      | ville          | Lyon |
    Alors "ACME" n'est pas signalée
