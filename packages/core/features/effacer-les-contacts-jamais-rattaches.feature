# language: fr
Fonctionnalité: Effacer les contacts jamais rattachés
  En tant que prospecteur
  Je veux qu'un contact à rattacher que personne ne rattache soit effacé 30 jours après son dernier rendez-vous
  Afin de ne pas garder les données personnelles d'une personne au-delà de ce que son rendez-vous demande

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et l'organisation "ACME" appartient à "SOLEAN IT"
    Et nous sommes le 18/11/2026 à 10h00
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et "Ada Lovelace" (ada@acme.fr) a pris une visio avec moi le 20/11/2026 à 14h00

  Scénario: Un contact jamais rattaché est effacé 30 jours après son dernier rendez-vous
    Soit j'ai noté à faire pour "Ada Lovelace" "Envoyer la proposition" pour le 25/11/2026
    Quand nous sommes le 20/12/2026 à 10h00
    Et les contacts jamais rattachés sont effacés
    Alors "Ada Lovelace" est effacée
    Et ses échanges et ses actions à faire sont effacés

  Scénario: Un contact à rattacher n'est pas effacé avant son jour
    Quand nous sommes le 19/12/2026 à 23h00
    Et les contacts jamais rattachés sont effacés
    Alors "Ada Lovelace" est un contact à rattacher, effacée le 20/12/2026 sans rattachement

  Scénario: Un contact rattaché n'est pas effacé
    Soit je rattache le contact "Ada Lovelace" à "ACME"
    Quand nous sommes le 20/12/2026 à 10h00
    Et les contacts jamais rattachés sont effacés
    Alors les contacts de "ACME" sont :
      | Ada Lovelace |

  Scénario: Un contact déjà effacé ne l'est pas deux fois
    Soit nous sommes le 20/12/2026 à 10h00
    Et les contacts jamais rattachés sont effacés
    Quand l'effacement de "Ada Lovelace" est demandé de nouveau
    Alors ce n'est pas refusé
