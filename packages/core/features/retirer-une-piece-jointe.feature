# language: fr
Fonctionnalité: Retirer une pièce jointe d'un échange
  En tant que prospecteur
  Je veux retirer un fichier joint par erreur à un échange
  Afin qu'il ne soit plus gardé

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et "Michel" est membre de "SOLEAN IT" avec le droit aux données personnelles
    Et l'organisation "ACME" appartient à "SOLEAN IT"
    Et le contact "Ada Lovelace" de "ACME"
    Et "Michel" a noté le 15/10/2026 à 14h35 un e-mail reçu de "Ada Lovelace" à 14h30 : "Voici notre cahier des charges"
    Et je suis connecté en tant que "Michel"
    Et "cahier-des-charges.pdf" est joint à cet échange
    Et "planning.xlsx" est joint à cet échange

  Scénario: Une pièce jointe retirée n'est plus listée ni téléchargeable
    Quand je retire "planning.xlsx"
    Alors le détail de l'échange liste :
      | cahier-des-charges.pdf |
    Et le fichier "planning.xlsx" n'est plus téléchargeable
