# language: fr
Fonctionnalité: Fixer un plafond d'envois par jour
  En tant que prospecteur
  Je veux fixer sur une campagne un plafond d'envois par jour, que l'IA qui la mène respecte et que je vois à l'écran
  Afin de ne pas faire repérer mon compte LinkedIn ou ma boîte e-mail, ce garde-fou ne m'empêchant jamais de le dépasser

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et l'organisation "ACME" appartient à "SOLEAN IT", taguée "#ESN"
    Et le contact "Ada Lovelace" de "ACME" a l'adresse e-mail "ada@acme.test"
    Et le contact "Grace Hopper" de "ACME" a l'adresse e-mail "grace@acme.test"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et nous sommes le 16/11/2026 à 17h00

  Scénario: Le plafond se règle à la préparation
    Quand je prépare la campagne "Relance PHP" avec un plafond de 30 envois par jour
    Alors la campagne "Relance PHP" a un plafond de 30 envois par jour

  Scénario: Sans plafond fixé à la préparation, la campagne n'en a aucun
    Quand je prépare la campagne "Relance PHP" avec le canal "E-mail" et les tags "#ESN"
    Alors la campagne "Relance PHP" n'a pas de plafond par jour

  Scénario: Un plafond de moins d'un envoi est refusé à la préparation
    Quand je prépare la campagne "Relance PHP" avec un plafond de 0 envoi par jour
    Alors c'est refusé : "A daily cap is at least 1 send"
