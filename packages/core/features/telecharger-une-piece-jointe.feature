# language: fr
Fonctionnalité: Télécharger une pièce jointe d'un échange
  En tant que prospecteur
  Je veux télécharger un fichier joint à un échange
  Afin de le relire tel qu'il a été reçu ou envoyé

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et "Michel" est membre de "SOLEAN IT" avec le droit aux données personnelles
    Et l'organisation "ACME" appartient à "SOLEAN IT"
    Et le contact "Ada Lovelace" de "ACME"
    Et "Michel" a noté le 15/10/2026 à 14h35 un e-mail reçu de "Ada Lovelace" à 14h30 : "Voici notre planning"
    Et je suis connecté en tant que "Michel"
    Et "planning.xlsx" est joint à cet échange

  Scénario: Le fichier se télécharge tel qu'il a été joint
    Alors je télécharge "planning.xlsx" tel que je l'ai joint

  Scénario: Sans le droit aux données personnelles, les pièces jointes sont cachées
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT" sans le droit aux données personnelles
    Quand je télécharge "planning.xlsx"
    Alors c'est refusé : "Only a prospector allowed to manage personal data may exchange with the contacts"

  Scénario: La pièce jointe d'un autre compte est introuvable
    Soit l'organisation "Concurrent" qui utilise Prospecter
    Et je suis connecté en tant que prospecteur de "Concurrent"
    Quand je télécharge "planning.xlsx"
    Alors c'est introuvable
