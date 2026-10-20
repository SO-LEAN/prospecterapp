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
    Et "ACME" ne garde aucun échange

  Scénario: Sans le droit aux données personnelles, je ne supprime pas un échange
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT" sans le droit aux données personnelles
    Quand je supprime cet échange
    Alors c'est refusé : "Only a prospector allowed to manage personal data may exchange with the contacts"

  Scénario: L'échange d'un autre compte est introuvable
    Soit l'organisation "Concurrent" qui utilise Prospecter
    Et je suis connecté en tant que prospecteur de "Concurrent"
    Quand je supprime cet échange
    Alors c'est introuvable
