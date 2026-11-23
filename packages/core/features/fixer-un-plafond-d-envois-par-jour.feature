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
    Alors c'est refusé : "A daily cap must be at least 1 send"

  Scénario: Je change le plafond de la campagne démarrée
    Soit la campagne "Relance PHP" démarrée par e-mail avec "Ada Lovelace" puis "Grace Hopper", son message pour une IA "tel quel"
    Quand je fixe le plafond de "Relance PHP" à 30 envois par jour
    Alors la campagne "Relance PHP" a un plafond de 30 envois par jour

  Scénario: Je retire le plafond de la campagne
    Soit je prépare la campagne "Relance PHP" avec un plafond de 30 envois par jour
    Quand je retire le plafond de "Relance PHP"
    Alors la campagne "Relance PHP" n'a pas de plafond par jour

  Scénario: Le plafond de la campagne arrêtée ne change plus
    Soit la campagne "Relance PHP" démarrée par e-mail avec "Ada Lovelace" puis "Grace Hopper", son message pour une IA "tel quel"
    Et la campagne "Relance PHP" est arrêtée
    Quand je fixe le plafond de "Relance PHP" à 30 envois par jour
    Alors c'est refusé : "A started campaign can no longer change"

  Scénario: Au plafond du jour, l'IA est avertie
    Soit la campagne "Relance PHP" démarrée par e-mail avec "Ada Lovelace" puis "Grace Hopper", son message pour une IA "tel quel"
    Et je fixe le plafond de "Relance PHP" à 1 envoi par jour
    Et "Ada Lovelace" est traitée dans la campagne "Relance PHP"
    Quand une IA demande le destinataire suivant de "Relance PHP"
    Alors elle reçoit "Grace Hopper", avec l'avertissement « plafond du jour atteint (1) »

  Scénario: Sous le plafond du jour, l'IA n'est pas avertie
    Soit la campagne "Relance PHP" démarrée par e-mail avec "Ada Lovelace" puis "Grace Hopper", son message pour une IA "tel quel"
    Et je fixe le plafond de "Relance PHP" à 2 envois par jour
    Et "Ada Lovelace" est traitée dans la campagne "Relance PHP"
    Quand une IA demande le destinataire suivant de "Relance PHP"
    Alors elle reçoit "Grace Hopper", sans avertissement, 1 traité aujourd'hui sur 2

  Scénario: Sans plafond du jour, l'IA n'est jamais avertie
    Soit la campagne "Relance PHP" démarrée par e-mail avec "Ada Lovelace" puis "Grace Hopper", son message pour une IA "tel quel"
    Et "Ada Lovelace" est traitée dans la campagne "Relance PHP"
    Quand une IA demande le destinataire suivant de "Relance PHP"
    Alors elle reçoit "Grace Hopper", sans plafond ni avertissement

  Scénario: Le plafond du jour se compte au jour du membre
    Soit je lis l'heure de "Europe/Paris"
    Et nous sommes le "2026-11-16 22:30 UTC"
    Et la campagne "Relance PHP" démarrée par e-mail avec "Ada Lovelace" puis "Grace Hopper", son message pour une IA "tel quel"
    Et je fixe le plafond de "Relance PHP" à 1 envoi par jour
    Et "Ada Lovelace" est traitée dans la campagne "Relance PHP"
    Quand nous sommes le "2026-11-16 23:30 UTC"
    Et une IA demande le destinataire suivant de "Relance PHP"
    Alors elle reçoit "Grace Hopper", sans avertissement, 0 traité aujourd'hui sur 1

  Scénario: La fiche de la campagne montre ses traités du jour sur son plafond
    Soit la campagne "Relance PHP" démarrée par e-mail avec "Ada Lovelace" puis "Grace Hopper", son message pour une IA "tel quel"
    Et je fixe le plafond de "Relance PHP" à 30 envois par jour
    Et "Ada Lovelace" est traitée dans la campagne "Relance PHP"
    Quand je consulte la campagne "Relance PHP"
    Alors elle montre 1 traité aujourd'hui sur un plafond de 30

  Scénario: Dépasser le plafond du jour n'est pas refusé
    Soit la campagne "Relance PHP" démarrée par e-mail avec "Ada Lovelace" puis "Grace Hopper", son message pour une IA "tel quel"
    Et je fixe le plafond de "Relance PHP" à 1 envoi par jour
    Et "Ada Lovelace" est traitée dans la campagne "Relance PHP"
    Quand je note pour "Grace Hopper" dans la campagne "Relance PHP" un échange
    Alors ce n'est pas refusé
    Et "Grace Hopper" est traitée dans la campagne "Relance PHP"

  Scénario: L'IA à ma demande dépasse le plafond du jour sans refus
    Soit la campagne "Relance PHP" démarrée par e-mail avec "Ada Lovelace" puis "Grace Hopper", son message pour une IA "tel quel"
    Et je fixe le plafond de "Relance PHP" à 1 envoi par jour
    Et "Ada Lovelace" est traitée dans la campagne "Relance PHP"
    Quand une IA note l'e-mail envoyé à "Grace Hopper" pour "Relance PHP", son Message-ID "<relance-grace@solean-it.test>" et le texte "Bonjour Grace"
    Alors ce n'est pas refusé
    Et "Grace Hopper" est traitée dans la campagne "Relance PHP"
