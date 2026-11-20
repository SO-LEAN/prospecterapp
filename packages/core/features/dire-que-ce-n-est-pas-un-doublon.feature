# language: fr
Fonctionnalité: Dire que ce n'est pas un doublon
  En tant que prospecteur
  Je veux lever le soupçon de doublon entre deux organisations, après vérification
  Afin qu'elles ne soient plus signalées l'une pour l'autre

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et l'organisation "Acme SAS" fait partie de mes organisations avec :
      | ville | Lyon |
    Et l'organisation "ACME s.a.s" fait partie de mes organisations avec :
      | ville | Lyon |
    Et "ACME s.a.s" a été signalée comme doublon possible de "Acme SAS"

  Scénario: Lever le soupçon après vérification
    Quand je dis que "ACME s.a.s" n'est pas un doublon de "Acme SAS"
    Alors ce n'est pas refusé
    Et "ACME s.a.s" n'est pas signalée

  Scénario: Lever un soupçon qui n'existe pas est refusé
    Soit l'organisation "Globex" fait partie de mes organisations
    Quand je dis que "Acme SAS" n'est pas un doublon de "Globex"
    Alors c'est refusé : "The organization is not suspected of being a duplicate of this one"

  Scénario: L'autre organisation n'est plus signalée en retour
    Quand je dis que "ACME s.a.s" n'est pas un doublon de "Acme SAS"
    Alors "Acme SAS" n'est pas signalée

  Scénario: Un déménagement ne rouvre pas le soupçon levé
    Soit j'ai dit que "ACME s.a.s" n'est pas un doublon de "Acme SAS"
    Quand je déménage "ACME s.a.s" à l'adresse :
      | rue   | 1 rue de la République |
      | ville | Lyon                   |
    Alors "ACME s.a.s" n'est pas signalée

  Scénario: Un changement de nom de l'autre organisation ne rouvre pas le soupçon levé
    Soit j'ai dit que "ACME s.a.s" n'est pas un doublon de "Acme SAS"
    Quand je révise l'identité de "Acme SAS" :
      | raison sociale | Acme |
    Alors "Acme" n'est pas signalée
