# language: fr
Fonctionnalité: Recalculer les chiffres
  En tant que Prospecter
  Je veux recalculer les chiffres de chaque compte depuis l'état des autres contextes
  Afin de les tenir quand Reporting démarre ou qu'ils se sont trompés

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et "Michel" est membre de "SOLEAN IT" avec le droit aux données personnelles
    Et je suis connecté en tant que "Michel"
    Et le géocodeur situe "Strasbourg" en 7.75, 48.58
    Et nous sommes le 20/10/2026 à 14h00

  Scénario: Un recalcul donne les mêmes chiffres que l'application des événements
    Soit les chiffres ont été recalculés
    Et je commence à prospecter l'organisation :
      | raison sociale | ACME       |
      | ville          | Strasbourg |
    Et je commence à prospecter l'organisation :
      | raison sociale | Globex |
    Et je commence à prospecter l'organisation :
      | raison sociale | Initech |
    Et j'enregistre le contact "Jean" pour "ACME" :
      | prénom | Jean   |
      | nom    | Dupont |
    Et "Michel" a noté le 15/10/2026 à 14h35 un appel reçu de "ACME" elle-même à 14h30 : "Rappeler en mars"
    Et "Michel" a noté le 15/09/2026 à 10h00 un appel reçu de "Globex" elle-même à 09h30 : "Rappeler en avril"
    Et j'arrête de prospecter "Initech"
    Et mes chiffres sont :
      | organisations prospectées   | 2 |
      | organisations géolocalisées | 1 |
      | contacts                    | 1 |
      | échanges                    | 2 |
      | échanges du mois            | 1 |
    Quand les chiffres sont recalculés
    Alors mes chiffres sont :
      | organisations prospectées   | 2 |
      | organisations géolocalisées | 1 |
      | contacts                    | 1 |
      | échanges                    | 2 |
      | échanges du mois            | 1 |

  Scénario: Un recalcul compte ce que Reporting n'a jamais reçu
    Soit je commence à prospecter l'organisation :
      | raison sociale | ACME       |
      | ville          | Strasbourg |
    Et j'enregistre le contact "Jean" pour "ACME" :
      | prénom | Jean   |
      | nom    | Dupont |
    Quand les chiffres sont recalculés
    Alors mes chiffres sont :
      | organisations prospectées   | 1 |
      | organisations géolocalisées | 1 |
      | contacts                    | 1 |

  Scénario: Pendant un recalcul, un fait reçu est appliqué aux deux versions
    Soit les chiffres ont été recalculés
    Et je commence à prospecter l'organisation :
      | raison sociale | ACME |
    Quand les chiffres sont recalculés, et qu'avant qu'il ne s'achève je commence à prospecter l'organisation :
      | raison sociale | Globex     |
      | ville          | Strasbourg |
    Alors mes chiffres sont :
      | organisations prospectées   | 2 |
      | organisations géolocalisées | 1 |

  Scénario: Un fait enregistré pendant qu'un recalcul relancé vide sa version est compté
    Soit les chiffres ont été recalculés
    Et je commence à prospecter l'organisation :
      | raison sociale | ACME |
    Et un recalcul interrompu, sa version encore en construction
    Quand les chiffres sont recalculés, et que pendant qu'il vide les versions abandonnées je commence à prospecter l'organisation :
      | raison sociale | Globex |
    Alors mes chiffres sont :
      | organisations prospectées | 2 |
    Et seule la nouvelle version active garde des faits et des chiffres

  Scénario: Un recalcul relancé ne laisse rien des versions abandonnées
    Soit les chiffres ont été recalculés
    Et je commence à prospecter l'organisation :
      | raison sociale | ACME |
    Et un recalcul achevé, et un fait enregistré dans l'ancienne version après qu'il l'a retirée
    Et un recalcul interrompu
    Quand il est relancé jusqu'au bout
    Alors seule la nouvelle version active garde des faits et des chiffres
    Et mes chiffres sont :
      | organisations prospectées | 1 |

  Scénario: Un recalcul ne compte que les échanges qui ont eu lieu
    Soit l'organisation "ACME" fait partie de mes organisations
    Et le contact "Ada Lovelace" de "ACME"
    Et "Michel" a noté le 15/10/2026 à 14h35 un appel reçu de "ACME" elle-même à 14h30 : "Rappeler en mars"
    Et j'ai planifié une visio avec "Ada Lovelace" le 17/11/2026 à 14h00
    Et j'ai annulé cette visio
    Et j'ai planifié une visio avec "Ada Lovelace" le 18/11/2026 à 14h00
    Quand les chiffres sont recalculés
    Alors mes chiffres sont :
      | échanges         | 1 |
      | échanges du mois | 1 |
