# language: fr
Fonctionnalité: Consulter les chiffres
  En tant que membre d'un compte
  Je veux connaître les chiffres de mon compte
  Afin de mesurer ma prospection

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et "Michel" est membre de "SOLEAN IT" avec le droit aux données personnelles
    Et je suis connecté en tant que "Michel"

  Scénario: Avant tout recalcul, les chiffres sont à zéro
    Soit je commence à prospecter l'organisation :
      | raison sociale | ACME |
    Alors mes chiffres sont :
      | organisations prospectées   | 0 |
      | organisations géolocalisées | 0 |
      | contacts                    | 0 |
      | échanges                    | 0 |
      | échanges du mois            | 0 |

  Scénario: Un compte sans aucun fait a tous ses chiffres à zéro
    Soit l'organisation "Globex" qui utilise Prospecter
    Et "Paul" est membre de "Globex" avec le droit aux données personnelles
    Et les chiffres ont été recalculés
    Et je commence à prospecter l'organisation :
      | raison sociale | ACME |
    Quand je suis connecté en tant que "Paul"
    Alors mes chiffres sont :
      | organisations prospectées   | 0 |
      | organisations géolocalisées | 0 |
      | contacts                    | 0 |
      | échanges                    | 0 |
      | échanges du mois            | 0 |

  Scénario: Les échanges du mois sont ceux du mois courant, en UTC
    Soit les chiffres ont été recalculés
    Et l'organisation "ACME" fait partie de mes organisations
    Et "Michel" a noté le 31/10/2026 à 23h40 un appel reçu de "ACME" elle-même à 23h30 : "Noté à Paris le 31 octobre"
    Et "Michel" a noté le 01/11/2026 à 00h40 un appel reçu de "ACME" elle-même à 00h30 : "Noté à Paris le 1er novembre, en octobre en UTC"
    Et "Michel" a noté le 01/11/2026 à 01h40 un appel reçu de "ACME" elle-même à 01h30 : "En novembre partout"
    Quand nous sommes le 01/11/2026 à 12h00
    Alors mes chiffres sont :
      | échanges         | 3 |
      | échanges du mois | 1 |
    Et nous sommes le 31/10/2026 à 12h00
    Et mes chiffres sont :
      | échanges         | 3 |
      | échanges du mois | 2 |
