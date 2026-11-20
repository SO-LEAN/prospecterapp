# language: fr
Fonctionnalité: Dire qu'un contact n'est pas un doublon
  En tant que prospecteur
  Je veux lever le soupçon de doublon entre deux contacts, après vérification
  Afin qu'ils ne soient plus signalés l'un pour l'autre

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et l'organisation "Acme SAS" appartient à "SOLEAN IT"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et le contact "Ada Lovelace" de "Acme SAS"
    Et le contact "ada LOVELACE" de "Acme SAS"
    Et le contact "ada LOVELACE" a été signalé comme doublon possible de "Ada Lovelace"

  Scénario: Lever le soupçon après vérification
    Quand je dis que le contact "ada LOVELACE" n'est pas un doublon de "Ada Lovelace"
    Alors ce n'est pas refusé
    Et le contact "ada LOVELACE" n'est pas signalé

  Scénario: Lever un soupçon qui n'existe pas est refusé
    Soit le contact "Grace Hopper" de "Acme SAS"
    Quand je dis que le contact "Ada Lovelace" n'est pas un doublon de "Grace Hopper"
    Alors c'est refusé : "The contact is not suspected of being a duplicate of this one"

  Scénario: Sans le droit aux données personnelles, je ne lève pas un soupçon
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT" sans le droit aux données personnelles
    Quand je dis que le contact "ada LOVELACE" n'est pas un doublon de "Ada Lovelace"
    Alors c'est refusé : "Only a member allowed to manage personal data may say a contact is no duplicate"

  Scénario: L'autre contact n'est plus signalé en retour
    Quand je dis que le contact "ada LOVELACE" n'est pas un doublon de "Ada Lovelace"
    Alors le contact "Ada Lovelace" n'est pas signalé

  Scénario: Un changement de nom ne rouvre pas le soupçon levé
    Soit j'ai dit que le contact "ada LOVELACE" n'est pas un doublon de "Ada Lovelace"
    Quand je change le nom de "ada LOVELACE" :
      | prénom | Ada      |
      | nom    | Lovelace |
    Alors le contact "ada LOVELACE" n'est pas signalé
