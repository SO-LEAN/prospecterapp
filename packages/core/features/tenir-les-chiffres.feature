# language: fr
Fonctionnalité: Tenir les chiffres
  En tant que Reporting
  Je veux appliquer les faits que Prospecting et PersonalData publient, dans l'ordre où ils arrivent et autant de fois qu'ils arrivent
  Afin de tenir les chiffres de chaque compte sans lire les tables des autres contextes

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et "Michel" est membre de "SOLEAN IT" avec le droit aux données personnelles
    Et je suis connecté en tant que "Michel"
    Et le géocodeur situe "Strasbourg" en 7.75, 48.58
    Et les chiffres ont été recalculés

  Scénario: Un fait de révision inférieure ou égale à la connue est ignoré
    Soit l'organisation "ACME" fait partie de mes organisations
    Et Reporting a reçu le fait de "ACME" : présente, géolocalisée, révision 5
    Quand Reporting reçoit le fait de "ACME" : présente, non géolocalisée, révision 4
    Et Reporting reçoit le fait de "ACME" : présente, non géolocalisée, révision 5
    Alors mes chiffres sont :
      | organisations prospectées   | 1 |
      | organisations géolocalisées | 1 |

  Scénario: Un fait de révision supérieure remplace le connu
    Soit l'organisation "ACME" fait partie de mes organisations
    Et Reporting a reçu le fait de "ACME" : présente, non géolocalisée, révision 1
    Quand Reporting reçoit le fait de "ACME" : présente, géolocalisée, révision 2
    Alors mes chiffres sont :
      | organisations prospectées   | 1 |
      | organisations géolocalisées | 1 |

  Scénario: Un fait terminal l'emporte sur tout fait non terminal et n'est jamais remplacé
    Soit l'organisation "ACME" fait partie de mes organisations
    Et Reporting a reçu le fait de "ACME" : présente, géolocalisée, révision 5
    Quand Reporting reçoit le fait de "ACME" : absente, non géolocalisée, révision 0
    Et Reporting reçoit le fait de "ACME" : présente, géolocalisée, révision 6
    Alors mes chiffres sont :
      | organisations prospectées   | 0 |
      | organisations géolocalisées | 0 |

  Scénario: Un fait terminal reçu avant tout autre fait de l'organisation l'empêche de compter
    Soit l'organisation "ACME" fait partie de mes organisations
    Quand Reporting reçoit le fait de "ACME" : absente, non géolocalisée, révision 0
    Et Reporting reçoit le fait de "ACME" : présente, géolocalisée, révision 1
    Alors mes chiffres sont :
      | organisations prospectées   | 0 |
      | organisations géolocalisées | 0 |

  Scénario: Le même fait reçu deux fois ne compte qu'une fois
    Soit je commence à prospecter l'organisation :
      | raison sociale | ACME       |
      | ville          | Strasbourg |
    Quand Reporting reçoit encore le dernier fait publié de "ACME"
    Alors mes chiffres sont :
      | organisations prospectées   | 1 |
      | organisations géolocalisées | 1 |

  Scénario: Une organisation déplacée sans point géographique quitte les organisations géolocalisées
    Soit je commence à prospecter l'organisation :
      | raison sociale | ACME       |
      | ville          | Strasbourg |
    Quand je déménage "ACME" à l'adresse :
      | ville | Nulle-part |
    Alors mes chiffres sont :
      | organisations prospectées   | 1 |
      | organisations géolocalisées | 0 |

  Scénario: Les contacts et les échanges notés comptent, ceux qu'on arrête de garder ne comptent plus
    Soit je commence à prospecter l'organisation :
      | raison sociale | ACME |
    Et j'enregistre le contact "Jean" pour "ACME" :
      | prénom | Jean   |
      | nom    | Dupont |
    Et "Michel" a noté le 15/10/2026 à 14h35 un appel reçu de "ACME" elle-même à 14h30 : "Rappeler en mars"
    Et nous sommes le 20/10/2026 à 14h00
    Quand j'arrête de prospecter "ACME"
    Alors mes chiffres sont :
      | organisations prospectées   | 0 |
      | contacts                    | 0 |
      | échanges                    | 0 |
      | échanges du mois            | 0 |

  Scénario: Un échange dont le moment change de mois passe d'un compteur mensuel à l'autre
    Soit l'organisation "ACME" fait partie de mes organisations
    Et "Michel" a noté le 15/10/2026 à 14h35 un appel reçu de "ACME" elle-même à 14h30 : "Rappeler en mars"
    Quand je corrige cet échange :
      | moment | 30/09/2026 09:00 |
    Alors mes chiffres sont :
      | échanges         | 1 |
      | échanges du mois | 0 |
    Et nous sommes le 30/09/2026 à 18h00
    Et mes chiffres sont :
      | échanges         | 1 |
      | échanges du mois | 1 |

  Scénario: Deux faits appliqués en même temps sur un compte donnent les mêmes chiffres qu'appliqués l'un après l'autre
    Soit l'organisation "ACME" fait partie de mes organisations
    Et l'organisation "Globex" fait partie de mes organisations
    Quand Reporting reçoit en même temps les faits :
      | organisation | présence | géolocalisation  | révision |
      | ACME         | présente | géolocalisée     | 1        |
      | Globex       | présente | non géolocalisée | 1        |
    Alors mes chiffres sont :
      | organisations prospectées   | 2 |
      | organisations géolocalisées | 1 |
