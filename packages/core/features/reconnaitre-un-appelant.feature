# language: fr
Fonctionnalité: Reconnaître un appelant
  En tant que prospecteur
  Je veux savoir qui m'appelle parmi les organisations que je prospecte et mes contacts
  Afin d'ouvrir sa fiche avant de décrocher

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et l'organisation "ACME" appartient à "SOLEAN IT"
    Et le contact "Grace Hopper" de "ACME" a le téléphone "06 12 34 56 78"

  Scénario: Le numéro d'une organisation que je prospecte la fait reconnaître, quelle que soit sa saisie
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et l'organisation "Globex" fait partie de mes organisations avec :
      | téléphone | 03 88 11 22 33 |
    Quand un appel arrive du "+33388112233"
    Alors l'appel vient de l'organisation "Globex"

  Scénario: Le numéro d'un de mes contacts le fait reconnaître
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT"
    Quand un appel arrive du "0033 6 12 34 56 78"
    Alors l'appel vient du contact "Grace Hopper"

  Scénario: Un numéro inconnu ne fait reconnaître personne
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT"
    Quand un appel arrive du "+33 1 23 45 67 89"
    Alors personne n'est reconnu

  Scénario: Le numéro d'une autre organisation ne fait reconnaître personne
    Soit l'organisation "Concurrent" qui utilise Prospecter
    Et l'organisation "Initech" appartient à "Concurrent"
    Et le contact "Linus Torvalds" de "Initech" a le téléphone "06 98 76 54 32"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Quand un appel arrive du "06 98 76 54 32"
    Alors personne n'est reconnu

  Scénario: Un numéro invalide est refusé
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT"
    Quand un appel arrive du "12"
    Alors c'est refusé : "Phone number "12" is not valid"

  Scénario: Sans le droit aux données personnelles, je ne reconnais pas un contact par son numéro
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT" sans le droit aux données personnelles
    Quand un appel arrive du "06 12 34 56 78"
    Alors c'est refusé : "Only a member allowed to manage personal data may recognize a person by their phone number"
