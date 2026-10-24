# language: fr
Fonctionnalité: Supprimer un échange
  En tant que prospecteur
  Je veux supprimer un échange noté par erreur
  Afin qu'il ne reste dans aucune timeline

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et "Michel" est membre de "SOLEAN IT" avec le droit aux données personnelles
    Et l'organisation "ACME" appartient à "SOLEAN IT"
    Et le contact "Ada Lovelace" de "ACME"
    Et "Michel" a noté le 15/10/2026 à 14h35 un message LinkedIn reçu de "Ada Lovelace" à 14h30 : "Intéressée par une mission en mars"

  Scénario: Un échange supprimé disparaît de toutes les timelines
    Soit je suis connecté en tant que "Michel"
    Quand je supprime cet échange
    Alors la timeline de "Ada Lovelace" est vide
    Et la timeline de "ACME" est vide

  Scénario: Un échange supprimé emporte ses pièces jointes
    Soit je suis connecté en tant que "Michel"
    Et "planning.xlsx" est joint à cet échange
    Quand je supprime cet échange
    Alors le fichier "planning.xlsx" n'est plus téléchargeable
