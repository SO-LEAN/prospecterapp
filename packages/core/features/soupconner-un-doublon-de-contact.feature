# language: fr
Fonctionnalité: Soupçonner un doublon de contact
  En tant que prospecteur
  Je veux qu'un contact qui ressemble à un autre de la même organisation soit signalé comme doublon possible, sans être refusé
  Afin de ne pas prospecter deux fois la même personne, notée sans e-mail ni lien communs

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et l'organisation "Acme SAS" appartient à "SOLEAN IT"
    Et l'organisation "Globex" appartient à "SOLEAN IT"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et le contact "Ada Lovelace" de "Acme SAS"

  Scénario: Un contact au nom proche dans la même organisation est soupçonné
    Quand j'enregistre le contact "ada LOVELACE" pour "Acme SAS" :
      | prénom | ada         |
      | nom    |  LOVELACE   |
    Alors ce n'est pas refusé
    Et le contact "ada LOVELACE" est signalé comme doublon possible de "Ada Lovelace"

  Scénario: Le même nom dans une autre organisation n'éveille pas de soupçon
    Quand j'enregistre le contact "Ada LOVELACE" pour "Globex" :
      | prénom | Ada      |
      | nom    | LOVELACE |
    Alors le contact "Ada LOVELACE" n'est pas signalé

  Scénario: Un contact d'un autre compte au même nom n'est pas soupçonné
    Soit l'organisation "Concurrent" qui utilise Prospecter
    Et l'organisation "Initech" appartient à "Concurrent"
    Et le contact "ADA Lovelace" de "Initech"
    Quand j'enregistre le contact "ada LOVELACE" pour "Globex" :
      | prénom | ada      |
      | nom    | LOVELACE |
    Alors le contact "ada LOVELACE" n'est pas signalé

  Scénario: Le contact auquel il ressemble est soupçonné en retour
    Quand j'enregistre le contact "ada LOVELACE" pour "Acme SAS" :
      | prénom | ada      |
      | nom    | LOVELACE |
    Alors le contact "Ada Lovelace" est signalé comme doublon possible de "ada LOVELACE"

  Scénario: Un contact renommé comme un autre de son organisation est soupçonné
    Soit le contact "Grace Hopper" de "Acme SAS"
    Quand je change le nom de "Grace Hopper" :
      | prénom | Ada      |
      | nom    | Lovelace |
    Alors le contact "Grace Hopper" est signalé comme doublon possible de "Ada Lovelace"

  Scénario: Un contact à rattacher renommé comme un autre n'est pas soupçonné
    Soit nous sommes le 18/11/2026 à 10h00
    Et "Grace Hopper" (grace@acme.fr) a pris une visio avec moi le 20/11/2026 à 14h00
    Quand je change le nom de "Grace Hopper" :
      | prénom | Ada      |
      | nom    | Lovelace |
    Alors le contact "Grace Hopper" n'est pas signalé

  Scénario: Un contact renommé qui ne ressemble plus n'est plus signalé
    Soit le contact "ada LOVELACE" de "Acme SAS"
    Et le contact "ada LOVELACE" a été signalé comme doublon possible de "Ada Lovelace"
    Quand je change le nom de "ada LOVELACE" :
      | prénom | Grace  |
      | nom    | Hopper |
    Alors le contact "ada LOVELACE" n'est pas signalé

  Scénario: Le contact auquel il ressemblait n'est plus signalé en retour
    Soit le contact "ada LOVELACE" de "Acme SAS"
    Et le contact "ada LOVELACE" a été signalé comme doublon possible de "Ada Lovelace"
    Quand je change le nom de "ada LOVELACE" :
      | prénom | Grace  |
      | nom    | Hopper |
    Alors le contact "Ada Lovelace" n'est pas signalé

  Scénario: Le soupçon disparaît avec l'autre contact
    Soit le contact "ada LOVELACE" de "Acme SAS"
    Et le contact "ada LOVELACE" a été signalé comme doublon possible de "Ada Lovelace"
    Quand j'efface le contact "Ada Lovelace"
    Alors le contact "ada LOVELACE" n'est pas signalé
    Et aucune levée de soupçon de contact n'a échoué

  Scénario: Sans le droit aux données personnelles, je ne vois pas qui est soupçonné
    Soit le contact "ada LOVELACE" de "Acme SAS"
    Et le contact "ada LOVELACE" a été signalé comme doublon possible de "Ada Lovelace"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT" sans le droit aux données personnelles
    Alors le contact "ada LOVELACE" n'est pas signalé
