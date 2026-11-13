# language: fr
Fonctionnalité: Régler mes rappels
  En tant que membre
  Je veux choisir si et combien de minutes avant un échange planifié je suis prévenu
  Afin de ne pas le manquer, quel que soit l'appareil sur lequel je travaille

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"

  Scénario: Par défaut, je suis prévenu 10 minutes avant
    Quand je consulte mes réglages de rappel
    Alors les rappels sont activés, 10 minutes avant

  Scénario: Je change le délai de mes rappels
    Quand je règle mes rappels à 30 minutes avant
    Alors les rappels sont activés, 30 minutes avant

  Scénario: Je désactive mes rappels
    Quand je désactive mes rappels
    Alors les rappels sont désactivés

  Scénario: Désactivés, mes rappels gardent leur délai pour quand je les réactive
    Soit je règle mes rappels à 30 minutes avant
    Quand je désactive mes rappels
    Alors les rappels sont désactivés, 30 minutes avant

  Scénario: Le délai d'un rappel est l'un de ceux proposés
    Quand je règle mes rappels à 7 minutes avant
    Alors c'est refusé : "A reminder comes 0, 5, 10, 15, 30 or 60 minutes before"

  Scénario: Mes réglages ne sont qu'à moi
    Soit un autre membre de "SOLEAN IT" a réglé ses rappels à 60 minutes avant
    Quand je consulte mes réglages de rappel
    Alors les rappels sont activés, 10 minutes avant
